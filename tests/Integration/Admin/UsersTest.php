<?php

declare(strict_types=1);

namespace App\Tests\Integration\Admin;

use App\Tests\TestCase\AbstractWebTestCase;

final class UsersTest extends AbstractWebTestCase
{
    public function testListUsersWithoutAuth(): void
    {
        $this->jsonRequest('GET', '/api/admin/chat/-1001180970364/users');

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testListUsersWithInvalidToken(): void
    {
        $this->authenticateClient('invalid-session-token');

        $this->jsonRequest('GET', '/api/admin/chat/-1001180970364/users');

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testUserDetailsWithoutAuth(): void
    {
        $this->jsonRequest('GET', '/api/admin/chat/-1001180970364/users/217708876');

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testUnbanWithoutAuth(): void
    {
        $this->jsonRequest('POST', '/api/admin/chat/-1001180970364/users/217708876/unban');

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testListUsersUnauthorizedResponseIsJson(): void
    {
        $this->jsonRequest('GET', '/api/admin/chat/-1001180970364/users');

        $data = $this->getJsonResponse();
        self::assertArrayHasKey('error', $data);
    }
}
