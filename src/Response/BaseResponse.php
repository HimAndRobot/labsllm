<?php

namespace LabsLLM\Response;

use LabsLLM\Contracts\ResponseInterface;

abstract class BaseResponse implements ResponseInterface
{

    /**
     * @var array
     */
    public $calledTools = [];

    /**
     * @var array
     */
    public $executedTools = [];

    /**
     * @var \stdClass
     */
    public $tokensUsed;

    /**
     * Check if a tool was called
     * 
     * @param string $toolName
     * @return bool
     */
    public function isCalledTool(string $toolName): bool
    {
        return in_array($toolName, array_column($this->calledTools, 'name'));
    }

    /**
     * Check if a tool was executed
     * 
     * @param string $toolName
     * @return bool
     */
    public function isExecutedTool(string $toolName): bool
    {
        return in_array($toolName, array_column($this->executedTools, 'name'));
    }

    /**
     * Get called tool by name
     * 
     * @param string $toolName
     * @return array|null
     */
    public function getCalledToolByName(string $toolName): array|null
    {
        return array_filter($this->calledTools, function ($tool) use ($toolName) {
            return $tool['name'] === $toolName;
        })[0];
    }

    /**
     * Get executed tool by name
     * 
     * @param string $toolName
     * @return array|null
     */
    public function getExecutedToolByName(string $toolName): array|null
    {
        return array_filter($this->executedTools, function ($tool) use ($toolName) {
            return $tool['name'] === $toolName;
        })[0];
    }
}