<?php

namespace Anik\Amqp;

use Anik\Amqp\Exceptions\AmqpException;
use Anik\Amqp\Exchanges\Exchange;

class Producer extends Connection
{
    public function publish(
        Producible $message,
        string $routingKey = '',
        ?Exchange $exchange = null,
        array $options = []
    ): bool {
        return $this->publishBatch([$message], $routingKey, $exchange, $options);
    }

    public function publishBatch(
        array $messages,
        string $routingKey = '',
        ?Exchange $exchange = null,
        array $options = []
    ): bool {
        if (count($messages) === 0) {
            return false;
        }

        $exchange = $this->prepareExchange($exchange, $options['exchange'] ?? []);

        $channel = $this->getChannel();
        $mandatory = $options['publish']['mandatory'] ?? false;
        $immediate = $options['publish']['immediate'] ?? false;
        $ticket = $options['publish']['ticket'] ?? null;

        $count = $batchCount = (int)($options['publish']['batch_count'] ?? 500);
        foreach ($messages as $message) {
            if (!$message instanceof Producible) {
                throw new AmqpException('Message must be an implementation of Anik\Amqp\Producible');
            }

            $channel->batch_basic_publish(
                $message->build(),
                $exchange->getName(),
                $routingKey,
                $mandatory,
                $immediate,
                $ticket
            );

            if (--$count <= 0) {
                $count = $batchCount;
                $channel->publish_batch();
            }
        }

        $channel->publish_batch();

        return true;
    }

    public function publishBasic(
        Producible $message,
        string $routingKey = '',
        ?Exchange $exchange = null,
        array $options = []
    ): bool {
        $exchange = $this->prepareExchange($exchange, $options['exchange'] ?? []);

        $channel = $this->getChannel();
        $mandatory = $options['publish']['mandatory'] ?? false;
        $immediate = $options['publish']['immediate'] ?? false;
        $ticket = $options['publish']['ticket'] ?? null;

        $channel->basic_publish(
            $message->build(),
            $exchange->getName(),
            $routingKey,
            $mandatory,
            $immediate,
            $ticket
        );

        return true;
    }
}
