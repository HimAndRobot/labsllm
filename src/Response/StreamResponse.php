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
     * @var \LabsLLM\Messages\MessagesBag | null
     */
    public $messagesBag;



    /**
     * @param string $response
     * @param array $functionCalls
     * @param array $calledTools
     * @param \LabsLLM\Messages\MessagesBag | null $messagesBag
     */
    public function __construct(string $response, array $functionCalls, array $calledTools, MessagesBag | null $messagesBag = null)
    {
        $this->response = $response;
        $this->functionCalls = $functionCalls;
        $this->calledTools = $calledTools;
        $this->messagesBag = $messagesBag;
    }

    public function isCalledTool(string $toolName): bool
    {
        return in_array($toolName, array_column($this->calledTools, 'name'));
    }
}
