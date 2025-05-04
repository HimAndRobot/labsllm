<?php

namespace LabsLLM\Messages;

/**
 * Base class for all messages
 */
class Message
{ 
    /**
     * Constructor
     *
     * @param string $role
     * @param string $content
     */
    function __construct(
        protected string $role,
        protected string $content
    ) {}
    
    /**
     * Returns the message role
     *
     * @return string
     */
    function getRole(): string
    {
        return $this->role;
    }

    /**
     * Returns the message content
     *
     * @return string
     */
    function getContent(): string
    {
        return $this->content;
    }
    
    /**
     * Returns the message data as an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'content' => $this->content
        ];
    }
    
    /**
     * Creates a user message
     *
     * @param string $content
     * @return \LabsLLM\Messages\Message
     */
    public static function user(string $content): self
    {
        return new self('user', $content);
    }
    
    /**
     * Creates an assistant message
     *
     * @param string $content
     * @return \LabsLLM\Messages\Message
     */
    public static function assistant(string $content): self
    {
        return new self('assistant', $content);
    }
    
    /**
     * Creates a system message
     *
     * @param string $content
     * @return \LabsLLM\Messages\Message
     */
    public static function system(string $content): self
    {
        return new self('system', $content);
    }
    
    /**
     * Creates a function message
     *
     * @param string $content
     * @param string $name
     * @return \LabsLLM\Messages\FunctionMessage
     */
    public static function function(string $content, string $name): array
    {
        return (new self('function', $content))->toArray();
    }
} 