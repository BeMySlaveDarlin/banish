# Banish - Telegram Moderation Bot

Telegram-бот для модерации чатов через систему голосования. Позволяет участникам голосовать за бан или прощение нарушителей.

[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php)](https://php.net)
[![Symfony](https://img.shields.io/badge/Symfony-6.4-000000?logo=symfony)](https://symfony.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15-4169E1?logo=postgresql)](https://www.postgresql.org)
[![Docker](https://img.shields.io/badge/Docker-26.1-2496ED?logo=docker)](https://www.docker.com)

## Stack

- PHP 8.3
- Symfony 6.4
- PostgreSQL 15
- Redis 5
- Memcached
- Nginx
- Supervisor

## Установка

### Клонирование

```bash
git clone git@github.com:BeMySlaveDarlin/banish.git
cd banish
```

### Конфигурация

```bash
cp .env.example .env
```

Обязательные параметры в `.env`:

| Параметр             | Описание                                  |
|----------------------|-------------------------------------------|
| `APP_ENV`            | Окружение: `prod`, `dev`, `local`, `test` |
| `APP_SECRET`         | Секрет для webhook интеграции             |
| `DATABASE_PORTS`     | Порт PostgreSQL (рекомендуется изменить)  |
| `MEMCACHED_PORTS`    | Порт Memcached (рекомендуется изменить)   |
| `TELEGRAM_BOT_NAME`  | Username бота без `@`                     |
| `TELEGRAM_BOT_TOKEN` | API токен бота                            |

### SSL сертификаты

```bash
cp docker/dummy/compose/docker-compose.prod.yaml docker-compose.override.yaml
```

Для prod окружения разместить сертификаты в `var/ssl`:

- `server.crt` - публичный ключ
- `server.key` - приватный ключ

Для dev окружения:

```bash
cp docker/dummy/ssl/* var/ssl/
```

### Запуск

```bash
make
```

Остановка:

```bash
make down
```

## Настройка Telegram

### Создание бота

1. Создать бота через [@BotFather](https://telegram.me/BotFather)
2. Получить токен

### Webhook

```bash
curl "https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/setWebhook?url=https://<HOSTNAME>/api/telegram/webhook/<APP_SECRET>"
```

### Права в чате

Добавить бота администратором с правами:

- Чтение сообщений
- Отправка сообщений
- Удаление сообщений
- Блокировка пользователей

## Команды бота

| Команда                     | Описание                                            | Доступ |
|-----------------------------|-----------------------------------------------------|--------|
| `/votesLimit N`             | Установить лимит голосов для бана/прощения (мин. 3) | Админ  |
| `/setMinMessagesForTrust N` | Минимум сообщений для доверенного пользователя      | Админ  |
| `/toggleBot`                | Включить/выключить бота                             | Админ  |
| `/toggleDeleteMessage`      | Включить/выключить удаление сообщений при бане      | Админ  |
| `/help`                     | Справка                                             | Все    |

## Использование

### Запуск процедуры бана

**Вариант 1:** Ответить на спам-сообщение с упоминанием `@bot_name`

**Вариант 2:** Упомянуть `@bot_name` - процедура начнется для предыдущего сообщения

### Голосование

После запуска процедуры участники голосуют кнопками:

- 🔨 Ban
- 🕊️ Forgive

При достижении лимита голосов (`votesLimit`) пользователь банится или прощается.

## Автоочистка сообщений

Настройка расписания очистки в таблице `public.queue_schedule_rule`:

```sql
-- Cron правило
UPDATE queue_schedule_rule
SET rule = '0 */6 * * *'
WHERE schedule = 'clear_bot_messages';

-- Интервал (5 seconds, 2 hours, 7 days)
UPDATE queue_schedule_rule
SET rule = '1 day'
WHERE schedule = 'clear_bot_messages';

-- Отключить очистку
DELETE
FROM queue_schedule_rule
WHERE schedule = 'clear_bot_messages';
```

## Make команды

```bash
make                    # Полная сборка и запуск
make restart           # Пересборка
make down              # Остановка
make composer-install  # Установка зависимостей
make composer-update   # Обновление зависимостей
make db-migrate        # Миграции
make clear-cache       # Очистка кеша
make clear-all         # Очистка кеша и логов
```

## Отключение бота глобально

```bash
curl "https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/deleteWebhook"
```

## Contributing

### If you have new and marvelous idea

Fork and [Request Pull](https://github.com/BeMySlaveDarlin/banish/pulls)!

### If you found issue or most probably shit-code

Create an [Issue](https://github.com/BeMySlaveDarlin/banish/issues)

[BackToTop](#Contents)
