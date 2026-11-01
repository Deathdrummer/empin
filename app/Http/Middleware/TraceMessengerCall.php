<?php

namespace App\Http\Middleware;

use App\Models\MessengerCall;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Только метаданные действий звонка. Ошибка диагностики не влияет на сам звонок. */
class TraceMessengerCall
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->isMethod('POST') || !preg_match('~^api/messenger/calls/(initiate|([0-9]{1,15})/(accept|reject|cancel|end))$~', $request->path(), $match)) {
            return $next($request);
        }
        $callId = isset($match[2]) && $match[2] !== '' ? (int) $match[2] : null;
        $action = $callId ? $match[3] : 'initiate';
        $started = microtime(true);
        $logger = null;
        try {
            // Отдельный локальный файл: не используем возможные внешние каналы приложения.
            $logger = Log::build(['driver' => 'daily', 'path' => storage_path('logs/mobile-calls.log'),
                'days' => 7, 'permission' => 0600]);
        } catch (\Throwable $ignored) { }
        $accessToken = $request->user()?->currentAccessToken();
        $context = [
            'request_id' => sprintf('%.6F-%d', $started, getmypid()),
            'action' => $action, 'call_id' => $callId,
            'actor_staff_id' => $request->user()?->staff_id,
            'access_token_id' => $accessToken && method_exists($accessToken, 'getKey') ? $accessToken->getKey() : null,
            'build' => $this->numberHeader($request, 'X-Empin-Build', 99999999),
            'cause' => $this->numberHeader($request, 'X-Empin-Call-Cause', 99),
            'client_session' => $this->numberHeader($request, 'X-Empin-Call-Session', 9007199254740991),
            'platform' => in_array($request->header('X-Empin-Platform'), ['android', 'ios'], true)
                ? $request->header('X-Empin-Platform') : null,
        ];
        $before = $this->snapshot($callId);
        $this->write($logger, $context + ['phase' => 'request', 'before' => $before]);
        $status = 500;
        $exceptionClass = null;
        try {
            $response = $next($request);
            $status = $response->getStatusCode();
            if (!$callId && method_exists($response, 'getData')) {
                $data = $response->getData(true);
                $candidate = $data['call_id'] ?? null;
                if (is_int($candidate) && $candidate > 0) $context['call_id'] = $callId = $candidate;
            }
            return $response;
        } catch (\Throwable $error) {
            $status = $error instanceof ValidationException ? $error->status
                : ($error instanceof HttpExceptionInterface ? $error->getStatusCode() : 500);
            $exceptionClass = get_class($error);
            throw $error;
        } finally {
            $this->write($logger, $context + ['phase' => 'response', 'http_status' => $status,
                'elapsed_ms' => (int) round((microtime(true) - $started) * 1000),
                'before' => $before, 'after' => $this->snapshot($callId), 'exception_class' => $exceptionClass]);
        }
    }

    private function numberHeader(Request $request, string $name, int $max): ?int
    {
        $value = $request->header($name);
        return is_string($value) && preg_match('/^[0-9]{1,16}$/D', $value) && (int) $value <= $max ? (int) $value : null;
    }

    private function snapshot(?int $id): ?array
    {
        if (!$id) return null;
        try {
            $call = MessengerCall::find($id, ['id', 'caller_id', 'callee_id', 'status']);
            return $call ? ['status' => $call->status, 'caller_id' => $call->caller_id, 'callee_id' => $call->callee_id] : null;
        } catch (\Throwable $ignored) { return null; }
    }

    private function write($logger, array $context): void
    {
        try { $logger?->info('call-lifecycle', ['server_time_ms' => (int) round(microtime(true) * 1000)] + $context); }
        catch (\Throwable $ignored) { }
    }
}
