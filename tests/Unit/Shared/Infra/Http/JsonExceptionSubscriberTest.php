<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infra\Http;

use App\Shared\Infra\Http\JsonExceptionSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[CoversClass(JsonExceptionSubscriber::class)]
final class JsonExceptionSubscriberTest extends TestCase
{
    #[Test]
    public function a_generic_exception_becomes_a_500_envelope_without_leaking_details(): void
    {
        $response = $this->respond(new \RuntimeException('SQLSTATE: secret connection string'));

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());

        $body = $this->decode($response);
        self::assertIsArray($body['error']);
        self::assertSame('internal_error', $body['error']['code']);
        self::assertSame('An unexpected error occurred.', $body['error']['message']);
        self::assertNull($body['data']);
    }

    /**
     * @return iterable<string, array{\Throwable, int, string, string}>
     */
    public static function client_errors(): iterable
    {
        yield 'not found' => [new NotFoundHttpException('No route found'), 404, 'not_found', 'No route found'];
        yield 'method not allowed' => [new MethodNotAllowedHttpException(['GET'], 'Method not allowed'), 405, 'method_not_allowed', 'Method not allowed'];
        yield 'other 4xx with message' => [new HttpException(403, 'Forbidden'), 403, 'bad_request', 'Forbidden'];
        yield 'other 4xx without message' => [new HttpException(400, ''), 400, 'bad_request', 'The request could not be processed.'];
    }

    #[Test]
    #[DataProvider('client_errors')]
    public function it_maps_http_exceptions_to_the_envelope(\Throwable $throwable, int $status, string $code, string $message): void
    {
        $response = $this->respond($throwable);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame($status, $response->getStatusCode());

        $body = $this->decode($response);
        self::assertIsArray($body['error']);
        self::assertSame($code, $body['error']['code']);
        self::assertSame($message, $body['error']['message']);
        self::assertNull($body['data']);
    }

    #[Test]
    public function it_stays_out_of_the_way_in_debug(): void
    {
        self::assertNull($this->respond(new \RuntimeException('boom'), debug: true));
    }

    private function respond(\Throwable $throwable, bool $debug = false): ?Response
    {
        $kernel = new class implements HttpKernelInterface {
            public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
            {
                return new Response();
            }
        };

        $event = new ExceptionEvent($kernel, new Request(), HttpKernelInterface::MAIN_REQUEST, $throwable);
        new JsonExceptionSubscriber($debug)->onException($event);

        return $event->getResponse();
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response): array
    {
        $content = $response->getContent();
        self::assertIsString($content);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($content, true, flags: \JSON_THROW_ON_ERROR);

        return $decoded;
    }
}
