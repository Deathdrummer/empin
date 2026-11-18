<?php

namespace App\Production\Erp;

use App\Models\Erp\Brigade;
use App\Models\Erp\BrigadeMember;
use App\Models\Staff;
use App\Models\TimesheetContract;
use App\Production\Support\OrgClock;
use App\Production\Support\ProductionException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Бригады в ERP («Доработки ERP», п. 3; ТЗ П-ОБ-15…19; интервью ERP-FR-001…013, 022–024).
 *
 * Бригада постоянная, 2–5 человек, включая бригадира (П-ОБ-16): назначенный бригадир сразу вносится в состав.
 * Бригадиром можно быть только в одной бригаде, рядовым членом — в нескольких (П-ОБ-17, ERP-FR-012/013).
 * Состав хранит даты вступления и выхода (для табеля), история допускает исправления задним числом (интервью 8.19,
 * DM-280). Бригада не удаляется, а расформировывается — история сохраняется (ТЗ 10.3.2).
 * Каждое изменение — в журнале: кто из администраторов ERP, что и когда.
 */
class BrigadeRegistry
{
    public const ENTITY = 'brigade';

    public function __construct(private readonly OrgClock $clock)
    {
    }

    public function create(string $name, ?int $foremanId, ?string $since = null): Brigade
    {
        return DB::transaction(function () use ($name, $foremanId, $since) {
            $name = $this->uniqueName($name);
            $brigade = Brigade::create(['name' => $name]);
            $this->log($brigade->id, 'created', null, ['name' => $name]);
            if ($foremanId) {
                $this->setForeman($brigade, $foremanId, $since);
            }

            return $brigade->fresh();
        });
    }

    public function rename(Brigade $brigade, string $name): void
    {
        $name = $this->uniqueName($name, $brigade->id);
        if ($name === $brigade->name) {
            return;
        }
        $before = $brigade->name;
        $brigade->update(['name' => $name]);
        $this->log($brigade->id, 'renamed', ['name' => $before], ['name' => $name]);
    }

    /**
     * Назначить, сменить или снять бригадира. Бригадир — член бригады (П-ОБ-16): если его нет в составе на дату
     * назначения, он вносится с этой даты. Прежний бригадир остаётся рядовым членом.
     */
    public function setForeman(Brigade $brigade, ?int $staffId, ?string $since = null): void
    {
        $before = $brigade->foreman_staff_id ? (int) $brigade->foreman_staff_id : null;
        if ($before === $staffId) {
            return;
        }
        if ($staffId) {
            $this->assertWorking($brigade);
        }
        DB::transaction(function () use ($brigade, $staffId, $since, $before) {
            if ($staffId) {
                $this->assertStaffWorks($staffId);
                $this->releaseFromOtherBrigade($brigade, $staffId);
                $date = $this->date($since);
                if (!BrigadeMember::activeOn($date)->where('brigade_id', $brigade->id)->where('staff_id', $staffId)->exists()) {
                    $this->addMember($brigade, $staffId, $date->toDateString());
                }
            }
            $brigade->update(['foreman_staff_id' => $staffId]);
            $this->log($brigade->id, 'foreman_changed', ['foreman' => $this->person($before)], ['foreman' => $this->person($staffId)]);
        });
    }

    /** Внести сотрудника в состав с даты вступления. Одновременного повторного членства нет (ERP-FR-011) */
    public function addMember(Brigade $brigade, int $staffId, string $joinedOn, array $note = []): BrigadeMember
    {
        $this->assertWorking($brigade);
        $this->assertStaffWorks($staffId);
        $date = $this->date($joinedOn)->toDateString();
        $member = $this->saveMember(new BrigadeMember(['brigade_id' => $brigade->id, 'staff_id' => $staffId]), $date, null);
        $this->log($brigade->id, 'member_added', null, ['staff' => $this->person($staffId), 'joined_on' => $date] + $note);

        return $member;
    }

    /**
     * Исключить из состава с даты выхода (с этого дня уже не состоит). Исключение из одной бригады не затрагивает
     * другие (ERP-FR-009). Исключённый бригадир перестаёт быть бригадиром — назначается новый
     */
    public function excludeMember(BrigadeMember $member, string $leftOn, array $note = []): void
    {
        if ($member->left_on) {
            throw ProductionException::invalid('Сотрудник уже исключён из бригады');
        }
        $this->correctMember($member, $member->joined_on->toDateString(), $leftOn, $note);
    }

    /**
     * Исправить даты вступления и выхода (в том числе задним числом: история членства допускает исправления —
     * интервью 8.19, DM-280). Пустая дата выхода — сотрудник снова состоит в бригаде.
     */
    public function correctMember(BrigadeMember $member, string $joinedOn, ?string $leftOn, array $note = []): void
    {
        $brigade = $member->brigade;
        $joined = $this->date($joinedOn)->toDateString();
        $left = $leftOn ? $this->date($leftOn)->toDateString() : null;
        $before = ['joined_on' => $member->joined_on->toDateString(), 'left_on' => $member->left_on?->toDateString()];
        if ($before === ['joined_on' => $joined, 'left_on' => $left]) {
            return;
        }
        if ($left === null && $before['left_on'] !== null) {
            // Вернуть в состав можно только работающего сотрудника
            $this->assertStaffWorks((int) $member->staff_id);
        }
        DB::transaction(function () use ($member, $brigade, $joined, $left, $before, $note) {
            $this->saveMember($member, $joined, $left);
            $staff = $this->person((int) $member->staff_id);
            $action = $before['left_on'] === null && $left !== null && $before['joined_on'] === $joined ? 'member_excluded' : 'member_corrected';
            $this->log($brigade->id, $action, $action === 'member_excluded' ? null : ['staff' => $staff] + $before, ['staff' => $staff, 'joined_on' => $joined, 'left_on' => $left] + $note);
            $this->dropForemanWithoutMembership($brigade, (int) $member->staff_id);
        });
    }

    /** Удалить ошибочно внесённую запись состава (запись в журнале остаётся) */
    public function removeMember(BrigadeMember $member): void
    {
        $brigade = $member->brigade;
        DB::transaction(function () use ($member, $brigade) {
            $before = ['staff' => $this->person((int) $member->staff_id), 'joined_on' => $member->joined_on->toDateString(), 'left_on' => $member->left_on?->toDateString()];
            $member->delete();
            $this->log($brigade->id, 'member_removed', $before, null);
            $this->dropForemanWithoutMembership($brigade, (int) $member->staff_id);
        });
    }

    /**
     * Сотрудник уволен в ERP («Работает» выключено или запись удалена): даты увольнения в ERP нет, поэтому днём
     * увольнения считается сегодня — последний день в составе, выход с завтра. Вступление с будущей даты отменяется,
     * бригадирство снимается. Повторное включение «Работает» состав не возвращает — его восстанавливает администратор.
     * Задания, выходы и история остаются (ТЗ 10.3.2).
     *
     * @return int сколько строк состава изменено
     */
    public function releaseStaff(int $staffId): int
    {
        $leftOn = $this->clock->today()->addDay()->toDateString();
        $members = BrigadeMember::with('brigade')->where('staff_id', $staffId)
            ->where(fn ($q) => $q->whereNull('left_on')->orWhere('left_on', '>', $leftOn))
            ->get();

        foreach ($members as $member) {
            if ($member->joined_on->toDateString() >= $leftOn) {
                DB::transaction(function () use ($member) {
                    $before = ['staff' => $this->person((int) $member->staff_id), 'joined_on' => $member->joined_on->toDateString(), 'left_on' => $member->left_on?->toDateString()];
                    $member->delete();
                    $this->log((int) $member->brigade_id, 'member_removed', $before, ['fired' => true]);
                    $this->dropForemanWithoutMembership($member->brigade, (int) $member->staff_id);
                });
                continue;
            }
            $this->correctMember($member, $member->joined_on->toDateString(), $leftOn, ['fired' => true]);
        }

        return $members->count();
    }

    /** Перевести в другую бригаду: выход из этой и вступление в ту же дату (ERP-FR-004) */
    public function transfer(BrigadeMember $member, Brigade $target, string $date): void
    {
        if ((int) $target->id === (int) $member->brigade_id) {
            throw ProductionException::invalid('Выберите другую бригаду');
        }
        DB::transaction(function () use ($member, $target, $date) {
            $source = $member->brigade;
            $this->excludeMember($member, $date, ['transferred_to' => ['id' => $target->id, 'name' => $target->name]]);
            $this->addMember($target, (int) $member->staff_id, $date, ['transferred_from' => ['id' => $source->id, 'name' => $source->name]]);
        });
    }

    /**
     * Расформировать с даты: с этого дня бригада не работает, новые задания ей не ставятся; бригада, состав и история
     * остаются (ТЗ 10.3.2). Приложение уже не учитывает бригаду с этой даты (Brigade::scopeWorkingOn)
     */
    public function disband(Brigade $brigade, string $date): void
    {
        $this->assertWorking($brigade);
        $day = $this->date($date)->toDateString();
        $brigade->update(['disbanded_on' => $day]);
        $this->log($brigade->id, 'disbanded', null, ['disbanded_on' => $day]);
    }

    /**
     * Задания бригады на дату расформирования и позже: сами не переносятся (исполнителя меняет создатель задания,
     * П-ЗД-10), поэтому ERP предупреждает администратора
     */
    public function tasksFrom(Brigade $brigade, string $date): int
    {
        return TimesheetContract::whereHas('team', fn ($q) => $q->where('brigade_id', $brigade->id)->where('day', '>=', $this->date($date)->toDateString()))->count();
    }

    /** Отменить ошибочное расформирование */
    public function restore(Brigade $brigade): void
    {
        if (!$brigade->disbanded_on) {
            return;
        }
        $before = $brigade->disbanded_on->toDateString();
        $brigade->update(['disbanded_on' => null]);
        $this->log($brigade->id, 'restored', ['disbanded_on' => $before], null);
    }

    /** История изменений бригады для ERP: новые сверху */
    public function history(Brigade $brigade): array
    {
        return ErpAudit::history(self::ENTITY, $brigade->id);
    }

    /** Даты членства с проверками модели (выход позже вступления, без пересечений) и расформирования */
    private function saveMember(BrigadeMember $member, string $joined, ?string $left): BrigadeMember
    {
        $brigade = $member->brigade ?? Brigade::findOrFail($member->brigade_id);
        if ($brigade->disbanded_on && $joined >= $brigade->disbanded_on->toDateString()) {
            throw ProductionException::invalid('Дата вступления не раньше даты расформирования бригады');
        }
        $member->joined_on = $joined;
        $member->left_on = $left;
        try {
            $member->save();
        } catch (InvalidArgumentException $e) {
            throw ProductionException::invalid($e->getMessage());
        }

        return $member;
    }

    /**
     * Бригадир — только в одной бригаде (ERP-FR-012, уникальность в базе). Бригадира уже расформированной бригады
     * снимаем там сами — он там больше не работает; действующую бригаду администратор меняет сам
     */
    private function releaseFromOtherBrigade(Brigade $brigade, int $staffId): void
    {
        $other = Brigade::where('foreman_staff_id', $staffId)->whereKeyNot($brigade->id)->first();
        if (!$other) {
            return;
        }
        if ($other->disbanded_on && $other->disbanded_on->toDateString() <= $this->clock->today()->toDateString()) {
            $other->update(['foreman_staff_id' => null]);
            $this->log($other->id, 'foreman_changed', ['foreman' => $this->person($staffId)], ['foreman' => null]);

            return;
        }
        throw ProductionException::invalid("Сотрудник уже бригадир бригады «{$other->name}»");
    }

    /**
     * Бригадир без открытой строки состава (исключён, в том числе с будущей даты, или запись удалена) перестаёт быть
     * бригадиром сразу — администратор назначает нового
     */
    private function dropForemanWithoutMembership(Brigade $brigade, int $staffId): void
    {
        $brigade->refresh();
        if ((int) $brigade->foreman_staff_id !== $staffId) {
            return;
        }
        if (BrigadeMember::where('brigade_id', $brigade->id)->where('staff_id', $staffId)->whereNull('left_on')->exists()) {
            return;
        }
        $brigade->update(['foreman_staff_id' => null]);
        $this->log($brigade->id, 'foreman_changed', ['foreman' => $this->person($staffId)], ['foreman' => null]);
    }

    private function log(int $brigadeId, string $action, ?array $before, ?array $after): void
    {
        ErpAudit::write(self::ENTITY, $brigadeId, $action, $before, $after);
    }

    private function person(?int $staffId): ?array
    {
        return ErpAudit::person($staffId);
    }

    private function assertWorking(Brigade $brigade): void
    {
        if ($brigade->disbanded_on) {
            throw ProductionException::invalid('Бригада расформирована');
        }
    }

    private function assertStaffWorks(int $staffId): void
    {
        if (!Staff::whereKey($staffId)->where('working', 1)->exists()) {
            throw ProductionException::invalid('Сотрудник не найден или не работает');
        }
    }

    /** Название без лишних пробелов; среди действующих бригад не повторяется — в приложении их выбирают по названию */
    private function uniqueName(string $name, ?int $exceptId = null): string
    {
        $name = mb_substr(trim(preg_replace('/\s+/u', ' ', $name)), 0, 255);
        if ($name === '') {
            throw ProductionException::invalid('Укажите название бригады');
        }
        $taken = Brigade::whereNull('disbanded_on')->where('name', $name)->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))->exists();
        if ($taken) {
            throw ProductionException::invalid("Бригада «{$name}» уже есть");
        }

        return $name;
    }

    private function date(?string $value): CarbonImmutable
    {
        if (!$value) {
            return $this->clock->today();
        }
        try {
            return $this->clock->dateOf($value);
        } catch (\Throwable) {
            throw ProductionException::invalid('Неверная дата');
        }
    }
}
