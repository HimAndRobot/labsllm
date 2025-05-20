<?php

namespace LabsLLM\Response;

use LabsLLM\Messages\MessagesBag;

class StreamResponse extends BaseResponse
{
    /**
     * @var string
     */
    public $response;

    /**
     * @var string
     */
    public $responseRaw;

    /**
     * @var \LabsLLM\Messages\MessagesBag | null
     */
    public $messagesBag;



    /**
     * @param string $response
     * @param array $calledTools
     * @param array $executedTools
     * @param \LabsLLM\Messages\MessagesBag | null $messagesBag
     */
    public function __construct(string $response, string $responseRaw, array $calledTools, array $executedTools, MessagesBag | null $messagesBag = null)
    {
        $this->response = $response;
        $this->responseRaw = $responseRaw;
        $this->calledTools = $calledTools;
        $this->executedTools = $executedTools;
        $this->messagesBag = $messagesBag;
    }

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
}
