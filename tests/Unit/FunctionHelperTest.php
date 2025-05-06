<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use LabsLLM\Helpers\FunctionHelper;
use LabsLLM\Parameters\StringParameter;

class FunctionHelperTest extends TestCase
{
    /**
     * Test creating a simple function without parameters
     */
    public function testCreateFunctionWithoutParameters(): void
    {
        $function = FunctionHelper::create('getDate', 'Get the current date')
            ->callable(function(array $arguments) {
                return 'Today is ' . date('Y-m-d');
            });
        
        $this->assertInstanceOf(FunctionHelper::class, $function);
        $this->assertEquals('getDate', $function->getName());
        
        // Test the function array structure
        $functionArray = $function->toArray();
        $this->assertEquals('getDate', $functionArray['function']['name']);
        $this->assertEquals('Get the current date', $functionArray['function']['description']);
        $this->assertEquals('function', $functionArray['type']);
        $this->assertArrayNotHasKey('parameters', $functionArray['function']);
    }
    
    /**
     * Test creating a function with parameters
     */
    public function testCreateFunctionWithParameters(): void
    {
        $function = FunctionHelper::create('getDateOrTime', 'Get date or time from the day')
            ->withParameter([
                new StringParameter('type', 'The type of data to get', ['date', 'time'])
            ], ['type'])
            ->callable(function(array $arguments) {
                return 'Test result';
            });
        
        $functionArray = $function->toArray();
        
        // Validate function has parameters
        $this->assertArrayHasKey('parameters', $functionArray['function']);
        
        // Validate parameters structure
        $parameters = $functionArray['function']['parameters'];
        $this->assertEquals('object', $parameters['type']);
        $this->assertArrayHasKey('properties', $parameters);
        $this->assertArrayHasKey('type', $parameters['properties']);
        
        // Validate required parameters
        $this->assertArrayHasKey('required', $parameters);
        $this->assertContains('type', $parameters['required']);
    }
    
    /**
     * Test function execution
     */
    public function testFunctionExecution(): void
    {
        $function = FunctionHelper::create('testFunction', 'A test function')
            ->callable(function() {
                return 'executed';
            });
        
        $result = $function->execute([]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('response', $result);
        $this->assertTrue($result['success']);
        $this->assertEquals('executed', $result['response']);
    }
    
    /**
     * Test function with parameters execution
     */
    public function testFunctionWithParametersExecution(): void
    {
        $function = FunctionHelper::create('testParamFunction', 'Test with params')
            ->withParameter([
                new StringParameter('param1', 'Test parameter')
            ])
            ->callable(function(string $param1) {
                return 'Value is: ' . $param1;
            });
        
        $result = $function->execute(['param1' => 'test_value']);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Value is: test_value', $result['response']);
    }
} 