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

}