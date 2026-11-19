<?php

namespace App\Production\Attendance;

use App\Models\AttendanceDay;
use App\Models\AttendanceMark;
use App\Models\AttendanceShift;
use Illuminate\Support\Collection;

/**
 * Отметки, смены и засчитанные дни за несколько дат — одним запросом на таблицу и с поиском по ключу: календарь за
 * период — сотни заданий и тысячи отметок
 */
final class AttendanceData
{
    /** @var array<string, AttendanceMark> "staff|date" */
    private array $markByStaff = [];
    /** @var array<string, AttendanceMark[]> "brigade|date" — отметки в бригаде */
    private array $marksByBrigade = [];
    /** @var array<string, AttendanceShift> "brigade|date" */
    private array $shifts = [];
    /** @var array<string, AttendanceDay> "staff|date" */
    private array $dayByStaff = [];
    /** @var array<string, AttendanceDay[]> "brigade|date" — дни в бригаде */
    private array $daysByBrigade = [];

    /** @param string[] $dates */
    public static function forDates(array $dates): self
    {
        $data = new self();
        $dates = array_values(array_unique($dates));
        if (!$dates) {
            return $data;
        }
        foreach (AttendanceMark::whereIn('work_date', $dates)->orderBy('id')->get() as $mark) {
            $date = $mark->work_date->toDateString();
            $data->markByStaff[$mark->staff_id.'|'.$date] = $mark;
            if (!$mark->as_driver && $mark->brigade_id) {
                $data->marksByBrigade[$mark->brigade_id.'|'.$date][] = $mark;
            }
        }
        foreach (AttendanceShift::whereIn('work_date', $dates)->get() as $shift) {
            $data->shifts[$shift->brigade_id.'|'.$shift->work_date->toDateString()] = $shift;
        }
        foreach (AttendanceDay::whereIn('work_date', $dates)->orderBy('id')->get() as $day) {
            $date = $day->work_date->toDateString();
            $data->dayByStaff[$day->staff_id.'|'.$date] = $day;
            if (!$day->as_driver && $day->brigade_id) {
                $data->daysByBrigade[$day->brigade_id.'|'.$date][] = $day;
            }
        }

        return $data;
    }

    public function markOf(int $staffId, string $date): ?AttendanceMark
    {
        return $this->markByStaff[$staffId.'|'.$date] ?? null;
    }

    public function shift(int $brigadeId, string $date): ?AttendanceShift
    {
        return $this->shifts[$brigadeId.'|'.$date] ?? null;
    }

    public function dayOf(int $staffId, string $date): ?AttendanceDay
    {
        return $this->dayByStaff[$staffId.'|'.$date] ?? null;
    }

    /** @return Collection<int, AttendanceDay> засчитанные дни бригады на дату */
    public function brigadeDays(int $brigadeId, string $date): Collection
    {
        return collect($this->daysByBrigade[$brigadeId.'|'.$date] ?? []);
    }

    /** @return Collection<int, AttendanceMark> все отметившиеся в бригаде на дату, кроме пришедших после подтверждения */
    public function brigadeMarks(int $brigadeId, string $date): Collection
    {
        return collect($this->marksByBrigade[$brigadeId.'|'.$date] ?? [])->reject(fn (AttendanceMark $m) => $m->after_confirmation)->values();
    }

    /**
     * Видимый состав бригады на дату до подтверждения (П-СМ-08): без заявки — все самозаявившиеся; с заявкой — те из
     * неё, кто отметился сам. Отметки, пришедшие после подтверждения, состав не меняют (П-СМ-03а)
     *
     * @return Collection<int, AttendanceMark>
     */
    public function visibleCrew(int $brigadeId, string $date): Collection
    {
        $crew = $this->shift($brigadeId, $date)?->crew();

        return collect($this->marksByBrigade[$brigadeId.'|'.$date] ?? [])
            ->reject(fn (AttendanceMark $m) => $m->after_confirmation)
            ->filter(fn (AttendanceMark $m) => $crew === null || in_array((int) $m->staff_id, $crew, true))
            ->values();
    }
}
