<?php

namespace LabsLLM\Helpers;

use LabsLLM\Contracts\ParameterInterface;

class FunctionHelper
{

    protected string $name;

    protected string $description;

    protected array $parameters;

    protected array $requiredParameters;

    protected \Closure $function;

    public function __construct(string $name, string $description)
    {
        $this->name = $name;
        $this->description = $description;
    }
    

    public static function create(string $name, string $description): FunctionHelper
    {
        $instance = new self($name, $description);
        return $instance;
    }


    /**
     * @param array<ParameterInterface> $parameters
     */
    public function withParameter(array $parameters, array $requiredParameters = []): FunctionHelper
    {
        $this->parameters = $parameters;
        $this->requiredParameters = $requiredParameters;
        return $this;
    }

    public function callable(\Closure $function): FunctionHelper
    {
        $this->function = $function;
        return $this;
    }

    public function toArray(): array
    {
        $properties = [];

        foreach ($this->parameters ?? [] as $parameter) {
            $properties[$parameter->getName()] = $parameter->mountBody();
        }
        

        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name,
                'description' => $this->description,
                ...(isset($this->parameters) ? [
                    'parameters' => [
                        'type' => 'object',
                        'properties' => $properties,
                        'required' => $this->requiredParameters ?? [],
                    ]
                ] : []),
            ],
        ];
    }
}