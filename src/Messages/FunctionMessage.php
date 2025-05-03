<?php

namespace LabsLLM\Messages;

/**
 * Class for function messages
 */
class FunctionMessage extends Message
{
    /**
     * Function name
     */
    protected string $name;
    
    /**
     * Constructor
     *
     * @param string $content
     * @param string $name
     */
    public function __construct(string $content, string $name)
    {
        parent::__construct('function', $content);
        $this->name = $name;
    }
    
    /**
     * Returns the function name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
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
            'name' => $this->name
        ];
    }
} 