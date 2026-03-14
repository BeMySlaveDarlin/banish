<?php

declare(strict_types=1);

namespace App\Tests\Integration\Admin;

use App\Tests\TestCase\AbstractWebTestCase;

final class ChatsTest extends AbstractWebTestCase
{
    public function testListChatsWithoutAuth(): void
    {
        $this->jsonRequest('GET', '/api/admin/chats');

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testListChatsWithInvalidToken(): void
    {
        $this->authenticateClient('invalid-session-token');

        $this->jsonRequest('GET', '/api/admin/chats');

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testListChatsResponseIsJson(): void
    {
        $this->jsonRequest('GET', '/api/admin/chats');

        $data = $this->getJsonResponse();
        self::assertArrayHasKey('error', $data);
    }
}
