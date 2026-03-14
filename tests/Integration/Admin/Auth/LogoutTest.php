<?php

declare(strict_types=1);

namespace App\Tests\Integration\Admin\Auth;

use App\Tests\TestCase\AbstractWebTestCase;

final class LogoutTest extends AbstractWebTestCase
{
    public function testLogoutWithoutAuth(): void
    {
        $this->jsonRequest('POST', '/api/admin/auth/logout/');

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testLogoutWithInvalidToken(): void
    {
        $this->authenticateClient('non-existent-session-token');

        $this->jsonRequest('POST', '/api/admin/auth/logout/');

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testLogoutRequiresAuthentication(): void
    {
        $this->jsonRequest('POST', '/api/admin/auth/logout/');

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }
}
