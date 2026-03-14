<?php

declare(strict_types=1);

namespace App\Tests\Unit\EdgeCases;

use App\Application\Command\Telegram\UnsupportedCommand;
use App\Domain\Telegram\ValueObject\TelegramCallbackQuery;
use App\Domain\Telegram\ValueObject\TelegramMessage;
use App\Domain\Telegram\ValueObject\TelegramMessageChat;
use App\Domain\Telegram\ValueObject\TelegramMessageFrom;
use App\Domain\Telegram\ValueObject\TelegramUpdate;
use App\Infrastructure\Telegram\Routing\Registry\FallbackRoutesRegistry;
use App\Infrastructure\Telegram\Routing\Router;
use App\Tests\Factory\TelegramUpdateFactory;
use App\Tests\TestCase\AbstractUnitTestCase;

final class TelegramEdgeCasesTest extends AbstractUnitTestCase
{
    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = new Router('test_bot', [new FallbackRoutesRegistry()]);
    }

    public function testUpdateWithNullMessageRoutesToUnsupported(): void
    {
        $update = new TelegramUpdate();
        $update->update_id = 1;

        $result = $this->router->route($update);

        self::assertSame(UnsupportedCommand::class, $result);
    }

    public function testPrivateChatMessageIsDetected(): void
    {
        $update = new TelegramUpdate();
        $update->update_id = 2;

        $message = new TelegramMessage();
        $message->message_id = 1;
        $message->date = time();
        $message->text = 'hello';
        $message->sticker = null;
        $message->document = null;

        $chat = new TelegramMessageChat();
        $chat->id = 12345;
        $chat->type = 'private';
        $message->chat = $chat;

        $from = new TelegramMessageFrom();
        $from->id = 12345;
        $from->is_bot = false;
        $message->from = $from;

        $update->message = $message;

        self::assertTrue($update->getChat()->isPrivate());
        self::assertSame(12345, $update->getChat()->id);
    }

    public function testEmptyCallbackDataReturnsNullData(): void
    {
        $update = TelegramUpdateFactory::createCallbackQuery(-1001234567890, 12345, '', 100);

        self::assertTrue($update->hasCallbackQueryData());
        self::assertNotNull($update->callback_query);
        self::assertSame('', $update->callback_query->data);
    }

    public function testChatIdZeroReturnsZeroFromGetChat(): void
    {
        $update = new TelegramUpdate();
        $update->update_id = 3;

        $message = new TelegramMessage();
        $message->message_id = 1;
        $message->date = time();
        $message->sticker = null;
        $message->document = null;

        $chat = new TelegramMessageChat();
        $chat->id = 0;
        $chat->type = 'supergroup';
        $message->chat = $chat;

        $from = new TelegramMessageFrom();
        $from->id = 100;
        $from->is_bot = false;
        $message->from = $from;

        $update->message = $message;

        self::assertSame(0, $update->getChat()->id);
        self::assertFalse($update->getChat()->isEmpty());
    }

    public function testUserIdZeroReturnsZeroFromGetFrom(): void
    {
        $update = new TelegramUpdate();
        $update->update_id = 4;

        $message = new TelegramMessage();
        $message->message_id = 1;
        $message->date = time();
        $message->sticker = null;
        $message->document = null;

        $chat = new TelegramMessageChat();
        $chat->id = -1001234567890;
        $chat->type = 'supergroup';
        $message->chat = $chat;

        $from = new TelegramMessageFrom();
        $from->id = 0;
        $from->is_bot = false;
        $message->from = $from;

        $update->message = $message;

        self::assertSame(0, $update->getFrom()->id);
        self::assertFalse($update->getFrom()->isEmpty());
    }

    public function testUpdateWithNoFieldsReturnsFallbackChat(): void
    {
        $update = new TelegramUpdate();
        $update->update_id = 5;

        $chat = $update->getChat();
        $from = $update->getFrom();

        self::assertNull($chat->id);
        self::assertTrue($chat->isEmpty());
        self::assertNull($from->id);
        self::assertTrue($from->isEmpty());
    }
}
