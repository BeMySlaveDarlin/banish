# Telegram manual ban bot

[![PHP 8.3](https://img.shields.io/badge/PHP->=8.3-%237A86B8)]()
[![Docker 26.1](https://img.shields.io/badge/Docker-26.1-blue)]()
[![DockerCompose 2.27](https://img.shields.io/badge/DockerCompose-2.27-lightblue)]()
[![PostgreSQL 15](https://img.shields.io/badge/PostgreSQL-15-lightgreen)]()

### Contents

- [Requirements](#Requirements)
    - [Auto](#Auto)
    - [Manual](#Manual)
- [Installation](#Installation)
    - [Pre-Configuration](#Pre-Configuration)
    - [Build and run](#Build-and-run)
- [General usage guide](#General-usage-guide)
    - [How to ban?](#How-to-ban)
    - [If you have new and marvelous idea](#If-you-have-new-and-marvelous-idea)
    - [If you found issue or most probably shit-code](#If-you-found-issue-or-most-probably-shit-code)

## Requirements

#### Auto

- [Docker](https://docs.docker.com/engine/install/) `v26^`
- [Docker Compose](https://docs.docker.com/compose/install/) `v2.27^`
- Linux based OS recommended
- make (optional)

#### Manual

- PHP 8.3
- Nginx 1.19
- PostgreSQL 15
- Redis 5
- Memcached
- Supervisor
- Composer 2.5
- ...

## Installation

### Pre-Configuration

Copy project anywhere you like

```shell
git clone git@github.com:BeMySlaveDarlin/banish.git
cd banish
```

Prepare `.env`

```shell
cp .env.example .env
```

List of mandatory params:

- **APP_ENV** - main param, can be `prod`, `dev`, `local`, `test`
- **APP_SECRET** - important param for bot webhook integration
- **DATABASE_PORTS** - highly recommended to change leading value to random one for security
- **MEMCACHED_PORTS** - same importance as for DATABASE_PORTS
- **TELEGRAM_BOT_NAME** - username of your telegram bot (eg: some_awesome_bot), without leading `@`
- **TELEGRAM_BOT_TOKEN** - token for API requests

### Build and run

You can manually deploy all required services (`nginx`, `pgsql`, `php[fpm]`, `supervisor` and etc) by yourself. In this case, you can skip the rest of readme.

For those, who do not mind using docker and make, next steps

- Copy override file depending on **APP_ENV** you're using

```
cp docker/dummy/compose/docker-compose.prod.yaml docker-compose.override.yaml
```

- Place any self-signed certificate into `var/ssl`. Feel free to use dummy certs from `docker/dummy/ssl` for dev env - just copy to `var/ssl`

```
server.crt //public key
server.key //private key
```

- Run `make` to start build
- Use `make down` to remove containers and stop application

### Configuring finalized ban proccess deletion

- Check out `public.queue_schedule_rule` table.
- Change option `rule` for schedule `clear_bot_messages` to desired value:
    - For `type: cron` you can set cron rule
    - For `type: every` you can set expressions: 5 seconds, 2 hours, 7 days...
- Or, if you don't want to delete ban messages, just remove record for schedule `clear_bot_messages`

## General usage guide

- Create your bot via [@BotFather](https://telegram.me/BotFather) using [Tutorial](https://core.telegram.org/bots/tutorial)
- Set telegram bot api webhook `https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/setWebhook?url=https://<HOSTNAME>/api/telegram/webhook/<APP_SECRET>`
- Add your bot to the chat as administrator
    - Read, Write, Delete messages
    - Reply, Ban users
- Command `/votesLimit ?` to set max limit `?` of votes, required to ban or forgive user. Default is `3` (minimal required also is `3`)
- Command `/toggleBot` to enable `BANHAMMER!` feature. One more time to disable bot. Default is `false`
- Command `/toggleDeleteMessage` toggles state of message deletion on ban. Works like a switch. Default is `true`

### Global bot disable

If you want for some reason to disable bot globally

- Use https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/deleteWebhook

### How to ban?

- Ways to start ban procedure
    - Directly reply to spam message with mentioning @bot_name
    - Mention @bot_name, and it will start procedure for previous message and user
- After ban procedure starts
    - Vote for `Ban`/`Forgive`
    - Cap to a vote limits

### If you have new and marvelous idea

Fork and [Request Pull](https://github.com/BeMySlaveDarlin/banish/pulls)!

### If you found issue or most probably shit-code

Create an [Issue](https://github.com/BeMySlaveDarlin/banish/issues)

[BackToTop](#Contents)
