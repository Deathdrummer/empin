<?php

namespace App\Production\Support;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Отказ в действии с понятной пользователю причиной (ТЗ, п. 9.1, правило 4: «пользователь видит причину»).
 * 403 — нет права, 404 — нет такого или нет доступа (существование чужого задания не раскрывается), 422 — данные.
 */
class ProductionException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 422, public readonly string $reason = 'invalid')
    {
        parent::__construct($message);
    }

    public static function forbidden(string $message, string $reason = 'forbidden'): self
    {
        return new self($message, 403, $reason);
    }

    public static function notFound(string $message = 'Задание не найдено или нет доступа'): self
    {
        return new self($message, 404, 'not_found');
    }

    public static function invalid(string $message, string $reason = 'invalid'): self
    {
        return new self($message, 422, $reason);
    }

    /** Ожидаемый отказ, не ошибка сервера — в журнал ошибок не пишется */
    public function report(): void
    {
    }

    public function render(): JsonResponse
    {
        return response()->json(['error' => $this->getMessage(), 'reason' => $this->reason], $this->status);
    }
}
