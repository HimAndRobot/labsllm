<?php

namespace LabsLLM\Response;

class StructureResponse extends BaseResponse
{
    /**
     * @var \stdClass
     */
    public $response;

    /**
     * @param \stdClass $response
     * @param array $calledTools    
     * @param array $executedTools
     */
    public function __construct(
        \stdClass $response,
        array $calledTools,
        array $executedTools
    ) {
        $this->response = $response;
        $this->calledTools = $calledTools;
        $this->executedTools = $executedTools;
    }
}