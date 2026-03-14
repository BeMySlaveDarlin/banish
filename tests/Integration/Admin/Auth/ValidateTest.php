<?php

declare(strict_types=1);

namespace App\Tests\Integration\Admin\Auth;

use App\Tests\TestCase\AbstractWebTestCase;

final class ValidateTest extends AbstractWebTestCase
{
    public function testValidateWithoutToken(): void
    {
        $this->jsonRequest('POST', '/api/admin/auth/validate/');

        self::assertSame(401, $this->client->getResponse()->getStatusCode());

        $data = $this->getJsonResponse();
        self::assertArrayHasKey('error', $data);
    }

    public function testValidateWithInvalidToken(): void
    {
        $this->authenticateClient('invalid-token-value');

        $this->jsonRequest('POST', '/api/admin/auth/validate/');

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testValidateRequiresAuthentication(): void
    {
        $this->jsonRequest('POST', '/api/admin/auth/validate/');

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }
}
