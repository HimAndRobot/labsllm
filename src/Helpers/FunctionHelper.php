<?php

namespace LabsLLM\Helpers;

use LabsLLM\Contracts\ParameterInterface;
use PhpParser\Node\NullableType;

class FunctionHelper
{

    /**
     * @var string
     */
    protected string $name;

    /**
     * @var string
     */
    protected string $description;

    /**
     * @var array<string>
     */
    protected array $parameters;

    /**
     * @var array<ParameterInterface>
     */

    protected array $requiredParameters;

    /**
     * @var \Closure
     */
    protected \Closure $function;


    /**
     * @param string $name
     * @param string $description
     */
    public function __construct(string $name, string $description)
    {
        $this->name = $name;
        $this->description = $description;
    }

    /**
     * @param string $name
     * @param string $description
     * @return FunctionHelper
     */
    public static function create(string $name, string $description): FunctionHelper
    {
        $instance = new self($name, $description);
        return $instance;
    }

    /**
     * @param array<ParameterInterface> $parameters
     * @param array<string> $requiredParameters
     * @return FunctionHelper
     */
    public function withParameter(array $parameters, array $requiredParameters = []): FunctionHelper
    {
        $this->parameters = $parameters;
        $this->requiredParameters = $requiredParameters;
        return $this;
    }

    /**
     * @param \Closure $function
     * @return FunctionHelper
     */
    public function callable(\Closure $function): FunctionHelper
    {
        $this->function = $function;
        return $this;
    }

    /**
     * @return array
     */
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

    /**
     * @param array<mixed> $arguments
     * @return array<string, mixed>
     */
    public function execute(array $arguments): string
    {
        $response = ($this->function)(...$arguments);
        return is_string($response) ? $response : json_encode($response);
    }

    /**
     * Summary of getName
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}