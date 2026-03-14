<?php

declare(strict_types=1);

namespace App\Tests\Integration\Admin\Auth;

use App\Tests\TestCase\AbstractWebTestCase;

final class LoginTest extends AbstractWebTestCase
{
    public function testLoginWithInvalidToken(): void
    {
        $this->jsonRequest(
            'POST',
            '/api/admin/auth/login/',
            json_encode(['token' => 'non-existent-token'], JSON_THROW_ON_ERROR),
        );

        $response = $this->client->getResponse();
        self::assertSame(401, $response->getStatusCode());

        $data = $this->getJsonResponse();
        self::assertArrayHasKey('error', $data);
        self::assertSame('Invalid or expired token', $data['error']);
    }

    public function testLoginWithEmptyTokenDoesNotSucceed(): void
    {
        $this->jsonRequest(
            'POST',
            '/api/admin/auth/login/',
            json_encode(['token' => ''], JSON_THROW_ON_ERROR),
        );

        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringNotContainsString('"success":true', $content);
    }

    public function testLoginWithMissingTokenFieldDoesNotSucceed(): void
    {
        $this->jsonRequest(
            'POST',
            '/api/admin/auth/login/',
            json_encode(new \stdClass(), JSON_THROW_ON_ERROR),
        );

        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringNotContainsString('"success":true', $content);
    }

    public function testLoginEndpointIsPublicAccess(): void
    {
        $this->jsonRequest(
            'POST',
            '/api/admin/auth/login/',
            json_encode(['token' => 'test-token'], JSON_THROW_ON_ERROR),
        );

        $response = $this->client->getResponse();
        self::assertNotSame(403, $response->getStatusCode());
    }

    public function testLoginReturnsErrorForUnknownToken(): void
    {
        $this->jsonRequest(
            'POST',
            '/api/admin/auth/login/',
            json_encode(['token' => 'unknown-token-value'], JSON_THROW_ON_ERROR),
        );

        $data = $this->getJsonResponse();
        self::assertArrayHasKey('error', $data);
    }
}
