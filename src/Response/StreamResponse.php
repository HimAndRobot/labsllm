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
     * @param array $usage
     */
    public function __construct(string $response, string $responseRaw, array $calledTools, array $executedTools, MessagesBag | null $messagesBag = null, array $usage = [])
    {
        $this->response = $response;
        $this->responseRaw = $responseRaw;
        $this->calledTools = $calledTools;
        $this->executedTools = $executedTools;
        $this->messagesBag = $messagesBag;
        $this->tokensUsed = $usage;
    }
}
