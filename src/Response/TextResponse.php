<?php

namespace LabsLLM\Response;

class TextResponse extends BaseResponse
{
    /**
     * @var string
     */
    public $response;

    /**
     * @param string $response
     * @param array $calledTools
     * @param array $executedTools
     */
    public function __construct(string $response, array $calledTools, array $executedTools, \stdClass $tokensUsed)
    {
        $this->response = $response;
        $this->calledTools = $calledTools;
        $this->executedTools = $executedTools;
        $this->tokensUsed = $tokensUsed;
    }
}
