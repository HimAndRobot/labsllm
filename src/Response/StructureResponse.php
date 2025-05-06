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
     * @param array $functionCalls
     * @param array $calledTools
     */
    public function __construct(
        \stdClass $response,
        array $functionCalls,
        array $calledTools
    ) {
        $this->response = $response;
        $this->functionCalls = $functionCalls;
        $this->calledTools = $calledTools;
    }
}