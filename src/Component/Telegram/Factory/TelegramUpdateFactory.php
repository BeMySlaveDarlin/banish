<?php

declare(strict_types=1);

namespace App\Component\Telegram\Factory;

use App\Component\Telegram\Policy\TelegramConfigPolicy;
use App\Component\Telegram\ValueObject\TelegramUpdate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\SerializerInterface;

readonly class TelegramUpdateFactory
{
    public ?Request $request;

    public function __construct(
        public SerializerInterface $serializer,
        public RequestStack $requestStack,
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function getUpdate(): TelegramUpdate
    {
        $json = json_decode($this->request->getContent() ?? '', true, 512, JSON_THROW_ON_ERROR);
        $data = self::getData($json);

        /** @var TelegramUpdate $update */
        $update = $this->serializer->deserialize($data, TelegramUpdate::class, 'json');
        $update->request = $this->request;

        return $update;
    }

    public static function getData(array $json = []): string
    {
        if (isset($json['callback_query'])) {
            $data = $json['callback_query'];
            $message = $data['message'];
            $message['from'] = $data['from'];
            unset($message['entities'], $message['reply_markup']);

            $data['message'] = $message;
            $data['update_id'] = $json['update_id'];
            $data['callback_query_id'] = $data['id'];
            $data['callback_query_data'] = $data['data'];
            unset($data['id'], $data['data'], $data['from']);

            return json_encode($data, TelegramConfigPolicy::JSON_OPTIONS);
        }

        return json_encode($json, TelegramConfigPolicy::JSON_OPTIONS);
    }
}
