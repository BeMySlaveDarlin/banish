# Banish - Telegram Moderation Bot

[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php)](https://php.net)
[![Symfony](https://img.shields.io/badge/Symfony-6.4-000000?logo=symfony)](https://symfony.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15-4169E1?logo=postgresql)](https://www.postgresql.org)
[![Docker](https://img.shields.io/badge/Docker-26.1-2496ED?logo=docker)](https://www.docker.com)

---

## About

**Banish** is a Telegram bot for democratic chat moderation. It allows community members to vote on banning or forgiving spammers through reactions, buttons, or commands.

### Features

- ✅ **Three ways to start a ban**: `/ban` command, bot mention, message reaction
- ✅ **Flexible voting**: inline buttons or reactions with customizable emoji
- ✅ **Admin panel**: manage chat settings, view history
- ✅ **Trusted users**: protect active members and admins from bans
- ✅ **Async processing**: scalable with message queues
- ✅ **Security**: admins are immune from banning

### Tech Stack

**Backend:** PHP 8.3 • Symfony 6.4 • PostgreSQL 15 • Redis • RabbitMQ
**Frontend:** Vue.js 3 • Vite • Vue Router
**Infrastructure:** Docker • Nginx • Supervisor

---

## Usage

### Starting a Ban Procedure

**Option 1: Command**
```
Reply to spammer's message: /ban
```

**Option 2: Bot Mention**
```
Reply to spammer's message: @bot_name
```

**Option 3: Reaction**
```
Long-press message → Add reaction → Select ban emoji (default: 👎)
```

### Voting

After ban procedure starts, members vote using:

**Inline Buttons**
- 🔨 Ban — vote for ban
- 🕊️ Forgive — vote for forgiveness

**Reactions on Original Message**
- Ban emoji (👎) = vote for ban
- Forgive emoji (👍) = vote for forgiveness
- Remove reaction = cancel vote

### Vote Results

When required vote count is reached (configurable):

- **Ban approved** → User blocked, spam message deleted
- **Forgive approved** → Procedure cancelled, user remains in chat

### Bot Commands

| Command | Description | Access |
|---------|-------------|--------|
| `/help` | Bot help | Everyone |
| `/ban` | Start ban procedure | Everyone |
| `/admin` | Get admin panel link | Chat admins |

### Admin Panel

Access via `/admin`. Configure per-chat settings:

- Vote count required for ban
- Emoji for reactions
- Minimum messages for "trusted" user protection
- Delete spam message after ban
- Enable/disable voting by reactions

---

## Installation

### Requirements

- Docker & Docker Compose
- 8+ vCPU, 16+ GB RAM (for production)
- Public domain with HTTPS

### Clone & Setup

```bash
git clone git@github.com:BeMySlaveDarlin/banish.git
cd banish
```

### Docker Compose Configuration

Copy appropriate compose file for your environment:

```bash
# For production
cp docker/docker-compose.prod.yaml docker-compose.override.yaml

# For development
cp docker/docker-compose.dev.yaml docker-compose.override.yaml
```

Or use Docker's default override mechanism — it will automatically load `docker-compose.override.yaml`.

### Environment Configuration

```bash
cp .env.example .env
```

**Required .env parameters:**

| Parameter | Description |
|-----------|-------------|
| `APP_ENV` | Environment: `prod`, `dev`, `local`, `test` |
| `APP_SECRET` | Webhook signature secret (random string) |
| `TELEGRAM_BOT_NAME` | Bot username without `@` |
| `TELEGRAM_BOT_TOKEN` | Bot API token from BotFather |
| `DATABASE_PORTS` | PostgreSQL port (change from default) |
| `MEMCACHED_PORTS` | Memcached port (change from default) |

### SSL Certificates

**For production:**
Place in `var/ssl/`:
- `server.crt` — public certificate
- `server.key` — private key

**For development:**
Certificates are generated automatically on first build:
```bash
make                  # Automatically generates SSL for NGINX_BACKEND_DOMAIN
```

Or manually:
```bash
mkdir -p var/ssl
openssl req -x509 -newkey rsa:4096 -keyout var/ssl/server.key -out var/ssl/server.crt -days 365 -nodes -subj "/CN=${NGINX_BACKEND_DOMAIN:-localhost}"
```

### Start Services

```bash
make                  # Full build and start
make down             # Stop all services
make restart          # Rebuild and restart
make db-migrate       # Run migrations
make clear-cache      # Clear cache
```

---

## Configuration

### Telegram Setup

**1. Create Bot**
- Talk to [@BotFather](https://telegram.me/BotFather)
- Create new bot, get token

**2. Set Webhook**
```bash
curl "https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://<DOMAIN>/api/telegram/webhook/<APP_SECRET>"
```

Verify:
```bash
curl "https://api.telegram.org/bot<TOKEN>/getWebhookInfo"
```

**3. Add to Group**
- Add bot as member with permissions:
  - Read messages
  - Send messages
  - Delete messages
  - Ban users

### Group Settings

For reaction voting to work:

1. **Enable Reactions**
   - Group Settings → Reactions → Enable

2. **Bot Receives Updates**
   - Webhook has `message_reaction` in `allowed_updates`

3. **Enable in Admin Panel**
   - Set `enableReactions: true` (default)

### Per-Chat Configuration

In admin panel (`/admin`):

- **Votes Required** — votes to approve ban (default: 3)
- **Ban Emoji** — emoji for ban votes (default: 👎)
- **Forgive Emoji** — emoji for forgiveness (default: 👍)
- **Min Messages for Trust** — messages to protect user (default: 5)
- **Delete Spam** — remove spam message after ban (default: true)
- **Enable Reactions** — allow reaction voting (default: true)

### Auto Cleanup

Configure in database `queue_schedule_rule` table:

```sql
-- Every 6 hours
UPDATE queue_schedule_rule
SET rule = '0 */6 * * *'
WHERE schedule = 'clear_bot_messages';

-- Or use intervals (1 day, 7 days, etc)
UPDATE queue_schedule_rule
SET rule = '1 day'
WHERE schedule = 'clear_bot_messages';

-- Disable cleanup
DELETE FROM queue_schedule_rule
WHERE schedule = 'clear_bot_messages';
```

---

## Troubleshooting

**Reactions not creating ban:**
- Verify reactions enabled in group settings
- Check webhook has `message_reaction` in `allowed_updates`
- View logs: `docker-compose logs app`

**Voting doesn't work:**
- Verify correct emoji in admin panel
- Check chat enabled (`enabled: true`)
- Check vote count threshold

**"Admin is immune" message:**
- Expected — group admins cannot be banned

**Webhook issues:**
- Test: `curl "https://api.telegram.org/bot<TOKEN>/getWebhookInfo"`
- Verify domain DNS resolves
- Check HTTPS certificate is valid

---

## Contributing

Found a bug or have an idea?

- **New features:** [Create PR](https://github.com/BeMySlaveDarlin/banish/pulls)
- **Report issue:** [Create Issue](https://github.com/BeMySlaveDarlin/banish/issues)
