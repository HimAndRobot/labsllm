<?php

namespace LabsLLM\Response;

use LabsLLM\Contracts\ResponseInterface;

abstract class BaseResponse implements ResponseInterface
{

    /**
     * @var array
     */
    public $functionCalls = [];

    /**
     * @var array
     */
    public $calledTools = [];

}