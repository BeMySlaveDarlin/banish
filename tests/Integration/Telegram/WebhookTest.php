<?php

declare(strict_types=1);

namespace App\Tests\Integration\Telegram;

use App\Tests\TestCase\AbstractWebTestCase;

final class WebhookTest extends AbstractWebTestCase
{
    private string $webhookSecret;

    protected function setUp(): void
    {
        parent::setUp();

        $container = self::getContainer();
        /** @var string $secret */
        $secret = $container->getParameter('app.secret');
        $this->webhookSecret = $secret;
    }

    public function testWebhookWithTextMessage(): void
    {
        $payload = json_encode([
            'update_id' => 835874835,
            'message' => [
                'message_id' => 494361,
                'from' => [
                    'id' => 331523702,
                    'is_bot' => false,
                    'first_name' => 'Vitaly',
                    'last_name' => 'Yakubenko',
                    'username' => 'ohuennaya_shutka',
                ],
                'chat' => [
                    'id' => -1001180970364,
                    'title' => 'Test Chat',
                    'type' => 'supergroup',
                    'username' => 'phpyhtelka',
                ],
                'date' => 1764549759,
                'text' => 'Hello world',
            ],
        ], JSON_THROW_ON_ERROR);

        $this->jsonRequest('POST', '/api/telegram/webhook/v2/' . $this->webhookSecret, $payload);

        self::assertResponseIsSuccessful();
        self::assertSame('OK', $this->client->getResponse()->getContent());
    }

    public function testWebhookWithBanCommand(): void
    {
        $payload = json_encode([
            'update_id' => 835874881,
            'message' => [
                'message_id' => 494401,
                'from' => [
                    'id' => 350961333,
                    'is_bot' => false,
                    'first_name' => 'Reporter',
                    'username' => 'Listopadd',
                    'language_code' => 'ru',
                ],
                'chat' => [
                    'id' => -1001180970364,
                    'title' => 'Test Chat',
                    'type' => 'supergroup',
                    'username' => 'phpyhtelka',
                ],
                'date' => 1764575504,
                'text' => '@BanishhBot',
                'entities' => [
                    ['type' => 'mention', 'offset' => 0, 'length' => 11],
                ],
                'reply_to_message' => [
                    'message_id' => 494387,
                    'from' => [
                        'id' => 8323753324,
                        'is_bot' => false,
                        'first_name' => 'Spammer',
                        'username' => 'spammer_user',
                    ],
                    'chat' => [
                        'id' => -1001180970364,
                        'title' => 'Test Chat',
                        'type' => 'supergroup',
                    ],
                    'date' => 1764571048,
                    'text' => 'Spam message content',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->jsonRequest('POST', '/api/telegram/webhook/v2/' . $this->webhookSecret, $payload);

        self::assertResponseIsSuccessful();
        self::assertSame('OK', $this->client->getResponse()->getContent());
    }

    public function testWebhookWithCallbackQuery(): void
    {
        $payload = json_encode([
            'update_id' => 835874900,
            'callback_query' => [
                'id' => '123456789',
                'from' => [
                    'id' => 217708876,
                    'is_bot' => false,
                    'first_name' => 'Voter',
                    'username' => 'BeMySlaveDarlin',
                ],
                'message' => [
                    'message_id' => 12345,
                    'from' => [
                        'id' => 7098212041,
                        'is_bot' => true,
                        'first_name' => 'Banish Bot',
                        'username' => 'BanishhBot',
                    ],
                    'chat' => [
                        'id' => -1001180970364,
                        'title' => 'Test Chat',
                        'type' => 'supergroup',
                    ],
                    'date' => 1764575504,
                    'text' => 'Ban vote message',
                ],
                'data' => 'ban:12345',
            ],
        ], JSON_THROW_ON_ERROR);

        $this->jsonRequest('POST', '/api/telegram/webhook/v2/' . $this->webhookSecret, $payload);

        self::assertResponseIsSuccessful();
        self::assertSame('OK', $this->client->getResponse()->getContent());
    }

    public function testWebhookWithReaction(): void
    {
        $payload = json_encode([
            'update_id' => 835874862,
            'message_reaction' => [
                'chat' => [
                    'id' => -1001180970364,
                    'type' => 'supergroup',
                    'title' => 'Test Chat',
                    'username' => 'phpyhtelka',
                ],
                'message_id' => 494387,
                'user' => [
                    'id' => 217708876,
                    'is_bot' => false,
                    'first_name' => 'Izya',
                    'username' => 'BeMySlaveDarlin',
                    'language_code' => 'ru',
                ],
                'date' => 1764571061,
                'new_reaction' => [
                    ['type' => 'emoji', 'emoji' => "\u{1F4A9}"],
                ],
                'old_reaction' => [],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->jsonRequest('POST', '/api/telegram/webhook/v2/' . $this->webhookSecret, $payload);

        self::assertResponseIsSuccessful();
        self::assertSame('OK', $this->client->getResponse()->getContent());
    }

    public function testWebhookWithInvalidSecret(): void
    {
        $payload = json_encode(['update_id' => 1, 'message' => ['text' => 'test']], JSON_THROW_ON_ERROR);

        $this->jsonRequest('POST', '/api/telegram/webhook/v2/wrong-secret', $payload);

        self::assertSame(403, $this->client->getResponse()->getStatusCode());
        self::assertSame('Forbidden', $this->client->getResponse()->getContent());
    }

    public function testWebhookWithEmptyJsonObject(): void
    {
        $this->jsonRequest('POST', '/api/telegram/webhook/v2/' . $this->webhookSecret, '{}');

        self::assertResponseIsSuccessful();
        self::assertSame('OK', $this->client->getResponse()->getContent());
    }

    public function testWebhookWithMyChatMemberUpdate(): void
    {
        $payload = json_encode([
            'update_id' => 835874910,
            'my_chat_member' => [
                'chat' => [
                    'id' => -1001180970364,
                    'title' => 'Test Chat',
                    'type' => 'supergroup',
                ],
                'from' => [
                    'id' => 217708876,
                    'is_bot' => false,
                    'first_name' => 'Admin',
                ],
                'date' => 1764575504,
                'old_chat_member' => [
                    'user' => ['id' => 7098212041, 'is_bot' => true, 'first_name' => 'Bot'],
                    'status' => 'member',
                ],
                'new_chat_member' => [
                    'user' => ['id' => 7098212041, 'is_bot' => true, 'first_name' => 'Bot'],
                    'status' => 'administrator',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->jsonRequest('POST', '/api/telegram/webhook/v2/' . $this->webhookSecret, $payload);

        self::assertResponseIsSuccessful();
        self::assertSame('OK', $this->client->getResponse()->getContent());
    }

    public function testWebhookWithMessageReactionCount(): void
    {
        $payload = json_encode([
            'update_id' => 835874920,
            'message_reaction_count' => [
                'chat' => [
                    'id' => -1001180970364,
                    'title' => 'Test Chat',
                    'type' => 'supergroup',
                ],
                'message_id' => 494387,
                'date' => 1764571061,
                'reactions' => [
                    ['type' => ['type' => 'emoji', 'emoji' => "\u{1F44D}"], 'total_count' => 5],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->jsonRequest('POST', '/api/telegram/webhook/v2/' . $this->webhookSecret, $payload);

        self::assertResponseIsSuccessful();
        self::assertSame('OK', $this->client->getResponse()->getContent());
    }
}
