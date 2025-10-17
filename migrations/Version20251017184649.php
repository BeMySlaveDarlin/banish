<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251017184649 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $conn = $this->connection;

        // Получаем все записи
        $chats = $conn->fetchAllAssociative('SELECT id, options FROM telegram_chats');

        foreach ($chats as $chat) {
            $options = $chat['options'] ? json_decode($chat['options'], true) : [];
            $options['min_messages_for_trust'] = 5;

            $conn->update(
                'telegram_chats',
                ['options' => json_encode($options)],
                ['id' => $chat['id']]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $conn = $this->connection;

        $chats = $conn->fetchAllAssociative('SELECT id, options FROM telegram_chats');

        foreach ($chats as $chat) {
            $options = $chat['options'] ? json_decode($chat['options'], true) : [];

            if (isset($options['min_messages_for_trust'])) {
                unset($options['min_messages_for_trust']);
                $conn->update(
                    'telegram_chats',
                    ['options' => json_encode($options)],
                    ['id' => $chat['id']]
                );
            }
        }
    }
}
