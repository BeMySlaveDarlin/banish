<?php

declare(strict_types=1);

namespace App\Tests\Integration\Admin;

use App\Tests\TestCase\AbstractWebTestCase;

final class AuditLogTest extends AbstractWebTestCase
{
    public function testChatLogsWithoutAuth(): void
    {
        $this->jsonRequest('GET', '/api/admin/chat/-1001180970364/audit-logs');

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testChatLogsWithInvalidToken(): void
    {
        $this->authenticateClient('invalid-session-token');

        $this->jsonRequest('GET', '/api/admin/chat/-1001180970364/audit-logs');

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testUserLogsWithoutAuth(): void
    {
        $this->jsonRequest('GET', '/api/admin/user/217708876/audit-logs');

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testChatLogsUnauthorizedResponseIsJson(): void
    {
        $this->jsonRequest('GET', '/api/admin/chat/-1001180970364/audit-logs');

        $data = $this->getJsonResponse();
        self::assertArrayHasKey('error', $data);
    }
}
