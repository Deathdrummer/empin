<?php

namespace App\Exceptions;

use App\Services\Locale;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
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
    public function register() {
		
		
        
       // $this->renderable(function (NotFoundHttpException $e, $request) {
       //     if ($request->is('api/*')) {
       //         return response()->json([
       //             'message' => 'Record not found.'
       //         ], 404);
       //     }
       // });
       // 
       // $this->renderable(function (ThrottleRequestsException $e, $request) {
       //     //if ($request->is('api/*')) {
       //         return response()->json([
       //             'message' => 'ThrottleRequestsException'
       //         ], 429);
       //     //}
       // });
        
        
        
        $this->reportable(function (Throwable $exception) {
			//getMessage
			//getCode
			//getFile
			//getLine
			//getTrace
			//getPrevious
			//getTraceAsString
			
			Log::error('file: '.$exception->getFile().' line: '.$exception->getLine());
			
        //
			//if ($this->isHttpException($exception) && !$request->expectsJson()) {
			//	return $details;
			//} elseif($request->expectsJson()) {
			//	$locale = new Locale('admin');
			//	$locale->set();
			//	$errData = $details->getData();
			//	$errData->status = $details->getStatusCode();
			//	$errData->message = __('errors.'.$errData->status) ?: $details->message;
			//	return response()->json($errData);
			//}
			//return $details;
        });
		
        //$this->renderable(function ($request) {
        //    logger('renderable');
        //    //return response()->view('errors.invalid-order', [], 500);
        //});
    }
    
    
   //protected function context() {
   //    return array_merge(parent::context(), [
   //        'foo' => 'bar',
   //    ]);
   //}
        
		
    
	
	public function render($request, Throwable $exception) {
        $details = parent::render($request, $exception);
        
		if ($this->isHttpException($exception) && !$request->expectsJson()) {
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
