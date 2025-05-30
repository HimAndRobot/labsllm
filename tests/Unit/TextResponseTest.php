<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use LabsLLM\Response\TextResponse;

class TextResponseTest extends TestCase
{
    /**
     * Test basic creation of a TextResponse instance
     */
    public function testCreateTextResponse(): void
    {
        $response = new TextResponse('Resposta de texto', [], []);
        
        $this->assertInstanceOf(TextResponse::class, $response);
        $this->assertEquals('Resposta de texto', $response->response);
        $this->assertIsArray($response->calledTools);
        $this->assertIsArray($response->executedTools);
        $this->assertEmpty($response->calledTools);
        $this->assertEmpty($response->executedTools);
    }
    
    /**
     * Test response with function calls and called tools
     */
    public function testTextResponseWithTools(): void
    {
        $functionCalls = [
            [
                'name' => 'getDate',
                'arguments' => [],
                'id' => 'call_123'
            ]
        ];
        
        $calledTools = [
            [
                'name' => 'getDate',
                'arguments' => [],
                'response' => ['response' => 'Today is 2023-12-31'],
                'id' => 'call_123'
            ]
        ];
        
        $response = new TextResponse('Aqui está a data', $functionCalls, $calledTools);
        
        $this->assertEquals('Aqui está a data', $response->response);
        $this->assertEquals($functionCalls, $response->calledTools);
        $this->assertEquals($calledTools, $response->executedTools);
        $this->assertCount(1, $response->calledTools);
        $this->assertCount(1, $response->executedTools);
    }
    
    /**
     * Test response with function parameters
     */
    public function testTextResponseWithFunctionParameters(): void
    {
        $functionCalls = [
            [
                'name' => 'getDateOrTime',
                'arguments' => ['type' => 'time'],
                'id' => 'call_456'
            ]
        ];
        
        $calledTools = [
            [
                'name' => 'getDateOrTime',
                'arguments' => ['type' => 'time'],
                'response' => ['response' => 'Current time is 15:30:00'],
                'id' => 'call_456'
            ]
        ];
        
        $response = new TextResponse('Veja o horário atual', $functionCalls, $calledTools);
        
        $this->assertEquals('Veja o horário atual', $response->response);
        $this->assertEquals('getDateOrTime', $response->calledTools[0]['name']);
        $this->assertEquals('time', $response->calledTools[0]['arguments']['type']);
        $this->assertEquals('Current time is 15:30:00', $response->executedTools[0]['response']['response']);
    }
} 