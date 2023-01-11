<?php namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Session\TokenMismatchException;
use App\Services\Locale;
use Throwable;

class Handler extends ExceptionHandler {
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
	protected $dontReport = [
		TokenMismatchException::class,
	];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        /* $this->reportable(function (Throwable $e) {
            Log::error('['.$e->getCode().'] "'.$e->getMessage().'" on line '.$e->getLine().' of file '.$e->getFile());
        }); */
    }
	
	
	
	/**
     * Логирование ошибок
     * Throwable $e
     */
	public function report(Throwable $e) {
		$code = $e->getCode();
		$message = $e->getMessage();
		$line = $e->getLine();
		$file = $e->getFile();
		
		Log::error("[{$code}] \"{$message}\" of file: {$file}:{$line} on line: {$line}");
		
		//parent::report($e);
	}
	
	
	/**
     * Отрисовка ошибок в соответствии с типом запроса
	 * $request
     * Throwable $e
     */
	public function render($request, Throwable $e) {
		$details = parent::render($request, $e);
		if ($this->isHttpException($e) && !$request->expectsJson()) {
			return $details;
		} elseif($request->expectsJson()) {
			$locale = new Locale('admin');
			$locale->set();
			$errData = $details->getData();
			$errData->status = $details->getStatusCode();
            $errData->message = __('errors.'.$errData->status) ?: $details->message;
			return response()->json($errData);
		}
		return $details;
		// !env('APP_DEBUG', false)
    }
	
}