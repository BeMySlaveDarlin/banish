<?php

declare(strict_types=1);

namespace App\Tests\Integration\Admin;

use App\Tests\TestCase\AbstractWebTestCase;

final class ConfigTest extends AbstractWebTestCase
{
    public function testGetConfigWithoutAuth(): void
    {
        $this->jsonRequest('GET', '/api/admin/chat/-1001180970364/config');

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testGetConfigWithInvalidToken(): void
    {
        $this->authenticateClient('invalid-session-token');

        $this->jsonRequest('GET', '/api/admin/chat/-1001180970364/config');

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testUpdateConfigWithoutAuth(): void
    {
        $this->jsonRequest(
            'POST',
            '/api/admin/chat/-1001180970364/config',
            json_encode(['votesRequired' => 5], JSON_THROW_ON_ERROR),
        );

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testGetConfigUnauthorizedResponseIsJson(): void
    {
        $this->jsonRequest('GET', '/api/admin/chat/-1001180970364/config');

        $data = $this->getJsonResponse();
        self::assertArrayHasKey('error', $data);
    }
}
