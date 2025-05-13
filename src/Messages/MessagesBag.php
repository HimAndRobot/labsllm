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
        $messagesWithoutSystem = array_filter($this->messages, fn($msg) => $msg['role'] !== 'system');
        return json_encode($messagesWithoutSystem);
    }

    /**
     * @return self
     */
    public function limit(int $limit, string $order = 'desc'): self
    {
        $newMessages = [];
        foreach (($order === 'desc' ? array_reverse($this->messages) : $this->messages) as $message) {
            $newMessages[] = $message;

            if ($order === 'asc' && !isset($message['tool_calls']) || $order === 'desc' && $message['role'] !== 'tool') {
                $limit--;
            }

            if ($limit === 0) {
                break;
            }
        }
        $this->messages = $newMessages;
        return $this;
    }

    /**
     * @return self
     */
    public function removeTools(): self
    {
        $newMessages = [];

        foreach ($this->messages as $message) {
            if ($message['role'] !== 'tool' && !isset($message['tool_calls'])) {
                    $newMessages[] = $message; 
            }
        }

        $this->messages = $newMessages;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->messages;
    }
}