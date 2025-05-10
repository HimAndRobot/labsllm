<?php

namespace LabsLLM\Response;

class StreamResponse extends BaseResponse
{
    /**
     * @var string
     */
    public $response;

    /**
     * @param string $response
     * @param array $functionCalls
     * @param array $calledTools
     */
    public function __construct(string $response, array $functionCalls, array $calledTools)
    {
        $this->response = $response;
        $this->functionCalls = $functionCalls;
        $this->calledTools = $calledTools;
    }

    public function isCalledTool(string $toolName): bool
    {
        return in_array($toolName, array_column($this->calledTools, 'name'));
    }
}
