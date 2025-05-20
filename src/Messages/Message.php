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
        protected string|null $content,
        protected array|null $toolCalls = null,
        protected string|null $toolCallId = null,
        protected string|null $toolName = null,
        protected string|null $toolDescription = null,
        protected array|string|null $toolArguments = null,
        protected array|string|null $toolResponse = null
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
            'content' => $this->content,
            ...($this->toolCalls ? ['tool_calls' => $this->toolCalls] : []),
            ...($this->toolCallId ? ['tool_call_id' => $this->toolCallId] : []),
            ...($this->toolName ? ['tool_name' => $this->toolName] : []),
            ...($this->toolDescription ? ['tool_description' => $this->toolDescription] : []),
            ...($this->toolArguments ? ['tool_arguments' => $this->toolArguments] : []),
            ...($this->toolResponse ? ['tool_response' => $this->toolResponse] : [])
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
    public static function assistant(string|null $content, array|null $toolCalls = null): self
    {
        return new self('assistant', $content, ($toolCalls ? $toolCalls : []));
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
     * Creates a tool message
     *
     * @param string $content
     * @param string $id
     * @param string $name
     * @param string $description
     * @param array|string $arguments
     * @param array|string $response
     * @return \LabsLLM\Messages\Message
     */
    public static function tool(string $content, string $id, string $name, string $description, array $arguments, string|null $response): self
    {
        if (is_array($arguments)) {
            $arguments = json_encode($arguments);
        }
        if (is_array($response)) {
            $response = json_encode($response);
        }
        return new self('tool', $content, null, $id, $name, $description, $arguments, $response);
    }

    /**
     * Creates a tool message with a call
     *
     * @param array $toolResponse
     * @param string $reponse
     * @param string $description
     * @return \LabsLLM\Messages\Message
     */
    public static function toolWithCall(array $toolResponse, string $reponse, string $description = ''): self {
        return new self('tool', $reponse, null, $toolResponse['id'], $toolResponse['name'], $description, $toolResponse['arguments'], $reponse);
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