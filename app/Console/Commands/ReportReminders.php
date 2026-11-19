<?php

namespace App\Console\Commands;

use App\Models\TimesheetContract;
use App\Production\Erp\ErpGateway;
use App\Production\Reports\ReportQueries;
use App\Production\Reports\ReportRecipients;
use App\Production\Support\NoticeTexts;
use App\Production\Support\OrgClock;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Напоминание об отсутствии отчёта (ТЗ П-ЗД-37, п. 8.6): push в 09:00 на следующий день после даты задания и далее
 * каждый день, пока отчёт не предоставлен, — одно на каждое задание каждому получателю
 */
class ReportReminders extends Command
{
    protected $signature = 'production:report-reminders';

    protected $description = 'Напоминания об отчётах по заданиям без предоставленного отчёта';

    public function handle(OrgClock $clock, ReportQueries $queries, ReportRecipients $recipients, PushNotificationService $push, ErpGateway $erp): int
    {
        $today = $clock->today();
        $sent = 0;
        $rights = [];
        $queries->dueQuery($today)->orderBy('timesheet_contracts.id')->chunk(200, function ($tasks) use ($today, $recipients, $push, $erp, &$sent, &$rights) {
            $objects = $erp->objects($tasks->pluck('contract_id')->map(fn ($id) => (int) $id)->unique()->values()->all());
            foreach ($tasks as $task) {
                /** @var TimesheetContract $task */
                $object = $objects[(int) $task->contract_id] ?? null;
                // «Отчёт за 18 ноября по объекту 12 «Название» не предоставлен.»
                $body = 'Отчёт '.NoticeTexts::about($object['number'] ?? null, $object['title'] ?? null, $task->team->day, $today).' не предоставлен.';
                foreach ($recipients->forTask($task, $today, $task->report, $rights) as $staffId) {
                    try {
                        $push->sendProductionNotice($staffId, 'Нет отчёта', $body, ['kind' => 'report_due', 'task_id' => (int) $task->id]);
                        $sent++;
                    } catch (Throwable $error) {
                        Log::warning('[Reports] reminder not sent: '.$error->getMessage());
                    }
                }
            }
        });
        $this->info("Напоминаний: $sent");

        return self::SUCCESS;
    }
}
