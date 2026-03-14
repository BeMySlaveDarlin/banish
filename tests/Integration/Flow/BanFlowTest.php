<?php

declare(strict_types=1);

namespace App\Tests\Integration\Flow;

use App\Domain\Common\ValueObject\JsonBValue;
use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Entity\TelegramChatUserEntity;
use App\Domain\Telegram\Enum\BanStatus;
use App\Domain\Telegram\Repository\BanRepository;
use App\Domain\Telegram\Repository\ChatRepository;
use App\Domain\Telegram\Repository\UserRepository;
use App\Domain\Telegram\Repository\VoteRepository;
use App\Tests\TestCase\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class BanFlowTest extends AbstractWebTestCase
{
    private const int CHAT_ID = -1001999999999;
    private const int REPORTER_ID = 100001;
    private const int SPAMMER_ID = 100002;
    private const int VOTER2_ID = 100003;
    private const int VOTER3_ID = 100004;
    private const int VOTER4_ID = 100005;

    private string $webhookSecret;
    private EntityManagerInterface $em;
    private ChatRepository $chatRepo;
    private UserRepository $userRepo;
    private BanRepository $banRepo;
    private VoteRepository $voteRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $container = self::getContainer();

        /** @var string $secret */
        $secret = $container->getParameter('app.secret');
        $this->webhookSecret = $secret;

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $this->em = $em;

        $this->cleanupTestData();

        /** @var ChatRepository $chatRepo */
        $chatRepo = $container->get(ChatRepository::class);
        $this->chatRepo = $chatRepo;

        /** @var UserRepository $userRepo */
        $userRepo = $container->get(UserRepository::class);
        $this->userRepo = $userRepo;

        /** @var BanRepository $banRepo */
        $banRepo = $container->get(BanRepository::class);
        $this->banRepo = $banRepo;

        /** @var VoteRepository $voteRepo */
        $voteRepo = $container->get(VoteRepository::class);
        $this->voteRepo = $voteRepo;

        $this->seedTestData();
    }

    public function testFullBanFlow(): void
    {
        $this->sendBanCommand(835900001, self::REPORTER_ID);
        self::assertResponseIsSuccessful();

        $this->refreshRepos();
        $bans = $this->banRepo->findBy(['chatId' => self::CHAT_ID]);
        self::assertNotEmpty($bans, 'Ban should be created in DB');

        /** @var TelegramChatUserBanEntity $ban */
        $ban = $bans[array_key_last($bans)];
        self::assertSame(BanStatus::PENDING, $ban->getStatus());
        self::assertSame(self::SPAMMER_ID, $ban->spammerId);
        self::assertSame(self::REPORTER_ID, $ban->reporterId);

        $votes = $this->voteRepo->findBy(['chatId' => self::CHAT_ID]);
        self::assertCount(1, $votes, 'Reporter vote should be created');

        $this->sendVote(835900002, self::VOTER2_ID, $ban->banMessageId, 'ban');
        self::assertResponseIsSuccessful();

        $this->sendVote(835900003, self::VOTER3_ID, $ban->banMessageId, 'ban');
        self::assertResponseIsSuccessful();

        $this->refreshRepos();
        $updatedBan = $this->banRepo->find($ban->id);
        self::assertNotNull($updatedBan);
        self::assertSame(BanStatus::BANNED, $updatedBan->getStatus());
    }

    public function testFullForgiveFlow(): void
    {
        $this->sendBanCommand(835900010, self::REPORTER_ID);
        self::assertResponseIsSuccessful();

        $this->refreshRepos();
        $bans = $this->banRepo->findBy(['chatId' => self::CHAT_ID]);
        self::assertNotEmpty($bans, 'Ban should be created after sendBanCommand');
        $ban = $bans[array_key_last($bans)];
        self::assertNotNull($ban->banMessageId, 'banMessageId should be set');

        $this->sendVote(835900011, self::VOTER2_ID, $ban->banMessageId, 'forgive');
        $this->sendVote(835900012, self::VOTER3_ID, $ban->banMessageId, 'forgive');
        $this->sendVote(835900013, self::VOTER4_ID, $ban->banMessageId, 'forgive');

        $this->refreshRepos();
        $updatedBan = $this->banRepo->find($ban->id);
        self::assertNotNull($updatedBan);
        self::assertSame(BanStatus::CANCELED, $updatedBan->getStatus());
    }

    public function testDuplicateBanRejected(): void
    {
        $this->sendBanCommand(835900020, self::REPORTER_ID);
        self::assertResponseIsSuccessful();

        $this->refreshRepos();
        $bansCount = count($this->banRepo->findBy(['chatId' => self::CHAT_ID]));

        $this->sendBanCommand(835900021, self::VOTER2_ID);
        self::assertResponseIsSuccessful();

        $this->refreshRepos();
        $bansAfter = count($this->banRepo->findBy(['chatId' => self::CHAT_ID]));
        self::assertSame($bansCount, $bansAfter, 'Duplicate ban should not create new ban');
    }

    public function testSelfBanRejected(): void
    {
        $this->sendBanCommand(835900030, self::SPAMMER_ID, 494387, self::SPAMMER_ID);
        self::assertResponseIsSuccessful();

        $this->refreshRepos();
        $bans = $this->banRepo->findBy([
            'chatId' => self::CHAT_ID,
            'reporterId' => self::SPAMMER_ID,
            'spammerId' => self::SPAMMER_ID,
        ]);
        self::assertEmpty($bans, 'Self-ban should not create a ban');
    }

    public function testDeduplicationBlocksDuplicateUpdate(): void
    {
        $this->sendBanCommand(835900050, self::REPORTER_ID);
        self::assertResponseIsSuccessful();

        $this->refreshRepos();
        $bansCount = count($this->banRepo->findBy(['chatId' => self::CHAT_ID]));

        $this->sendBanCommand(835900050, self::REPORTER_ID);
        self::assertResponseIsSuccessful();

        $this->refreshRepos();
        $bansAfter = count($this->banRepo->findBy(['chatId' => self::CHAT_ID]));
        self::assertSame($bansCount, $bansAfter, 'Duplicate update_id should be deduplicated');
    }

    private function refreshRepos(): void
    {
        $container = self::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $this->em = $em;

        /** @var BanRepository $banRepo */
        $banRepo = $container->get(BanRepository::class);
        $this->banRepo = $banRepo;

        /** @var VoteRepository $voteRepo */
        $voteRepo = $container->get(VoteRepository::class);
        $this->voteRepo = $voteRepo;
    }

    private function cleanupTestData(): void
    {
        $conn = $this->em->getConnection();
        $chatId = self::CHAT_ID;
        $conn->executeStatement("DELETE FROM \"telegram_chats_users_bans_votes\" WHERE chat_id = $chatId");
        $conn->executeStatement("DELETE FROM \"telegram_chats_users_bans\" WHERE chat_id = $chatId");
    }

    private function seedTestData(): void
    {
        $em = $this->em;

        $existingChat = $this->chatRepo->findByChatId(self::CHAT_ID);
        if ($existingChat === null) {
            $chat = new TelegramChatEntity();
            $chat->chatId = self::CHAT_ID;
            $chat->type = 'supergroup';
            $chat->name = 'Integration Test Chat';
            $chat->isEnabled = true;
            $chat->options = new JsonBValue(TelegramChatEntity::getDefaultOptions());
            $em->persist($chat);
        }

        foreach ([self::REPORTER_ID, self::SPAMMER_ID, self::VOTER2_ID, self::VOTER3_ID, self::VOTER4_ID] as $userId) {
            $existing = $this->userRepo->findByChatAndUser(self::CHAT_ID, $userId);
            if ($existing === null) {
                $user = new TelegramChatUserEntity();
                $user->chatId = self::CHAT_ID;
                $user->userId = $userId;
                $user->isBot = false;
                $user->isAdmin = false;
                $user->name = "User $userId";
                $em->persist($user);
            }
        }

        $em->flush();
    }

    private function sendBanCommand(
        int $updateId,
        int $reporterId,
        int $spamMessageId = 494387,
        ?int $spammerIdOverride = null
    ): void {
        $spammerId = $spammerIdOverride ?? self::SPAMMER_ID;

        $payload = json_encode([
            'update_id' => $updateId,
            'message' => [
                'message_id' => $updateId + 1000,
                'from' => [
                    'id' => $reporterId,
                    'is_bot' => false,
                    'first_name' => 'Reporter',
                ],
                'chat' => [
                    'id' => self::CHAT_ID,
                    'title' => 'Test Chat',
                    'type' => 'supergroup',
                ],
                'date' => time(),
                'text' => '/ban',
                'entities' => [
                    ['type' => 'bot_command', 'offset' => 0, 'length' => 4],
                ],
                'reply_to_message' => [
                    'message_id' => $spamMessageId,
                    'from' => [
                        'id' => $spammerId,
                        'is_bot' => false,
                        'first_name' => 'Spammer',
                    ],
                    'chat' => [
                        'id' => self::CHAT_ID,
                        'type' => 'supergroup',
                    ],
                    'date' => time() - 60,
                    'text' => 'spam content',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->jsonRequest('POST', '/api/telegram/webhook/v2/' . $this->webhookSecret, $payload);
    }

    private function sendVote(int $updateId, int $voterId, int $banMessageId, string $voteType): void
    {
        $payload = json_encode([
            'update_id' => $updateId,
            'callback_query' => [
                'id' => (string) $updateId,
                'from' => [
                    'id' => $voterId,
                    'is_bot' => false,
                    'first_name' => "Voter $voterId",
                ],
                'message' => [
                    'message_id' => $banMessageId,
                    'from' => [
                        'id' => 7098212041,
                        'is_bot' => true,
                        'first_name' => 'Bot',
                    ],
                    'chat' => [
                        'id' => self::CHAT_ID,
                        'title' => 'Test Chat',
                        'type' => 'supergroup',
                    ],
                    'date' => time(),
                    'text' => 'Ban vote',
                ],
                'data' => $voteType,
            ],
        ], JSON_THROW_ON_ERROR);

        $this->jsonRequest('POST', '/api/telegram/webhook/v2/' . $this->webhookSecret, $payload);
    }
}
