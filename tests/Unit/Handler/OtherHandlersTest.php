<?php

declare(strict_types=1);

namespace App\Tests\Unit\Handler;

use App\Application\Command\Telegram\HelpCommand;
use App\Application\Command\Telegram\Message\DeletedMessageCommand;
use App\Application\Command\Telegram\MyChatMember\MyChatMemberCommand;
use App\Application\Command\Telegram\UnsupportedCommand;
use App\Application\Handler\HelpHandler;
use App\Application\Handler\Message\DeletedMessageHandler;
use App\Application\Handler\MyChatMember\MyChatMemberHandler;
use App\Application\Handler\UnsupportedHandler;
use App\Domain\Telegram\Constants\Messages;
use App\Domain\Telegram\Enum\UserStatus;
use App\Domain\Telegram\Repository\RequestHistoryRepository;
use App\Domain\Telegram\Repository\UserRepository;
use App\Domain\Telegram\Service\TelegramApiServiceInterface;
use App\Domain\Telegram\Service\UserPersisterInterface;
use App\Domain\Telegram\ValueObject\TelegramMessageEntity;
use App\Tests\Factory\EntityFactory;
use App\Tests\Factory\TelegramUpdateFactory;
use App\Tests\TestCase\AbstractUnitTestCase;
use Psr\Log\NullLogger;

final class OtherHandlersTest extends AbstractUnitTestCase
{
    private const int CHAT_ID = -1001180970364;
    private const int USER_ID = 217708876;

    public function testHelpHandlerReturnsHelpMessage(): void
    {
        $telegramApi = $this->createStub(TelegramApiServiceInterface::class);
        $telegramApi->method('sendMessage')->willReturn(null);

        $handler = new HelpHandler($telegramApi, 'test_bot');

        $chat = EntityFactory::createChat(self::CHAT_ID);
        $user = EntityFactory::createUser(self::CHAT_ID, self::USER_ID);
        $update = TelegramUpdateFactory::createTextMessage(self::CHAT_ID, self::USER_ID, '/help');

        $entity = new TelegramMessageEntity();
        $entity->type = 'bot_command';
        $entity->offset = 0;
        $entity->length = 5;

        $message = $update->message;
        self::assertNotNull($message);
        $message->entities = [$entity];

        $command = new HelpCommand($update, $chat, $user);

        $result = $handler->handle($command);

        self::assertSame(Messages::MESSAGE_PROCESSED, $result);
    }

    public function testHelpHandlerNoBotCommandReturnsCommand404(): void
    {
        $telegramApi = $this->createStub(TelegramApiServiceInterface::class);
        $handler = new HelpHandler($telegramApi, 'test_bot');

        $chat = EntityFactory::createChat(self::CHAT_ID);
        $user = EntityFactory::createUser(self::CHAT_ID, self::USER_ID);
        $update = TelegramUpdateFactory::createTextMessage(self::CHAT_ID, self::USER_ID, 'just text');
        $command = new HelpCommand($update, $chat, $user);

        $result = $handler->handle($command);

        self::assertSame(Messages::MESSAGE_COMMAND_404, $result);
    }

    public function testMyChatMemberHandlerUserJoinedPersistsCalled(): void
    {
        $userRepository = $this->createStub(UserRepository::class);
        $userPersister = $this->createMock(UserPersisterInterface::class);

        $handler = new MyChatMemberHandler($userRepository, $userPersister);

        $update = TelegramUpdateFactory::createMyChatMember(self::CHAT_ID, 'left', 'member');
        $myChatMember = $update->my_chat_member;
        self::assertNotNull($myChatMember);
        self::assertNotNull($myChatMember->old_chat_member);
        self::assertNotNull($myChatMember->new_chat_member);
        $myChatMember->old_chat_member->status = 'left';
        $myChatMember->new_chat_member->status = 'member';

        $chat = EntityFactory::createChat(self::CHAT_ID);
        $user = EntityFactory::createUser(self::CHAT_ID, self::USER_ID);
        $command = new MyChatMemberCommand($update, $chat, $user);

        $userPersister->expects(self::once())->method('persist');

        $result = $handler->handle($command);

        self::assertSame(Messages::MESSAGE_SILENT_OK, $result);
    }

    public function testMyChatMemberHandlerUserLeftStatusChanged(): void
    {
        $userRepository = $this->createMock(UserRepository::class);
        $userPersister = $this->createStub(UserPersisterInterface::class);

        $handler = new MyChatMemberHandler($userRepository, $userPersister);

        $update = TelegramUpdateFactory::createMyChatMember(self::CHAT_ID, 'member', 'left');
        $myChatMember = $update->my_chat_member;
        self::assertNotNull($myChatMember);
        self::assertNotNull($myChatMember->old_chat_member);
        self::assertNotNull($myChatMember->new_chat_member);
        $myChatMember->old_chat_member->status = 'member';
        $myChatMember->new_chat_member->status = 'left';

        $existingUser = EntityFactory::createUser(self::CHAT_ID, self::USER_ID);

        $chat = EntityFactory::createChat(self::CHAT_ID);
        $user = EntityFactory::createUser(self::CHAT_ID, self::USER_ID);
        $command = new MyChatMemberCommand($update, $chat, $user);

        $userRepository->method('findByChatAndUser')->willReturn($existingUser);
        $userRepository->expects(self::once())->method('save');

        $result = $handler->handle($command);

        self::assertSame(Messages::MESSAGE_SILENT_OK, $result);
        self::assertSame(UserStatus::LEFT, $existingUser->status);
    }

    public function testMyChatMemberHandlerUserBannedStatusChanged(): void
    {
        $userRepository = $this->createMock(UserRepository::class);
        $userPersister = $this->createStub(UserPersisterInterface::class);

        $handler = new MyChatMemberHandler($userRepository, $userPersister);

        $update = TelegramUpdateFactory::createMyChatMember(self::CHAT_ID, 'member', 'kicked');
        $myChatMember = $update->my_chat_member;
        self::assertNotNull($myChatMember);
        self::assertNotNull($myChatMember->old_chat_member);
        self::assertNotNull($myChatMember->new_chat_member);
        $myChatMember->old_chat_member->status = 'member';
        $myChatMember->new_chat_member->status = 'kicked';

        $existingUser = EntityFactory::createUser(self::CHAT_ID, self::USER_ID);

        $chat = EntityFactory::createChat(self::CHAT_ID);
        $user = EntityFactory::createUser(self::CHAT_ID, self::USER_ID);
        $command = new MyChatMemberCommand($update, $chat, $user);

        $userRepository->method('findByChatAndUser')->willReturn($existingUser);
        $userRepository->expects(self::once())->method('save');

        $result = $handler->handle($command);

        self::assertSame(Messages::MESSAGE_SILENT_OK, $result);
        self::assertSame(UserStatus::BANNED, $existingUser->status);
    }

    public function testMyChatMemberHandlerNoMyChatMemberReturnsSilentOk(): void
    {
        $userRepository = $this->createStub(UserRepository::class);
        $userPersister = $this->createStub(UserPersisterInterface::class);

        $handler = new MyChatMemberHandler($userRepository, $userPersister);

        $chat = EntityFactory::createChat(self::CHAT_ID);
        $user = EntityFactory::createUser(self::CHAT_ID, self::USER_ID);
        $update = TelegramUpdateFactory::createTextMessage(self::CHAT_ID, self::USER_ID, 'test');
        $command = new MyChatMemberCommand($update, $chat, $user);

        $result = $handler->handle($command);

        self::assertSame(Messages::MESSAGE_SILENT_OK, $result);
    }

    public function testDeletedMessageHandlerMarksMessageAsDeleted(): void
    {
        $historyRepo = $this->createMock(RequestHistoryRepository::class);
        $handler = new DeletedMessageHandler($historyRepo, new NullLogger());

        $update = TelegramUpdateFactory::createDeletedMessage(self::CHAT_ID, 12345);
        $chat = EntityFactory::createChat(self::CHAT_ID);
        $user = EntityFactory::createUser(self::CHAT_ID, self::USER_ID);
        $command = new DeletedMessageCommand($update, $chat, $user);

        $historyRepo->expects(self::once())
            ->method('markMessageDeleted')
            ->with(self::CHAT_ID, 12345);

        $result = $handler->handle($command);

        self::assertSame(Messages::MESSAGE_SILENT_OK, $result);
    }

    public function testDeletedMessageHandlerZeroChatIdReturnsSilentOk(): void
    {
        $historyRepo = $this->createMock(RequestHistoryRepository::class);
        $handler = new DeletedMessageHandler($historyRepo, new NullLogger());

        $update = TelegramUpdateFactory::createDeletedMessage(0, 0);
        $chat = EntityFactory::createChat(self::CHAT_ID);
        $user = EntityFactory::createUser(self::CHAT_ID, self::USER_ID);
        $command = new DeletedMessageCommand($update, $chat, $user);

        $historyRepo->expects(self::never())->method('markMessageDeleted');

        $result = $handler->handle($command);

        self::assertSame(Messages::MESSAGE_SILENT_OK, $result);
    }

    public function testDeletedMessageHandlerExceptionReturnsSilentOk(): void
    {
        $historyRepo = $this->createStub(RequestHistoryRepository::class);
        $handler = new DeletedMessageHandler($historyRepo, new NullLogger());

        $update = TelegramUpdateFactory::createDeletedMessage(self::CHAT_ID, 12345);
        $chat = EntityFactory::createChat(self::CHAT_ID);
        $user = EntityFactory::createUser(self::CHAT_ID, self::USER_ID);
        $command = new DeletedMessageCommand($update, $chat, $user);

        $historyRepo->method('markMessageDeleted')
            ->willThrowException(new \Exception('DB error'));

        $result = $handler->handle($command);

        self::assertSame(Messages::MESSAGE_SILENT_OK, $result);
    }

    public function testUnsupportedHandlerReturnsNotSupported(): void
    {
        $handler = new UnsupportedHandler();
        $command = new UnsupportedCommand();

        $result = $handler->handle($command);

        self::assertSame(Messages::MESSAGE_NOT_SUPPORTED, $result);
    }
}
