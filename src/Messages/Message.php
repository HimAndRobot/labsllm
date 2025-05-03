<?php

namespace LabsLLM\Messages;

/**
 * Base class for all messages
 */
class Message
{
    /**
     * Message role
     */
    protected string $role;
    
    /**
     * Message content
     */
    protected string $content;
    
    /**
     * Constructor
     *
     * @param string $role
     * @param string $content
     */
    public function __construct(string $role, string $content)
    {
        $this->role = $role;
        $this->content = $content;
    }
    
    /**
     * Returns the message role
     *
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }
    
    /**
     * Returns the message content
     *
     * @return string
     */
    public function getContent(): string
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
    public static function function(string $content, string $name): FunctionMessage
    {
        return new FunctionMessage($content, $name);
    }
} 