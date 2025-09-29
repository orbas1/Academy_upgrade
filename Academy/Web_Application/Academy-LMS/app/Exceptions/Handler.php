<?php

namespace App\Exceptions;

use App\Support\Http\ApiResponseBuilder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
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
     * A list of the inputs that are never flashed to the session on validation exceptions.
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

    public function render($request, Throwable $exception)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->renderApiResponse($request, $exception);
        }

        if ($this->isHttpException($exception)) {
            if ($exception->getStatusCode() == 404) {
                return response()->view('errors.404');
            }
            if ($exception->getStatusCode() == 500) {
                return response()->view('errors.500');
            } else {
                return response()->view('errors.500');
            }
        }

        return parent::render($request, $exception);
    }

    protected function renderApiResponse($request, Throwable $exception): JsonResponse
    {
        /** @var ApiResponseBuilder $builder */
        $builder = app(ApiResponseBuilder::class);

        $exception = $this->prepareException($exception);

        if ($exception instanceof ValidationException) {
            return $builder->error(
                'Validation Failed',
                'The submitted data is invalid.',
                $exception->status,
                $exception->errors()
            );
        }

        if ($exception instanceof AuthenticationException) {
            return $builder->error(
                'Unauthenticated',
                $exception->getMessage() ?: 'Authentication is required.',
                SymfonyResponse::HTTP_UNAUTHORIZED
            );
        }

        if ($exception instanceof AuthorizationException) {
            return $builder->error(
                'Forbidden',
                $exception->getMessage() ?: 'You are not authorized to perform this action.',
                SymfonyResponse::HTTP_FORBIDDEN
            );
        }

        if ($exception instanceof ModelNotFoundException) {
            return $builder->error(
                'Not Found',
                'The requested resource could not be located.',
                SymfonyResponse::HTTP_NOT_FOUND
            );
        }

        if ($exception instanceof HttpExceptionInterface) {
            $message = $exception->getMessage() ?: SymfonyResponse::$statusTexts[$exception->getStatusCode()] ?? 'HTTP Error';

            return $builder->error(
                $message,
                $message,
                $exception->getStatusCode()
            );
        }

        $detail = config('app.debug')
            ? $exception->getMessage()
            : 'An unexpected error occurred.';

        $meta = [];

        if (config('app.debug')) {
            $meta['debug'] = [
                'exception' => get_class($exception),
            ];
        }

        return $builder->error('Server Error', $detail, SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR, [], $meta);
    }
}
