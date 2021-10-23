<?php

namespace Anik\Amqp;

use Anik\Amqp\Exchanges\Exchange;
use Anik\Amqp\Qos\Qos;
use Anik\Amqp\Queues\Queue;
use PhpAmqpLib\Connection\AbstractConnection;

class Consumer extends Connection
{
    protected $consumerTag;
    protected $noLocal = false;
    protected $noAck = false;
    protected $exclusive = false;
    protected $nowait = false;
    protected $arguments = [];
    protected $ticket = null;

    public function __construct(
        AbstractConnection $connection,
        ?AMQPChannel $channel = null,
        array $options = []
    ) {
        parent::__construct($connection, $channel);

        $this->setConsumerTag($this->getDefaultConsumerTag());
        $this->reconfigure($options);
    }

    public function reconfigure(array $options): self
    {
        if (isset($options['tag'])) {
            $this->setConsumerTag($options['tag']);
        }

        if (isset($options['no_local'])) {
            $this->setNoLocal((bool)$options['no_local']);
        }

        if (isset($options['no_ack'])) {
            $this->setNoAck((bool)$options['no_ack']);
        }

        if (isset($options['exclusive'])) {
            $this->setExclusive((bool)$options['exclusive']);
        }

        if (isset($options['nowait'])) {
            $this->setNowait((bool)$options['nowait']);
        }

        if (isset($options['arguments'])) {
            $this->setArguments((array)$options['arguments']);
        }

        if (isset($options['ticket'])) {
            $this->setTicket($options['ticket']);
        }

        return $this;
    }

    protected function getDefaultConsumerTag(): string
    {
        return sprintf("anik.amqp_consumer_%s_%s", gethostname(), getmypid());
    }

    protected function setConsumerTag(string $tag): self
    {
        $this->consumerTag = $tag;

        return $this;
    }

    protected function getConsumerTag(): string
    {
        return $this->consumerTag;
    }

    public function setNoLocal(bool $noLocal): self
    {
        $this->noLocal = $noLocal;

        return $this;
    }

    public function isNoLocal(): bool
    {
        return $this->noLocal;
    }

    public function setNoAck(bool $noAck): self
    {
        $this->noAck = $noAck;

        return $this;
    }

    public function isNoAck(): bool
    {
        return $this->noAck;
    }

    public function setExclusive(bool $exclusive): self
    {
        $this->exclusive = $exclusive;

        return $this;
    }

    public function isExclusive(): bool
    {
        return $this->exclusive;
    }

    public function setNowait(bool $nowait): self
    {
        $this->nowait = $nowait;

        return $this;
    }

    public function isNowait(): bool
    {
        return $this->nowait;
    }

    public function setArguments(array $arguments): self
    {
        $this->arguments = $arguments;

        return $this;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function setTicket(int $ticket): self
    {
        $this->ticket = $ticket;

        return $this;
    }

    public function getTicket(): ?int
    {
        return $this->ticket;
    }

    public function consume(
        Consumable $handler,
        string $bindingKey = '',
        ?Exchange $exchange = null,
        ?Queue $queue = null,
        ?Qos $qos = null,
        array $options = []
    ) {
        if (isset($options['consumer'])) {
            $this->reconfigure($options['consumer']);
        }

        $exchange = $this->makeOrReconfigureExchange($exchange, $options['exchange'] ?? []);
        $queue = $this->makeOrReconfigureQueue($queue, $options['queue'] ?? []);

        $exchange->shouldDeclare() ? $this->exchangeDeclare($exchange) : null;
        $queue->shouldDeclare() ? $this->queueDeclare($queue) : null;

        $this->queueBind($queue, $exchange, $bindingKey, $options['bind'] ?? []);

        if (is_null($qos) && isset($options['qos'])) {
            $qos = Qos::make($options['qos']);
        } elseif ($qos && isset($options['qos'])) {
            $qos = $qos->reconfigure($options['qos']);
        }

        if ($qos) {
            $this->applyQos($qos);
        }

        $this->getChannel()->basic_consume(
            $queue->getName(),
            $this->getConsumerTag(),
            $this->isNoLocal(),
            $this->isNoAck(),
            $this->isExclusive(),
            $this->isNowait(),
            function ($message) use ($handler) {
                $handler->setMessage($message)->handle();
            },
            $this->getTicket(),
            $this->getArguments()
        );

        $allowedMethods = $options['consume']['allowed_methods'] ?? null;
        $nonBlocking = $options['consume']['non_blocking'] ?? false;
        $timeout = $options['consume']['timeout'] ?? 0;
        while ($this->getChannel()->is_consuming()) {
            $this->getChannel()->wait($allowedMethods, $nonBlocking, $timeout);
        }
    }
}
