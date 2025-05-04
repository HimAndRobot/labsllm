<?php

namespace LabsLLM\Messages;

class MessagesBag
{
    /**
     * @var Message[]
     */
    protected array $messages = [];

    /**
     * @param Message[] $messages
     * @return self
     */
    public static function create(array $messages = []): self
    {
        $instance = new self();
        $instance->messages = array_map(fn (Message $message) => $message->toArray(), $messages);

        return $instance;
    }

    /**
     * @param string $json
     * @return self
     */
    public static function createFromJson(string $json): self
    {
        if (empty($json)) {
            throw new \InvalidArgumentException('JSON is empty');
        }

        $instance = new self();
        $instance->messages = json_decode($json, true);

        return $instance;
    }

    /**
     * @param Message $message
     * @return self
     */
    public function add(Message $message): self
    {
        $this->messages[] = $message->toArray();
        return $this;
    }

    /**
     * @return string
     */
    public function asJson(): string
    {
        return json_encode($this->messages);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->messages;
    }
}