<?php

declare(strict_types=1);

namespace App\Tests\TestCase;

use App\Domain\Admin\Entity\AdminSessionEntity;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    protected function createAuthenticatedSession(int $userId = 217708876): AdminSessionEntity
    {
        return new AdminSessionEntity('test-session-token-123', $userId, 3600);
    }

    protected function authenticateClient(string $token = 'test-session-token-123'): void
    {
        $this->client->getCookieJar()->set(
            new \Symfony\Component\BrowserKit\Cookie('token', $token)
        );
    }

    /**
     * @param array<string, string> $headers
     */
    protected function jsonRequest(
        string $method,
        string $uri,
        string $body = '{}',
        array $headers = [],
    ): void {
        $serverHeaders = ['CONTENT_TYPE' => 'application/json'];
        foreach ($headers as $key => $value) {
            $serverHeaders['HTTP_' . strtoupper(str_replace('-', '_', $key))] = $value;
        }

        $this->client->request($method, $uri, [], [], $serverHeaders, $body);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getJsonResponse(): array
    {
        $content = $this->client->getResponse()->getContent();
        self::assertIsString($content);

        /** @var array<string, mixed> $data */
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }
}
