<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        // API 请求统一错误处理
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($request, $exception);
        }

        return parent::render($request, $exception);
    }

    /**
     * 处理 API 异常
     */
    protected function handleApiException($request, Throwable $exception)
    {
        // 验证异常
        if ($exception instanceof ValidationException) {
            return response()->json([
                'message' => '数据验证失败',
                'errors' => $exception->errors(),
            ], 422);
        }

        // 模型未找到异常
        if ($exception instanceof ModelNotFoundException) {
            return response()->json([
                'message' => '资源不存在',
            ], 404);
        }

        // 路由未找到异常
        if ($exception instanceof NotFoundHttpException) {
            return response()->json([
                'message' => '接口不存在',
            ], 404);
        }

        // 数据库查询异常
        if ($exception instanceof QueryException) {
            \Log::error('Database query error', [
                'message' => $exception->getMessage(),
                'sql' => $exception->getSql(),
            ]);

            return response()->json([
                'message' => '数据库操作失败',
            ], 500);
        }

        // 通用异常
        $statusCode = method_exists($exception, 'getStatusCode')
            ? $exception->getStatusCode()
            : 500;

        $message = $exception->getMessage() ?: '服务器错误';

        // 生产环境不暴露详细错误信息
        if (config('app.debug')) {
            return response()->json([
                'message' => $message,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ], $statusCode);
        }

        return response()->json([
            'message' => $statusCode === 500 ? '服务器错误' : $message,
        ], $statusCode);
    }
}
