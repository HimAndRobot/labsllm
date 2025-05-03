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
    private function toArray(): array
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
    public static function user(string $content): array
    {
        return (new self('user', $content))->toArray();
    }
    
    /**
     * Creates an assistant message
     *
     * @param string $content
     * @return \LabsLLM\Messages\Message
     */
    public static function assistant(string $content): array
    {
        return (new self('assistant', $content))->toArray();
    }
    
    /**
     * Creates a system message
     *
     * @param string $content
     * @return \LabsLLM\Messages\Message
     */
    public static function system(string $content): array
    {
        return (new self('system', $content))->toArray();
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