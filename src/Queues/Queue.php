<?php

namespace Anik\Amqp\Queues;

use Anik\Amqp\Connection\ChannelInterface;
use Anik\Amqp\Exceptions\AmqpException;

class Queue
{
    protected $name;
    protected $declare = false;
    protected $passive = false;
    protected $durable = true;
    protected $exclusive = false;
    protected $autoDelete = false;
    protected $nowait = false;
    protected $arguments = [];
    protected $ticket = null;

    public function __construct(string $name)
    {
        $this->setName($name);
    }

    public static function make(array $options): Queue
    {
        if (!isset($options['name'])) {
            throw new AmqpException('Exchange name is required.');
        }

        return (new static($options['name']))->reconfigure($options);
    }

    public function reconfigure(array $options): self
    {
        if (isset($options['declare'])) {
            $this->setDeclare((bool)$options['declare']);
        }

        if (isset($options['passive'])) {
            $this->setPassive((bool)$options['passive']);
        }

        if (isset($options['durable'])) {
            $this->setDurable((bool)$options['durable']);
        }

        if (isset($options['exclusive'])) {
            $this->setExclusive((bool)$options['exclusive']);
        }

        if (isset($options['auto_delete'])) {
            $this->setAutoDelete((bool)$options['auto_delete']);
        }

        if (isset($options['no_wait'])) {
            $this->setNowait((bool)$options['no_wait']);
        }

        if (isset($options['arguments'])) {
            $this->setArguments((array)$options['arguments']);
        }

        if (isset($options['ticket'])) {
            $this->setTicket($options['ticket']);
        }

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setDeclare(bool $declare): self
    {
        $this->declare = $declare;

        return $this;
    }

    public function shouldDeclare(): bool
    {
        return $this->declare;
    }

    public function isPassive(): bool
    {
        return $this->passive;
    }

    public function setPassive(bool $passive): self
    {
        $this->passive = $passive;

        return $this;
    }

    public function isDurable(): bool
    {
        return $this->durable;
    }

    public function setDurable(bool $durable): self
    {
        $this->durable = $durable;

        return $this;
    }

    public function isExclusive(): bool
    {
        return $this->exclusive;
    }

    public function setExclusive(bool $exclusive): self
    {
        $this->exclusive = $exclusive;

        return $this;
    }

    public function isAutoDelete(): bool
    {
        return $this->autoDelete;
    }

    public function setAutoDelete(bool $autoDelete): self
    {
        $this->autoDelete = $autoDelete;

        return $this;
    }

    public function isNowait(): bool
    {
        return $this->nowait;
    }

    public function setNowait(bool $nowait): self
    {
        $this->nowait = $nowait;

        return $this;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function setArguments(array $arguments): self
    {
        $this->arguments = $arguments;

        return $this;
    }

    public function getTicket(): ?int
    {
        return $this->ticket;
    }

    public function setTicket(?int $ticket): self
    {
        $this->ticket = $ticket;

        return $this;
    }
}
