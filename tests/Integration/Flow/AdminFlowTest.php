<?php

declare(strict_types=1);

namespace App\Tests\Integration\Flow;

use App\Domain\Admin\Entity\AdminExchangeTokenEntity;
use App\Domain\Admin\Entity\AdminSessionEntity;
use App\Tests\TestCase\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class AdminFlowTest extends AbstractWebTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();

        $container = self::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $this->em = $em;

        $conn = $this->em->getConnection();
        $conn->executeStatement('DELETE FROM admin_exchange_tokens WHERE id LIKE \'test-%\'');
        $conn->executeStatement('DELETE FROM admin_sessions WHERE id LIKE \'test-%\'');
    }

    public function testLoginViaExchangeToken(): void
    {
        $session = new AdminSessionEntity('test-session-flow-1', 217708876, 3600);
        $this->em->persist($session);

        $token = new AdminExchangeTokenEntity('test-exchange-flow-1', 217708876, $session->id);
        $this->em->persist($token);
        $this->em->flush();

        $this->jsonRequest(
            'POST',
            '/api/admin/auth/login/',
            json_encode(['token' => 'test-exchange-flow-1'], JSON_THROW_ON_ERROR),
        );

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        $data = $this->getJsonResponse();
        self::assertTrue($data['success'] ?? false);

        $cookie = $this->client->getCookieJar()->get('token');
        self::assertNotNull($cookie, 'Auth cookie should be set after login');
    }

    public function testAuthenticatedRequestSucceeds(): void
    {
        $session = new AdminSessionEntity('test-session-flow-2', 217708876, 3600);
        $this->em->persist($session);
        $this->em->flush();

        $this->client->getCookieJar()->set(
            new \Symfony\Component\BrowserKit\Cookie('token', 'test-session-flow-2')
        );

        $this->jsonRequest('GET', '/api/admin/chats');

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());
    }

    public function testExpiredSessionReturns401(): void
    {
        $session = new AdminSessionEntity('test-session-expired', 217708876, -1);
        $this->em->persist($session);
        $this->em->flush();

        $this->client->getCookieJar()->set(
            new \Symfony\Component\BrowserKit\Cookie('token', 'test-session-expired')
        );

        $this->jsonRequest('GET', '/api/admin/chats');

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testLogoutRevokesSession(): void
    {
        $session = new AdminSessionEntity('test-session-logout', 217708876, 3600);
        $this->em->persist($session);
        $this->em->flush();

        $this->client->getCookieJar()->set(
            new \Symfony\Component\BrowserKit\Cookie('token', 'test-session-logout')
        );

        $this->jsonRequest('POST', '/api/admin/auth/logout/');
        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('GET', '/api/admin/chats');
        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }
}
