<?php

declare(strict_types=1);

namespace App\Shared\Infra\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Renders uncaught exceptions in the API's `{ "error": {code, message}, "data":
 * null }` envelope, so error responses match the documented contract instead of
 * Symfony's default page. Server errors never leak internals. In debug it stays
 * out of the way so the developer error page (stack trace) still shows.
 */
final readonly class JsonExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private bool $debug,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onException'];
    }

    public function onException(ExceptionEvent $event): void
    {
        if ($this->debug) {
            return;
        }

        $throwable = $event->getThrowable();
        $status = $throwable instanceof HttpExceptionInterface
            ? $throwable->getStatusCode()
            : JsonResponse::HTTP_INTERNAL_SERVER_ERROR;

        $serverError = $status >= JsonResponse::HTTP_INTERNAL_SERVER_ERROR;
        $code = match (true) {
            $serverError => 'internal_error',
            JsonResponse::HTTP_NOT_FOUND === $status => 'not_found',
            JsonResponse::HTTP_METHOD_NOT_ALLOWED === $status => 'method_not_allowed',
            default => 'bad_request',
        };
        $message = $serverError
            ? 'An unexpected error occurred.'
            : ('' !== $throwable->getMessage() ? $throwable->getMessage() : 'The request could not be processed.');

        $event->setResponse(new JsonResponse(
            ['error' => ['code' => $code, 'message' => $message], 'data' => null],
            $status,
        ));
    }
}
