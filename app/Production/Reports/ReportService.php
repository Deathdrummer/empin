<?php

namespace App\Production\Reports;

use App\Models\AuditEntry;
use App\Models\Erp\ContractPlanRevision;
use App\Models\Erp\ContractReadinessEvent;
use App\Models\Erp\WorkType;
use App\Models\Staff;
use App\Models\StaffNotification;
use App\Models\TaskReport;
use App\Models\TaskReportItem;
use App\Models\TaskReportPhoto;
use App\Models\TimesheetContract;
use App\Production\Attendance\AttendanceNotices;
use App\Production\Objects\ObjectDirectory;
use App\Production\Rights\Right;
use App\Production\Support\NoticeTexts;
use App\Production\Support\ProductionException;
use App\Production\Tasks\TaskAccess;
use App\Production\Tasks\TaskFacts;
use App\Production\Tasks\Viewer;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Отчёт по заданию: заявление, согласование, отклонение, правка согласованного, фото (ТЗ, раздел 8) */
final class ReportService
{
    public const MAX_ITEMS = 200;
    public const MAX_PHOTOS = 30;
    public const PHOTO_DIR = 'production/reports';

    public function __construct(
        private readonly TaskAccess $tasks,
        private readonly ReportAccess $access,
        private readonly ReportAllocations $allocations,
        private readonly ObjectReadiness $readiness,
        private readonly AttendanceNotices $notices,
        private readonly ReportRecipients $recipients,
    ) {
    }

    /** Видимое задание и его отчёт (или 404) */
    public function find(Viewer $viewer, int $taskId): array
    {
        $task = TimesheetContract::find($taskId);
        if (!$task || !$this->tasks->canView($viewer, $facts = TaskFacts::fromModel($task))) {
            throw ProductionException::notFound();
        }

        return [$task, $facts, TaskReport::where('task_id', $task->id)->first()];
    }

    /**
     * Создать или изменить отчёт автором (право 8): черновик, отправка, правка отправленного до решения (П-ФК-09…11).
     * Без связи заполненное приходит позже: отказ — с уведомлением автору, данные остаются на телефоне (П-ФК-43, 44).
     *
     * @return array{0: TaskReport, 1: array} отчёт и предупреждения
     */
    public function save(Viewer $viewer, int $taskId, array $input, CarbonImmutable $today): array
    {
        $offline = (bool) ($input['offline'] ?? false);
        $result = DB::transaction(function () use ($viewer, $taskId, $input, $offline) {
            $task = TimesheetContract::whereKey($taskId)->lockForUpdate()->first();
            if (!$task || !$this->tasks->canView($viewer, $facts = TaskFacts::fromModel($task))) {
                return [null, ProductionException::notFound('Задание удалено или нет доступа'), null];
            }
            $report = TaskReport::where('task_id', $task->id)->lockForUpdate()->first();
            $created = false;
            if (!$report) {
                if (!$this->access->canCreate($viewer, $facts, null)) {
                    return [$task, ProductionException::forbidden('Нет права «Заявлять отчёт»', 'no_right'), null];
                }
                $report = TaskReport::create([
                    'task_id' => $task->id,
                    'status' => TaskReport::DRAFT,
                    'author_staff_id' => $viewer->staffId,
                    // Подставленное в поле значение запоминается (П-ФК-35а): без связи телефон показывал своё
                    'object_percent_prefilled' => array_key_exists('object_percent_prefilled', $input)
                        ? ObjectReadiness::parse($input['object_percent_prefilled'])
                        : $this->readiness->current((int) $task->contract_id),
                    'client_uuid' => $input['client_uuid'] ?? null,
                ]);
                $created = true;
            } elseif ((int) $report->author_staff_id !== $viewer->staffId) {
                return [$task, ProductionException::invalid('По заданию уже есть отчёт другого автора', 'report_exists'), $report];
            } elseif (!$report->open()) {
                return [$task, ProductionException::invalid('Отчёт уже согласован', 'report_approved'), $report];
            } elseif (!$this->access->canEdit($viewer, $facts, $report)) {
                return [$task, ProductionException::forbidden('Нет права изменять отчёт', 'report_locked'), $report];
            }

            $items = $this->items($report, $input['items'] ?? []);
            $before = $this->snapshot($report);
            $report->fill([
                'text' => $this->text($input['text'] ?? null),
                'task_percent_declared' => ObjectReadiness::parse($input['task_percent'] ?? null, 'Процент задания — число от 0 до 100'),
                'object_percent_declared' => ObjectReadiness::parse($input['object_percent'] ?? null, 'Готовность объекта — число от 0 до 100'),
            ]);

            $submit = (bool) ($input['submit'] ?? false) && $report->status !== TaskReport::SUBMITTED;
            $problems = $submit || $report->status === TaskReport::SUBMITTED
                ? $this->problems((int) $task->contract_id, $items, $viewer, approving: false, exceptReportId: $report->id)
                : [];
            if ($problems && $report->status === TaskReport::SUBMITTED) {
                // Отправленный отчёт с превышением без права не меняется (П-ФК-44)
                return [$task, ProductionException::invalid($problems[0], 'not_allowed'), $report];
            }

            $this->replaceDeclared($report, $items);
            $warnings = [];
            if ($submit && $problems) {
                // Отчёт остаётся черновиком (П-ФК-44)
                $warnings[] = ['code' => 'not_submitted', 'message' => $problems[0]];
            } elseif ($submit) {
                $report->fill([
                    'status' => TaskReport::SUBMITTED,
                    'submitted_by_staff_id' => $viewer->staffId,
                    'submitted_at' => now(),
                    'decided_by_staff_id' => null,
                    'decided_at' => null,
                    'reject_comment' => null,
                ]);
            }

            $provided = $submit && !$problems;
            if ($created || $report->isDirty() || $before != $this->snapshot($report)) {
                $report->revision = (int) $report->revision + ($created ? 0 : 1);
                $report->save();
                $this->audit($report->id, $created ? 'created' : ($provided ? 'submitted' : 'saved'), $viewer, $created ? null : $before, $this->snapshot($report->fresh()));
                if ($created && $provided) {
                    $this->audit($report->id, 'submitted', $viewer, null, null);
                }
            }

            return [$task, null, $report->fresh(), $warnings, $provided];
        });

        [$task, $error, $report] = $result;
        if ($error) {
            if ($offline) {
                $this->notNotified($viewer->staffId, $task, 'Отчёт не принят', 'не принят', $error->getMessage(), $taskId, $today);
            }
            throw $error;
        }
        $warnings = $result[3] ?? [];
        if ($offline && $warnings) {
            $this->notNotified($viewer->staffId, $task, 'Отчёт не отправлен', 'не отправлен', $warnings[0]['message'], $taskId, $today);
        }
        if ($result[4] ?? false) {
            $this->afterProvided($task, $report, $viewer, $today, submitted: true);
        }

        return [$report, $warnings];
    }

    /**
     * Согласовать одним действием, поправив любые данные (П-ФК-13, П-ФК-14). Чужой черновик считается заявленным
     * согласующим; изменённые количества в отправленном — уведомление заявителю (П-ФК-16). Только при связи.
     */
    public function approve(Viewer $viewer, int $taskId, array $input, CarbonImmutable $today): TaskReport
    {
        [$task, $report, $changes, $wasProvided] = DB::transaction(function () use ($viewer, $taskId, $input) {
            [$task, $facts, $report] = $this->lockForDecision($viewer, $taskId, (int) ($input['revision'] ?? 0));
            if (!$this->access->canApprove($viewer, $facts, $report)) {
                throw ProductionException::forbidden('Нет права согласовывать этот отчёт');
            }
            $items = $this->items($report, $input['items'] ?? [], requireQuantity: true);
            if ($problems = $this->problems((int) $task->contract_id, $items, $viewer, approving: true, exceptReportId: $report->id)) {
                throw ProductionException::invalid($problems[0], 'not_allowed');
            }

            $before = $this->snapshot($report);
            $wasSubmitted = $report->status !== TaskReport::DRAFT;
            $wasProvided = $report->provided();
            $changes = $wasSubmitted ? $this->quantityChanges($report, $items) : [];
            $taskPercent = ObjectReadiness::parse($input['task_percent'] ?? null, 'Процент задания — число от 0 до 100');
            $objectPercent = ObjectReadiness::parse($input['object_percent'] ?? null, 'Готовность объекта — число от 0 до 100');

            $this->replaceApproved($report, $items, declare: !$wasSubmitted);
            $report->fill([
                'text' => array_key_exists('text', $input) ? $this->text($input['text']) : $report->text,
                'task_percent_approved' => $taskPercent,
                'object_percent_approved' => $objectPercent,
                'status' => TaskReport::APPROVED,
                'decided_by_staff_id' => $viewer->staffId,
                'decided_at' => now(),
                'reject_comment' => null,
                'revision' => (int) $report->revision + 1,
            ]);
            if (!$wasSubmitted) {
                // Черновик заявлен и согласован согласующим (П-ФК-13, П-ФК-19)
                $report->fill([
                    'submitted_by_staff_id' => $viewer->staffId,
                    'submitted_at' => now(),
                    'task_percent_declared' => $taskPercent,
                    'object_percent_declared' => $objectPercent,
                ]);
            }
            $report->save();

            // Поле не меняли — готовность не трогается и в ERP ничего не уходит (П-ФК-35а)
            if ($objectPercent !== null && $objectPercent !== $report->object_percent_prefilled) {
                $this->readiness->apply((int) $task->contract_id, $objectPercent, ContractReadinessEvent::SOURCE_REPORT_APPROVED,
                    ['staff_id' => $viewer->staffId, 'report_id' => $report->id, 'task_id' => $task->id]);
            }
            $this->allocations->recompute((int) $task->contract_id);
            $this->audit($report->id, 'approved', $viewer, $before, $this->snapshot($report->fresh()));

            return [$task, $report->fresh(), $changes, $wasProvided];
        });

        if ($changes && (int) $report->author_staff_id !== $viewer->staffId) {
            // «Иванов Сергей Петрович изменил количество в отчёте за 18 ноября по объекту …: опоры — 5 шт вместо 6 шт.»
            $items = implode('; ', array_map(fn ($c) => sprintf('%s — %s вместо %s', NoticeTexts::lower((string) $c['name']),
                $this->quantityText($c['approved'], $c['unit']), $this->quantityText($c['declared'], $c['unit'])), $changes));
            $this->notices->send((int) $report->author_staff_id, StaffNotification::KIND_REPORT_QUANTITIES_CHANGED, 'Отчёт согласован с изменениями',
                sprintf('%s количество в отчёте %s: %s.', $this->whoDid($viewer->staffId, 'изменил'), $this->about($task, $today), $items),
                ['task_id' => (int) $task->id, 'changes' => $changes], push: true, taskId: (int) $task->id);
        }
        if (!$wasProvided) {
            $this->afterProvided($task, $report, $viewer, $today, submitted: false);
        }

        return $report;
    }

    /** Отклонить отправленный отчёт: заявитель исправит и отправит тот же отчёт (П-ФК-17). Только при связи. */
    public function reject(Viewer $viewer, int $taskId, array $input): TaskReport
    {
        $report = DB::transaction(function () use ($viewer, $taskId, $input) {
            [$task, $facts, $report] = $this->lockForDecision($viewer, $taskId, (int) ($input['revision'] ?? 0));
            if (!$this->access->canReject($viewer, $facts, $report)) {
                throw ProductionException::forbidden('Нет права отклонять этот отчёт');
            }
            $before = $this->snapshot($report);
            $report->update([
                'status' => TaskReport::REJECTED,
                'decided_by_staff_id' => $viewer->staffId,
                'decided_at' => now(),
                'reject_comment' => $this->text($input['comment'] ?? null),
                'revision' => (int) $report->revision + 1,
            ]);
            $this->audit($report->id, 'rejected', $viewer, $before, $this->snapshot($report->fresh()));

            return $report->fresh();
        });
        // Отклонённый не считается предоставленным: плашки «отчёт предоставлен» больше не верны (П-ЗД-37)
        StaffNotification::query()->open()->where('kind', StaffNotification::KIND_REPORT_PROVIDED)->where('task_id', $taskId)->update(['withdrawn_at' => now()]);

        return $report;
    }

    /**
     * Правка согласованного (право 10): содержимое меняется, статус остаётся, на отчёте и позициях — «изменено кем,
     * когда», история «до/после»; готовность — только если пользователь решил применить (П-ФК-27, П-ФК-33)
     */
    public function correct(Viewer $viewer, int $taskId, array $input): TaskReport
    {
        return DB::transaction(function () use ($viewer, $taskId, $input) {
            [$task, $facts, $report] = $this->lockReport($viewer, $taskId, (int) ($input['revision'] ?? 0));
            if (!$this->access->canCorrect($viewer, $facts, $report)) {
                throw ProductionException::forbidden('Нет права «Изменять согласованный отчёт»');
            }
            $items = $this->items($report, $input['items'] ?? [], requireQuantity: true);
            if ($problems = $this->problems((int) $task->contract_id, $items, $viewer, approving: true, exceptReportId: $report->id)) {
                throw ProductionException::invalid($problems[0], 'not_allowed');
            }

            $before = $this->snapshot($report);
            $itemsChanged = $this->replaceApproved($report, $items, declare: false, markEditor: $viewer->staffId);
            $objectBefore = $report->object_percent_approved;
            $report->fill([
                'text' => array_key_exists('text', $input) ? $this->text($input['text']) : $report->text,
                'task_percent_approved' => ObjectReadiness::parse($input['task_percent'] ?? null, 'Процент задания — число от 0 до 100'),
                'object_percent_approved' => ObjectReadiness::parse($input['object_percent'] ?? null, 'Готовность объекта — число от 0 до 100'),
            ]);
            if (!$itemsChanged && !$report->isDirty()) {
                return $report;
            }
            $report->fill(['edited_by_staff_id' => $viewer->staffId, 'edited_at' => now(), 'revision' => (int) $report->revision + 1])->save();

            if (($input['apply_object_percent'] ?? false) && $report->object_percent_approved !== null && $report->object_percent_approved !== $objectBefore) {
                $this->readiness->apply((int) $task->contract_id, $report->object_percent_approved, ContractReadinessEvent::SOURCE_REPORT_EDIT,
                    ['staff_id' => $viewer->staffId, 'report_id' => $report->id, 'task_id' => $task->id]);
            }
            $this->allocations->recompute((int) $task->contract_id);
            $this->audit($report->id, 'corrected', $viewer, $before, $this->snapshot($report->fresh()), ['apply_object_percent' => (bool) ($input['apply_object_percent'] ?? false)]);

            return $report->fresh();
        });
    }

    /** Добавить фото: автор в несогласованном, согласующий до решения, право 10 — в согласованном */
    public function addPhoto(Viewer $viewer, int $taskId, UploadedFile $file, array $input): TaskReportPhoto
    {
        $uuid = $input['client_uuid'] ?? null;
        $offline = (bool) ($input['offline'] ?? false);
        if ($uuid && ($existing = TaskReportPhoto::where('client_uuid', $uuid)->first())) {
            return $existing;
        }
        try {
            [$task, , $report] = $this->find($viewer, $taskId);
            if (!$report) {
                throw ProductionException::notFound('Отчёт не найден');
            }
            if (!$this->canChangePhotos($viewer, $task, $report)) {
                throw ProductionException::forbidden('Нет права изменять отчёт', 'report_locked');
            }
            if ($report->photos()->count() >= self::MAX_PHOTOS) {
                throw ProductionException::invalid('В отчёте не больше '.self::MAX_PHOTOS.' фото');
            }
        } catch (ProductionException $error) {
            if ($offline) {
                $this->notNotified($viewer->staffId, $task ?? TimesheetContract::find($taskId), 'Фото не принято', $error->getMessage(), $taskId);
            }
            throw $error;
        }

        $path = $file->store(self::PHOTO_DIR, 'public');
        [$width, $height] = @getimagesize($file->getRealPath()) ?: [null, null];

        return DB::transaction(function () use ($viewer, $report, $path, $width, $height, $file, $uuid) {
            $photo = TaskReportPhoto::create([
                'report_id' => $report->id,
                'path' => '/storage/'.$path,
                'width' => $width,
                'height' => $height,
                'size' => $file->getSize(),
                'added_by_staff_id' => $viewer->staffId,
                'client_uuid' => $uuid,
                'created_at' => now(),
            ]);
            $this->touchAfterPhoto($report, $viewer);
            $this->audit($report->id, 'photo_added', $viewer, null, ['photo_id' => $photo->id]);

            return $photo;
        });
    }

    public function removePhoto(Viewer $viewer, int $taskId, int $photoId): void
    {
        [$task, , $report] = $this->find($viewer, $taskId);
        $photo = $report ? TaskReportPhoto::where('report_id', $report->id)->find($photoId) : null;
        if (!$photo) {
            throw ProductionException::notFound('Фото не найдено');
        }
        if (!$this->canChangePhotos($viewer, $task, $report)) {
            throw ProductionException::forbidden('Нет права изменять отчёт', 'report_locked');
        }
        DB::transaction(function () use ($viewer, $report, $photo) {
            $photo->delete();
            $this->touchAfterPhoto($report, $viewer);
            $this->audit($report->id, 'photo_removed', $viewer, ['photo_id' => $photo->id], null);
        });
        $this->deleteFile($photo->path);
    }

    /**
     * Прямое изменение готовности (право 15) из шкалы объекта в видимом задании: только с причиной и при связи
     * (П-ФК-32 в, ИС-ФК-11)
     */
    public function changeReadiness(Viewer $viewer, int $taskId, array $input): ?float
    {
        [$task, $facts] = $this->find($viewer, $taskId);
        if (!$this->access->canEditReadiness($viewer, $facts)) {
            throw ProductionException::forbidden('Нет права «Изменять готовность объекта напрямую»');
        }
        $reason = $this->text($input['reason'] ?? null) ?? throw ProductionException::invalid('Укажите причину', 'reason_required');
        $percent = ObjectReadiness::parse($input['percent'] ?? null, 'Готовность — число от 0 до 100')
            ?? throw ProductionException::invalid('Готовность — число от 0 до 100');
        $shown = ObjectReadiness::parse($input['shown'] ?? null);

        DB::transaction(fn () => $this->readiness->apply((int) $task->contract_id, $percent, ContractReadinessEvent::SOURCE_DIRECT,
            ['staff_id' => $viewer->staffId, 'reason' => $reason, 'task_id' => $task->id], checkShown: true, shown: $shown, recordUnchanged: true));

        return $this->readiness->current((int) $task->contract_id);
    }

    /**
     * Выбор по готовности при удалении задания: предлагается, только если актуальная готовность установлена этим
     * отчётом (П-ФК-40). null — выбора нет.
     *
     * @return array{current: ?float, previous: ?float}|null
     */
    public function deleteChoice(TimesheetContract $task, ?TaskReport $report = null): ?array
    {
        $report ??= TaskReport::where('task_id', $task->id)->first();
        $event = $report ? $this->readiness->setByReport((int) $task->contract_id, $report->id) : null;

        return $event ? [
            'current' => $event->percent_after !== null ? (float) $event->percent_after : null,
            'previous' => $event->percent_before !== null ? (float) $event->percent_before : null,
        ] : null;
    }

    /**
     * Перед удалением задания (внутри транзакции): отчёт выбывает из итогов, действие — в журнале; выбор «оставить /
     * вернуть прежнюю» пишется в историю готовности (П-ФК-39, П-ФК-40). Возвращает пути фото для удаления после записи.
     *
     * @return string[]
     */
    public function beforeTaskDelete(Viewer $viewer, TimesheetContract $task, ?string $choice): array
    {
        $report = TaskReport::where('task_id', $task->id)->lockForUpdate()->first();
        if (!$report) {
            return [];
        }
        $offer = $this->deleteChoice($task, $report);
        if ($offer) {
            if (!in_array($choice, ['keep', 'restore'], true)) {
                throw ProductionException::invalid('Выберите, что сделать с готовностью объекта', 'readiness_choice');
            }
            $this->readiness->apply((int) $task->contract_id, $choice === 'restore' ? $offer['previous'] : $offer['current'], ContractReadinessEvent::SOURCE_TASK_DELETED,
                ['staff_id' => $viewer->staffId, 'report_id' => $report->id, 'task_id' => $task->id, 'details' => ['choice' => $choice]],
                recordUnchanged: true);
        }
        $paths = $report->photos()->pluck('path')->all();
        $this->audit($report->id, 'deleted', $viewer, $this->snapshot($report), null, ['task_id' => (int) $task->id, 'readiness_choice' => $offer ? $choice : null]);
        StaffNotification::query()->open()->where('task_id', $task->id)
            ->whereIn('kind', [StaffNotification::KIND_REPORT_PROVIDED, StaffNotification::KIND_REPORT_SUBMITTED])
            ->update(['withdrawn_at' => now()]);
        $report->delete();

        return $paths;
    }

    /** После удаления задания: итоги объекта без отчёта, файлы фото */
    public function afterTaskDelete(int $contractId, array $photoPaths): void
    {
        $this->allocations->recompute($contractId);
        foreach ($photoPaths as $path) {
            $this->deleteFile($path);
        }
    }

    /**
     * После изменения задания (внутри транзакции): итоги по прежнему и новому объекту и дате (П-ФК-28, П-ФК-41, П-ФК-42);
     * при смене исполнителя прежний исполнитель теряет право править отчёт (П-ЗД-33)
     */
    public function afterTaskUpdate(Viewer $viewer, TimesheetContract $task, array $before, array $after, CarbonImmutable $today, bool $executorChanged): void
    {
        $report = TaskReport::where('task_id', $task->id)->lockForUpdate()->first();
        if (!$report) {
            return;
        }
        if ($executorChanged && $report->open() && $report->author_locked_at === null
            && $this->belongedTo((int) $report->author_staff_id, $before['executor'], $today)) {
            $report->update(['author_locked_at' => now()]);
            $this->audit($report->id, 'author_locked', $viewer, ['executor' => $before['executor']], ['executor' => $after['executor']]);
        }
        $oldContract = (int) $before['object']['id'];
        $newContract = (int) $after['object']['id'];
        if ($report->status === TaskReport::APPROVED && ($oldContract !== $newContract || $before['date'] !== $after['date'])) {
            $this->allocations->recompute($newContract);
            if ($oldContract !== $newContract) {
                $this->allocations->recompute($oldContract);
            }
        }
    }

    /**
     * Смена объекта в задании: в том же окне можно изменить готовность прежнего и нового объекта, отдельное право не
     * нужно (П-ФК-41, П-ЗД-29). $input — ['old' => [percent, shown], 'new' => [percent, shown]]
     */
    public function applyObjectChangeReadiness(Viewer $viewer, TimesheetContract $task, int $oldContractId, int $newContractId, array $input): void
    {
        foreach (['old' => $oldContractId, 'new' => $newContractId] as $key => $contractId) {
            if (!isset($input[$key]) || !is_array($input[$key]) || !array_key_exists('percent', $input[$key])) {
                continue;
            }
            $percent = ObjectReadiness::parse($input[$key]['percent'], 'Готовность — число от 0 до 100');
            $shown = ObjectReadiness::parse($input[$key]['shown'] ?? null);
            if ($percent === null || $percent === $shown) {
                continue;
            }
            $this->readiness->apply($contractId, $percent, ContractReadinessEvent::SOURCE_OBJECT_CHANGE,
                ['staff_id' => $viewer->staffId, 'task_id' => $task->id, 'details' => ['from_object_id' => $oldContractId, 'to_object_id' => $newContractId]],
                checkShown: true, shown: $shown);
        }
    }

    /**
     * Проверка превышения проекта и работ вне плана по правам 11–14 (П-ФК-20, П-ФК-21, П-ПР-17); у объекта без плана
     * не проверяется (П-ФК-04). Возвращает причины отказа.
     *
     * @param array<int, ?float> $items work_type_id → количество
     * @return string[]
     */
    public function problems(int $contractId, array $items, Viewer $viewer, bool $approving, ?int $exceptReportId): array
    {
        $problems = [];
        foreach ($items as $quantity) {
            if ($quantity === null) {
                $problems[] = 'Укажите количество в каждой позиции';
                break;
            }
        }
        $plan = ContractPlanRevision::currentFor($contractId);
        if (!$plan) {
            return $problems;
        }
        $planned = $plan->items()->pluck('quantity', 'work_type_id')->map(fn ($q) => (float) $q)->all();
        $approved = $this->allocations->approvedTotals($contractId, $exceptReportId);
        $names = WorkType::withTrashed()->whereIn('id', array_keys($items))->pluck('name', 'id');

        $overage = $approving ? Right::OverageApprove : Right::OverageDeclare;
        $offPlan = $approving ? Right::OffPlanApprove : Right::OffPlanDeclare;
        foreach ($items as $typeId => $quantity) {
            if (!array_key_exists($typeId, $planned)) {
                if (!$viewer->rights->has($offPlan)) {
                    $problems[] = sprintf('«%s» нет в плане объекта. Нет права «%s»', $names[$typeId] ?? 'Вид работ', $offPlan->title());
                }
            } elseif ((float) $quantity > 0 && round(($approved[$typeId] ?? 0) + (float) $quantity, 3) > $planned[$typeId] && !$viewer->rights->has($overage)) {
                $problems[] = sprintf('«%s»: больше проекта. Нет права «%s»', $names[$typeId] ?? 'Вид работ', $overage->title());
            }
        }

        return $problems;
    }

    /** Фото меняет тот, кто вправе менять содержимое отчёта в его статусе */
    private function canChangePhotos(Viewer $viewer, TimesheetContract $task, TaskReport $report): bool
    {
        $facts = TaskFacts::fromModel($task);

        return $report->open()
            ? $this->access->canEdit($viewer, $facts, $report) || $this->access->canApprove($viewer, $facts, $report)
            : $this->access->canCorrect($viewer, $facts, $report);
    }

    private function touchAfterPhoto(TaskReport $report, Viewer $viewer): void
    {
        $report->revision = (int) $report->revision + 1;
        if ($report->status === TaskReport::APPROVED) {
            $report->edited_by_staff_id = $viewer->staffId;
            $report->edited_at = now();
        }
        $report->save();
    }

    /**
     * Задание и отчёт под блокировкой для решения: согласующий видел последнюю редакцию
     *
     * @return array{0: TimesheetContract, 1: TaskFacts, 2: TaskReport}
     */
    private function lockForDecision(Viewer $viewer, int $taskId, int $revision): array
    {
        [$task, $facts, $report] = $this->lockReport($viewer, $taskId, $revision);
        if (!$report->open()) {
            throw ProductionException::invalid('Отчёт уже согласован', 'report_approved');
        }

        return [$task, $facts, $report];
    }

    private function lockReport(Viewer $viewer, int $taskId, int $revision): array
    {
        $task = TimesheetContract::whereKey($taskId)->lockForUpdate()->first();
        if (!$task || !$this->tasks->canView($viewer, $facts = TaskFacts::fromModel($task))) {
            throw ProductionException::notFound();
        }
        $report = TaskReport::where('task_id', $task->id)->lockForUpdate()->first() ?? throw ProductionException::notFound('Отчёт не найден');
        if ($revision !== (int) $report->revision) {
            throw ProductionException::invalid('Отчёт уже изменён', 'report_changed');
        }

        return [$task, $facts, $report];
    }

    /**
     * Позиции из запроса: виды работ только из справочника ERP, без повторов (П-ФК-04); удалённый из справочника
     * остаётся, только если уже был в отчёте
     *
     * @return array<int, ?float>
     */
    private function items(TaskReport $report, array $items, bool $requireQuantity = false): array
    {
        if (count($items) > self::MAX_ITEMS) {
            throw ProductionException::invalid('Слишком много позиций');
        }
        $current = $report->items()->pluck('work_type_id')->map(fn ($id) => (int) $id)->all();
        $result = [];
        foreach ($items as $item) {
            $typeId = (int) ($item['work_type_id'] ?? 0);
            if (isset($result[$typeId])) {
                throw ProductionException::invalid('Вид работ указан дважды');
            }
            $raw = $item['quantity'] ?? null;
            $quantity = $raw === null || $raw === '' ? null : round((float) str_replace(',', '.', (string) $raw), 3);
            if ($quantity !== null && ($quantity < 0 || $quantity >= 1e11)) {
                throw ProductionException::invalid('Количество — число не меньше нуля');
            }
            if ($requireQuantity && $quantity === null) {
                throw ProductionException::invalid('Укажите количество в каждой позиции');
            }
            $result[$typeId] = $quantity;
        }
        $types = WorkType::withTrashed()->whereIn('id', array_keys($result))->get()->keyBy('id');
        foreach (array_keys($result) as $typeId) {
            $type = $types[$typeId] ?? null;
            if (!$type || ($type->trashed() && !in_array($typeId, $current, true))) {
                throw ProductionException::invalid('Вид работ не найден в справочнике');
            }
        }

        return $result;
    }

    /** Заявленные количества автора; согласованных у несогласованного отчёта нет */
    private function replaceDeclared(TaskReport $report, array $items): void
    {
        $report->items()->whereNotIn('work_type_id', array_keys($items) ?: [0])->delete();
        foreach ($items as $typeId => $quantity) {
            TaskReportItem::updateOrCreate(['report_id' => $report->id, 'work_type_id' => $typeId], ['quantity_declared' => $quantity, 'quantity_approved' => null]);
        }
    }

    /**
     * Согласованные количества. $declare — согласующий сам заявляет черновик; $markEditor — правка согласованного:
     * на изменённых позициях отметка «изменено кем, когда». Возвращает, изменились ли позиции.
     */
    private function replaceApproved(TaskReport $report, array $items, bool $declare, ?int $markEditor = null): bool
    {
        $existing = $report->items()->get()->keyBy(fn (TaskReportItem $i) => (int) $i->work_type_id);
        $changed = false;
        foreach ($existing as $typeId => $item) {
            if (!array_key_exists($typeId, $items)) {
                $item->delete();
                $changed = true;
            }
        }
        foreach ($items as $typeId => $quantity) {
            $item = $existing[$typeId] ?? new TaskReportItem(['report_id' => $report->id, 'work_type_id' => $typeId, 'quantity_declared' => null]);
            $differs = !$item->exists || $item->quantity_approved !== $quantity;
            $item->quantity_approved = $quantity;
            if ($declare) {
                $item->quantity_declared = $quantity;
            }
            if ($markEditor && $differs) {
                $item->edited_by_staff_id = $markEditor;
                $item->edited_at = now();
            }
            $changed = $changed || $differs;
            $item->save();
        }

        return $changed;
    }

    /** Что согласующий поменял в отправленном: позиции с другим количеством, добавленные и снятые */
    private function quantityChanges(TaskReport $report, array $items): array
    {
        $existing = $report->items()->with('workType')->get()->keyBy(fn (TaskReportItem $i) => (int) $i->work_type_id);
        $types = WorkType::withTrashed()->whereIn('id', array_keys($items))->get()->keyBy('id');
        $changes = [];
        foreach ($existing as $typeId => $item) {
            $approved = array_key_exists($typeId, $items) ? $items[$typeId] : null;
            if ($approved !== $item->quantity_declared) {
                $changes[] = ['work_type_id' => $typeId, 'name' => $item->workType?->name, 'unit' => $item->workType?->unit, 'declared' => $item->quantity_declared, 'approved' => $approved];
            }
        }
        foreach ($items as $typeId => $quantity) {
            if (!isset($existing[$typeId])) {
                $changes[] = ['work_type_id' => $typeId, 'name' => $types[$typeId]?->name, 'unit' => $types[$typeId]?->unit, 'declared' => null, 'approved' => $quantity];
            }
        }

        return $changes;
    }

    /** Сотрудник был исполнителем задания: сам исполнитель или член бригады-исполнителя */
    private function belongedTo(int $staffId, array $executor, CarbonImmutable $today): bool
    {
        if ($executor['type'] === 'brigade') {
            return in_array($staffId, $this->recipients->brigadeStaff((int) $executor['brigade_id'], $today), true);
        }

        return (int) $executor['staff_id'] === $staffId;
    }

    /**
     * Отчёт стал предоставленным: создателю задания — уведомление об отправке (п. 8.6); тем, кто получал бы
     * напоминание, если отчёт предоставил не один из них, — плашка «отчёт предоставлен» (П-ЗД-37)
     */
    private function afterProvided(TimesheetContract $task, TaskReport $report, Viewer $viewer, CarbonImmutable $today, bool $submitted): void
    {
        $name = $this->personName($viewer->staffId);
        // «Петров Иван Иванович отправил отчёт за 18 ноября по объекту 12 «Название».»
        $sentence = sprintf('%s отчёт %s.', $this->whoDid($viewer->staffId, $submitted ? 'отправил' : 'согласовал'), $this->about($task, $today));
        $creator = $task->creator_staff_id ? (int) $task->creator_staff_id : null;
        if ($submitted && $creator && $creator !== $viewer->staffId) {
            $this->notices->send($creator, StaffNotification::KIND_REPORT_SUBMITTED, 'Отчёт на согласование',
                $sentence, ['task_id' => (int) $task->id], push: true, taskId: (int) $task->id);
        }
        $recipients = $this->recipients->forTask($task, $today);
        if (in_array($viewer->staffId, $recipients, true)) {
            return;
        }
        foreach ($recipients as $staffId) {
            $this->notices->send($staffId, StaffNotification::KIND_REPORT_PROVIDED, 'Отчёт предоставлен',
                $sentence, ['task_id' => (int) $task->id, 'by' => ['id' => $viewer->staffId, 'name' => $name]], taskId: (int) $task->id);
        }
    }

    /**
     * Данные, пришедшие без связи, не приняты — уведомление автору до ручного закрытия (П-ФК-43, П-ФК-44):
     * «Отчёт за 18 ноября по объекту 12 «Название» не принят. Отчёт уже согласован.»
     */
    private function notNotified(int $staffId, ?TimesheetContract $task, string $title, string $verdict, string $reason, int $taskId, CarbonImmutable $today): void
    {
        $body = ($task ? 'Отчёт '.$this->about($task, $today).' '.$verdict.'. ' : '').rtrim($reason, '.').'.';
        $this->notices->send($staffId, StaffNotification::KIND_REPORT_NOT_ACCEPTED, $title, $body,
            ['task_id' => $taskId, 'reason' => $reason], push: true, taskId: $taskId);
    }

    /** «за 18 ноября по объекту 12 «Название»» */
    private function about(TimesheetContract $task, CarbonImmutable $today): string
    {
        $task->loadMissing('team');
        $object = DB::table('contracts')->where('id', $task->contract_id)->first(['object_number', 'title']);

        return NoticeTexts::about($object?->object_number, $object?->title, $task->team->day, $today);
    }

    /** «Иванов Сергей Петрович изменил», «Иванова Мария Петровна изменила» */
    private function whoDid(int $staffId, string $verb): string
    {
        $staff = Staff::query()->select(['id', 'sname', 'fname', 'mname'])->find($staffId);
        $name = $staff ? ObjectDirectory::name($staff->sname, $staff->fname, $staff->mname) : '';

        return NoticeTexts::upper(trim($name.' '.NoticeTexts::verb($verb, $staff?->mname)));
    }

    private function quantityText(?float $quantity, ?string $unit): string
    {
        return $quantity === null ? '—' : rtrim(rtrim(number_format($quantity, 3, ',', ' '), '0'), ',').($unit ? ' '.$unit : '');
    }

    /** Снимок содержимого для истории «до/после» (П-ФК-15, П-ФК-27) */
    private function snapshot(TaskReport $report, ?TaskReport $unsaved = null): array
    {
        $source = $unsaved ?? $report;

        return [
            'status' => $source->status,
            'text' => $source->text,
            'task_percent' => ['declared' => $source->task_percent_declared, 'approved' => $source->task_percent_approved],
            'object_percent' => ['declared' => $source->object_percent_declared, 'approved' => $source->object_percent_approved],
            'reject_comment' => $source->reject_comment,
            'items' => TaskReportItem::where('report_id', $report->id)->with('workType')->orderBy('id')->get()->map(fn (TaskReportItem $i) => [
                'work_type_id' => (int) $i->work_type_id,
                'name' => $i->workType?->name,
                'unit' => $i->workType?->unit,
                'declared' => $i->quantity_declared,
                'approved' => $i->quantity_approved,
            ])->all(),
        ];
    }

    private function audit(int $reportId, string $action, Viewer $viewer, ?array $before, ?array $after, array $context = []): void
    {
        AuditEntry::create([
            'entity_type' => AuditEntry::ENTITY_REPORT,
            'entity_id' => $reportId,
            'action' => $action,
            'actor_staff_id' => $viewer->staffId,
            'before' => $before,
            'after' => $after,
            'context' => $context ?: null,
        ]);
    }

    private function personName(int $staffId): string
    {
        $staff = Staff::query()->select(['id', 'sname', 'fname', 'mname'])->find($staffId);

        return $staff ? ObjectDirectory::name($staff->sname, $staff->fname, $staff->mname) : '';
    }

    private function text(?string $text): ?string
    {
        $text = $text !== null ? trim($text) : null;

        return $text === '' ? null : $text;
    }

    private function deleteFile(string $path): void
    {
        $relative = Str::after($path, '/storage/');
        if ($relative !== '' && Storage::disk('public')->exists($relative)) {
            Storage::disk('public')->delete($relative);
        }
    }
}
