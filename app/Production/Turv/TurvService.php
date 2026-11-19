<?php

namespace App\Production\Turv;

use App\Models\AttendanceDay;
use App\Models\AttendanceMark;
use App\Models\Erp\Brigade;
use App\Models\Erp\BrigadeMember;
use App\Models\Erp\Driver;
use App\Models\Staff;
use App\Models\TurvOrder;
use App\Production\Attendance\AttendanceService;
use App\Production\Objects\ObjectDirectory;
use App\Production\Rights\Grant;
use App\Production\Rights\Right;
use App\Production\Support\OrgClock;
use App\Production\Support\ProductionException;
use App\Production\Tasks\Viewer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * ТУРВ (ТЗ, раздел 7; П-СМ-25…36; П-ПР-20…23): месяц по подтверждённым и исправленным выходам, разделы бригад,
 * «Водители» и персональный, доступ по членству и праву 19, корректировка по праву 18, порядок за учётной записью.
 * Ячейка: R — выход, пусто — нет, «-» — дата недоступна по правам, «?» — неподтверждённая отметка (только для права 18)
 */
final class TurvService
{
    public const CELL_DAY = 'R';
    public const CELL_NONE = '';
    public const CELL_LOCKED = '-';
    public const CELL_MARK = '?';

    /** @var array<int, string> ФИО */
    private array $names = [];

    public function __construct(private readonly OrgClock $clock, private readonly AttendanceService $attendance)
    {
    }

    /**
     * @param array{brigade_ids?: int[], rows?: array<string, int[]>}|null $order порядок с экрана (экспорт — как видно, П-СМ-35)
     */
    public function month(Viewer $viewer, string $month, ?array $order = null): array
    {
        $first = CarbonImmutable::createFromFormat('!Y-m', $month, $this->clock->timezone())->startOfMonth();
        $dates = [];
        for ($d = $first; $d->month === $first->month; $d = $d->addDay()) {
            $dates[] = $d->toDateString();
        }
        $today = $this->clock->today()->toDateString();
        $view = $viewer->rights->grantsOf(Right::TimesheetView);
        $correct = $viewer->rights->grantsOf(Right::AttendanceCorrect);

        $days = AttendanceDay::whereBetween('work_date', [$dates[0], end($dates)])->get();
        $dayAt = $days->keyBy(fn (AttendanceDay $d) => $d->staff_id.'|'.$d->work_date->toDateString());
        // «?» — неподтверждённые самоотметки — видит только владелец права 18 (П-СМ-15б)
        $markAt = $correct->isNotEmpty()
            ? AttendanceMark::whereBetween('work_date', [$dates[0], end($dates)])->get()->keyBy(fn (AttendanceMark $m) => $m->staff_id.'|'.$m->work_date->toDateString())
            : collect();
        $correctDate = fn (string $date, ?int $brigadeId) => $date <= $today
            && $correct->contains(fn (Grant $g) => $g->allowsDate(CarbonImmutable::parse($date), CarbonImmutable::parse($today)) && ($brigadeId === null || $g->coversBrigade($brigadeId)));

        $sections = [];
        // ФИО всех, кто может попасть в строки, — одним запросом (при праве «вся компания» это сотни сотрудников)
        $this->preloadNames($days->pluck('staff_id')->all());

        // ---- Бригады: базовый доступ по членству (с 1-го числа месяца вступления до конца месяца выхода) и право 19 ----
        $base = $this->baseRanges($viewer->staffId);
        $brigadeGrants = $view->filter(fn (Grant $g) => $g->allBrigades || $g->brigadeIds);
        $brigadeIds = collect(array_keys($base))
            ->merge($brigadeGrants->contains(fn (Grant $g) => $g->allBrigades)
                ? Brigade::where(fn ($q) => $q->whereNull('disbanded_on')->orWhere('disbanded_on', '>', $dates[0]))->pluck('id')
                : $brigadeGrants->flatMap(fn (Grant $g) => $g->brigadeIds))
            ->map(fn ($id) => (int) $id)->unique()->values();
        $brigades = Brigade::whereIn('id', $brigadeIds)->get()->keyBy('id');
        $memberships = BrigadeMember::whereIn('brigade_id', $brigadeIds)->get()->groupBy('brigade_id');
        $this->preloadNames($memberships->flatten()->pluck('staff_id')->all());
        foreach ($brigades as $brigade) {
            $id = (int) $brigade->id;
            $allowed = array_values(array_filter($dates, fn (string $date) => $this->inRanges($base[$id] ?? [], $date)
                || $brigadeGrants->contains(fn (Grant $g) => ($g->allBrigades || in_array($id, $g->brigadeIds, true)) && $g->coversViewDate(CarbonImmutable::parse($date)))));
            if (!$allowed) {
                continue; // нет ни одной разрешённой даты — раздела нет (П-СМ-32)
            }
            // Строки: состоявшие в бригаде в разрешённые даты и привлечённые с днями в этой бригаде (П-СМ-31)
            $staff = collect($memberships->get($id, []))
                ->filter(fn (BrigadeMember $m) => collect($allowed)->contains(fn ($date) => $m->joined_on->toDateString() <= $date && (!$m->left_on || $m->left_on->toDateString() > $date)))
                ->pluck('staff_id')
                ->merge($days->filter(fn (AttendanceDay $d) => !$d->as_driver && (int) $d->brigade_id === $id && in_array($d->work_date->toDateString(), $allowed, true))->pluck('staff_id'))
                ->map(fn ($s) => (int) $s)->unique()->values()->all();
            $correctDates = array_values(array_filter($allowed, fn ($date) => $correctDate($date, $id)));
            $rows = array_map(fn (int $staffId) => $this->row($staffId, $dates, $allowed, function (string $date) use ($staffId, $id, $dayAt, $markAt, $correctDates) {
                $day = $dayAt->get($staffId.'|'.$date);
                if ($day && !$day->as_driver && (int) $day->brigade_id === $id) {
                    return self::CELL_DAY;
                }
                $mark = $markAt->get($staffId.'|'.$date);

                return !$day && $mark && !$mark->as_driver && (int) $mark->brigade_id === $id && in_array($date, $correctDates, true) ? self::CELL_MARK : self::CELL_NONE;
            }), $staff);
            // По умолчанию первым — текущий бригадир, остальные по алфавиту (П-СМ-33, инт. 8.49)
            usort($rows, fn ($a, $b) => [(int) ($a['staff_id'] !== (int) $brigade->foreman_staff_id), $a['name']] <=> [(int) ($b['staff_id'] !== (int) $brigade->foreman_staff_id), $b['name']]);
            $sections[] = [
                'key' => 'b'.$id,
                'type' => 'brigade',
                'brigade_id' => $id,
                'title' => $brigade->name,
                'rows' => $rows,
                'correct_dates' => $correctDates,
                // «Без ограничений по бригадам» — выход можно поставить любому сотруднику ERP (п. 7.2, право 18)
                'can_add_staff' => $correctDates && $correct->contains(fn (Grant $g) => $g->scopeAll || $g->allBrigades),
            ];
        }
        $sections = $this->sortSections($sections, $order['brigade_ids'] ?? $this->saved($viewer)?->brigade_ids ?? []);

        // ---- Водители: по праву 19 на этих людей или на компанию; свою строку водитель видит без права (П-СМ-30а) ----
        $driverStaff = Driver::query()->pluck('staff_id')
            ->merge($days->filter(fn (AttendanceDay $d) => $d->as_driver)->pluck('staff_id'))
            ->map(fn ($s) => (int) $s)->unique()->values();
        $driverRows = [];
        $this->preloadNames($driverStaff->all());
        foreach ($driverStaff as $staffId) {
            $allowed = array_values(array_filter($dates, fn (string $date) => $staffId === $viewer->staffId || $this->personCovered($view, $staffId, $date)));
            if (!$allowed) {
                continue;
            }
            $driverRows[] = $this->row($staffId, $dates, $allowed, function (string $date) use ($staffId, $dayAt, $markAt, $correctDate) {
                $day = $dayAt->get($staffId.'|'.$date);
                if ($day && $day->as_driver) {
                    return self::CELL_DAY;
                }
                $mark = $markAt->get($staffId.'|'.$date);

                return !$day && $mark && $mark->as_driver && $correctDate($date, null) ? self::CELL_MARK : self::CELL_NONE;
            });
        }
        if ($driverRows) {
            usort($driverRows, fn ($a, $b) => $a['name'] <=> $b['name']);
            // К разделу «Водители» перечень бригад права 18 не применяется — только глубина (П-ПР-19а)
            $sections[] = [
                'key' => 'drivers', 'type' => 'drivers', 'title' => 'Водители', 'rows' => $driverRows,
                'correct_dates' => array_values(array_filter($dates, fn ($date) => $correctDate($date, null))),
                'can_add_staff' => false,
            ];
        }

        // ---- Персональный раздел: все дни сотрудника в любых бригадах и водителем (П-СМ-30) ----
        $people = $view->flatMap(fn (Grant $g) => $g->staffIds);
        if ($view->contains(fn (Grant $g) => $g->scopeAll)) {
            $people = $people->merge(Staff::where('working', 1)->pluck('id'))->merge($days->pluck('staff_id'));
        }
        $personalRows = [];
        $people = $people->map(fn ($s) => (int) $s)->unique()->values();
        $this->preloadNames($people->all());
        foreach ($people as $staffId) {
            $allowed = array_values(array_filter($dates, fn (string $date) => $this->personCovered($view, $staffId, $date)));
            if ($allowed) {
                $personalRows[] = $this->row($staffId, $dates, $allowed, fn (string $date) => $dayAt->has($staffId.'|'.$date) ? self::CELL_DAY : self::CELL_NONE);
            }
        }
        if ($personalRows) {
            usort($personalRows, fn ($a, $b) => $a['name'] <=> $b['name']);
            $sections[] = ['key' => 'personal', 'type' => 'personal', 'title' => 'Персонально', 'rows' => $personalRows, 'correct_dates' => [], 'can_add_staff' => false];
        }

        $rowsOrder = $order['rows'] ?? $this->saved($viewer)?->rows ?? [];
        foreach ($sections as &$section) {
            $section['rows'] = $this->sortRows($section['rows'], $rowsOrder[$section['key']] ?? []);
            $section['empty'] = $section['rows'] === [];
        }
        unset($section);

        return [
            'month' => $first->format('Y-m'),
            'today' => $today,
            'dates' => array_map(fn (string $date) => ['date' => $date, 'day' => (int) substr($date, 8, 2), 'weekday' => CarbonImmutable::parse($date)->isoWeekday()], $dates),
            'sections' => $sections,
            'can_correct' => $correct->isNotEmpty(),
        ];
    }

    /**
     * Корректировка ячейки (право 18; П-СМ-15, П-ПР-19а): только в видимом разделе, в пределах глубины и бригад права,
     * на сегодня и прошлые даты. Без ограничений по бригадам — любому сотруднику ERP
     */
    public function correct(Viewer $viewer, string $kind, ?int $brigadeId, int $staffId, string $date, bool $present, bool $notify): array
    {
        $today = $this->clock->today()->toDateString();
        if ($date > $today) {
            throw ProductionException::invalid('Исправить можно только сегодня и прошлые даты', 'future_date');
        }
        if (!$viewer->rights->has(Right::AttendanceCorrect)) {
            throw ProductionException::forbidden('Нет права «Корректировать выходы»');
        }
        $table = $this->month($viewer, substr($date, 0, 7));
        $key = $kind === 'driver' ? 'drivers' : 'b'.$brigadeId;
        $section = collect($table['sections'])->firstWhere('key', $key);
        if (!$section || !in_array($date, $section['correct_dates'], true)) {
            throw ProductionException::forbidden('Нет права исправлять выход за эту дату');
        }
        $row = collect($section['rows'])->firstWhere('staff_id', $staffId);
        $index = array_search($date, array_column($table['dates'], 'date'), true);
        if ($row && $row['cells'][$index] === self::CELL_LOCKED) {
            throw ProductionException::forbidden('Нет права исправлять выход за эту дату');
        }
        if (!$row && !$section['can_add_staff']) {
            throw ProductionException::invalid('Сотрудника нет в разделе');
        }
        if (!$row && !Staff::whereKey($staffId)->exists()) {
            throw ProductionException::invalid('Сотрудник не найден');
        }
        $this->attendance->correct($viewer, $staffId, CarbonImmutable::parse($date, $this->clock->timezone()), $kind === 'driver' ? null : $brigadeId, $present, $notify);

        return $this->month($viewer, substr($date, 0, 7));
    }

    /** Порядок бригад и строк — за учётной записью, для всех месяцев; сохраняется после каждой перестановки (инт. 8.53) */
    public function saveOrder(Viewer $viewer, array $brigadeIds, array $rows): void
    {
        TurvOrder::updateOrCreate(['staff_id' => $viewer->staffId], [
            'brigade_ids' => array_values(array_map('intval', $brigadeIds)),
            'rows' => collect($rows)->map(fn ($ids) => array_values(array_map('intval', (array) $ids)))->all(),
        ]);
    }

    // ---- Внутреннее ----

    /** @return array<int, array<int, array{0: string, 1: ?string}>> brigade_id => [с, по] */
    private function baseRanges(int $staffId): array
    {
        $ranges = [];
        foreach (BrigadeMember::where('staff_id', $staffId)->get() as $m) {
            $ranges[(int) $m->brigade_id][] = [
                $m->joined_on->copy()->startOfMonth()->toDateString(),
                $m->left_on ? $m->left_on->copy()->subDay()->endOfMonth()->toDateString() : null,
            ];
        }

        return $ranges;
    }

    private function inRanges(array $ranges, string $date): bool
    {
        foreach ($ranges as [$from, $to]) {
            if ($date >= $from && ($to === null || $date <= $to)) {
                return true;
            }
        }

        return false;
    }

    /** Право 19 по человеку или по всей компании на дату (П-ПР-20) */
    private function personCovered(Collection $view, int $staffId, string $date): bool
    {
        return $view->contains(fn (Grant $g) => ($g->scopeAll || in_array($staffId, $g->staffIds, true)) && $g->coversViewDate(CarbonImmutable::parse($date)));
    }

    private function row(int $staffId, array $dates, array $allowed, callable $cell): array
    {
        $cells = array_map(fn (string $date) => in_array($date, $allowed, true) ? $cell($date) : self::CELL_LOCKED, $dates);

        return [
            'staff_id' => $staffId,
            'name' => $this->name($staffId),
            'cells' => $cells,
            'total' => count(array_filter($cells, fn ($c) => $c === self::CELL_DAY)),
        ];
    }

    /** Сохранённый порядок бригад, новые — в конец по названию (инт. 8.33) */
    private function sortSections(array $sections, array $saved): array
    {
        $position = array_flip(array_map('intval', $saved));
        usort($sections, fn ($a, $b) => [$position[$a['brigade_id']] ?? PHP_INT_MAX, $a['title']] <=> [$position[$b['brigade_id']] ?? PHP_INT_MAX, $b['title']]);

        return $sections;
    }

    /** Сохранённый порядок строк раздела; новые — в конец, в порядке по умолчанию (инт. 8.34, 8.50) */
    private function sortRows(array $rows, array $saved): array
    {
        $position = array_flip(array_map('intval', $saved));
        $default = array_flip(array_column($rows, 'staff_id'));
        usort($rows, fn ($a, $b) => [$position[$a['staff_id']] ?? PHP_INT_MAX, $default[$a['staff_id']]] <=> [$position[$b['staff_id']] ?? PHP_INT_MAX, $default[$b['staff_id']]]);

        return $rows;
    }

    private function saved(Viewer $viewer): ?TurvOrder
    {
        return TurvOrder::where('staff_id', $viewer->staffId)->first();
    }

    /** @param int[] $staffIds */
    private function preloadNames(array $staffIds): void
    {
        $missing = array_diff(array_unique(array_map('intval', $staffIds)), array_keys($this->names));
        foreach (array_chunk($missing, 500) as $chunk) {
            foreach (Staff::query()->whereIn('id', $chunk)->get(['id', 'sname', 'fname', 'mname']) as $staff) {
                $this->names[(int) $staff->id] = ObjectDirectory::name($staff->sname, $staff->fname, $staff->mname);
            }
        }
    }

    private function name(int $staffId): ?string
    {
        if (!array_key_exists($staffId, $this->names)) {
            $staff = Staff::query()->select(['id', 'sname', 'fname', 'mname'])->find($staffId);
            $this->names[$staffId] = $staff ? ObjectDirectory::name($staff->sname, $staff->fname, $staff->mname) : null;
        }

        return $this->names[$staffId];
    }
}
