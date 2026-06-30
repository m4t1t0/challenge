<?php

declare(strict_types=1);

namespace App\Tests\Functional\Base;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

final class ExampleTest extends WebTestCase
{
    #[Test]
    public function get_api_example_returns_success_json(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/api/example');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertJsonStringEqualsJsonString(
            json_encode(
                [
                    'status' => 'success',
                    'message' => 'Query executed successfully',
                ],
                \JSON_THROW_ON_ERROR,
            ),
            (string) $client->getResponse()->getContent(),
        );
    }
}
