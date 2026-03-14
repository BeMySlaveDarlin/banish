# Banish

[![Build & Test](https://github.com/BeMySlaveDarlin/banish/actions/workflows/build.yml/badge.svg)](https://github.com/BeMySlaveDarlin/banish/actions)
[![PHP 8.3](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php)](https://php.net)
[![Symfony 6.4](https://img.shields.io/badge/Symfony-6.4-000000?logo=symfony)](https://symfony.com)
[![PostgreSQL 15](https://img.shields.io/badge/PostgreSQL-15-4169E1?logo=postgresql)](https://www.postgresql.org)

Telegram bot for democratic chat moderation. Community members vote to ban or forgive spammers through reactions, buttons, or commands.

## How It Works

**Start a ban** in any of three ways:

| Method | How |
|--------|-----|
| Command | Reply to spam with `/ban` |
| Mention | Reply to spam with `@bot_name` |
| Reaction | React to spam with ban emoji |

**Vote** using inline buttons or reactions on the original message. When the required vote count is reached, the spammer is banned and their messages are deleted. If forgive votes win, the procedure is cancelled.

Admins and trusted users (configurable message threshold) are immune from banning.

## Tech Stack

| Layer | Stack |
|-------|-------|
| Backend | PHP 8.3, Symfony 6.4, Doctrine ORM |
| Database | PostgreSQL 15 (with table partitioning) |
| Queue | RabbitMQ + Symfony Messenger |
| Cache | Memcached |
| Frontend | Vue 3, Pinia, Vite |
| Infrastructure | Docker, Nginx, Supervisor |
| CI/CD | GitHub Actions (6-stage pipeline) |
| Testing | PHPUnit 12 (162 tests), Vitest (60 tests) |

## Quick Start

### Prerequisites

- Docker & Docker Compose
- Domain with HTTPS (for production webhook)

### Setup

```bash
git clone git@github.com:BeMySlaveDarlin/banish.git
cd banish
cp .env.example .env

# Choose environment
cp docker/dummy/compose/docker-compose.dev.yaml docker-compose.override.yaml   # development
cp docker/dummy/compose/docker-compose.prod.yaml docker-compose.override.yaml  # production
```

Edit `.env` with your values:

```env
APP_SECRET=your_random_secret
TELEGRAM_BOT_NAME=your_bot_name
TELEGRAM_BOT_TOKEN=your_bot_token
```

### Run

```bash
make all          # build, start, install deps, migrate, cleanup
```

That's it. The bot is running.

### Telegram Webhook

```bash
curl "https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://<DOMAIN>/api/telegram/webhook/v2/<APP_SECRET>&allowed_updates=[\"message\",\"callback_query\",\"message_reaction\",\"my_chat_member\"]"
```

Add the bot to your group with permissions: read/send/delete messages, ban users.

## Admin Panel

Web interface for managing chats, users, and settings.

### Access

Generate a login link via Telegram (`/admin` command in chat) or via CLI:

```bash
docker compose exec app php bin/console app:admin:generate-link <USER_ID>
```

### Features

- **Chats** -- list of managed chats with statistics
- **Users** -- member list, ban history, manual unban
- **Config** -- votes required, emoji, trust threshold, delete mode
- **Audit Logs** -- admin action history

## CLI Commands

```bash
# Admin
docker compose exec app php bin/console app:admin:generate-link <userId>   # login link
docker compose exec app php bin/console app:admin:ban-user <chatId> <userId>  # manual ban

# Maintenance
make db-migrate          # run migrations
make quality             # PHPStan + PHPCS
make clear-all           # clear cache + logs
make refresh-partitions  # refresh DB partitions
make frontend-build      # rebuild frontend
```

## Configuration

### Per-Chat Settings (Admin Panel)

| Setting | Default | Description |
|---------|---------|-------------|
| Votes Required | 3 | Votes needed to approve ban |
| Ban Emoji | :thumbsdown: | Reaction emoji for ban votes |
| Forgive Emoji | :thumbsup: | Reaction emoji for forgive votes |
| Min Messages for Trust | 5 | Messages before user becomes "trusted" |
| Delete Messages | true | Delete spammer's messages on ban |
| Delete Only | false | Delete message without banning user |
| Enable Reactions | true | Allow voting via reactions |

### Environment Variables

| Variable | Required | Description |
|----------|----------|-------------|
| `APP_SECRET` | Yes | Webhook secret (random string) |
| `TELEGRAM_BOT_TOKEN` | Yes | Token from @BotFather |
| `TELEGRAM_BOT_NAME` | Yes | Bot username without @ |
| `DATABASE_URL` | Yes | PostgreSQL connection string |
| `MESSENGER_TRANSPORT_DSN` | Yes | RabbitMQ connection string |

See `.env.example` for all available variables.

## Architecture

Clean Architecture with strict layer separation:

```
src/
├── Application/      # CQRS commands + handlers
├── Domain/           # Business logic, entities, services
├── Infrastructure/   # Symfony integration, Doctrine, Telegram routing
└── Presentation/     # Controllers, console commands
```

Key patterns:
- **CQRS**: Command -> Handler via Symfony Messenger
- **ISP**: TelegramApiService split into ChatMember/Message/Webhook interfaces
- **Rich Domain**: Ban entity with state machine (pending -> banned/forgiven/expired)
- **Auto-discovery**: PHP attributes for command/handler registration

## Development

```bash
make build            # rebuild containers
make up               # start
make down             # stop
make phpstan          # static analysis (level 9)
make cs-check         # code style check
make cs-fix           # auto-fix code style
```

### Running Tests

```bash
# Backend
docker compose exec app php vendor/bin/phpunit

# Frontend
docker compose exec frontend npm test
```

### CI Pipeline

6-stage GitHub Actions pipeline:

```
backend-build  --> backend-quality (PHPStan + PHPCS)
               --> backend-test    (PHPUnit + PostgreSQL)

frontend-build --> frontend-quality (ESLint)
               --> frontend-test    (Vitest)
```

## License

MIT
