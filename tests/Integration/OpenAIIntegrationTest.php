<?php

namespace Tests\Integration;

use LabsLLM\LabsLLM;
use LabsLLM\Messages\Message;
use LabsLLM\Providers\OpenAI;
use PHPUnit\Framework\TestCase;
use LabsLLM\Messages\MessagesBag;
use LabsLLM\Helpers\FunctionHelper;
use LabsLLM\Parameters\NumberParameter;
use LabsLLM\Parameters\ObjectParameter;
use LabsLLM\Parameters\StringParameter;

/**
 * Integration tests that actually call the OpenAI API
 * 
 * IMPORTANT: These tests require a valid OpenAI API key to run.
 * They should not be run in every CI/CD build to avoid unnecessary costs.
 * 
 * @group integration
 */
class OpenAIIntegrationTest extends TestCase
{
    /**
     * API key for testing
     */
    private string $apiKey;
    
    /**
     * Set up the test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Get API key from environment or skip test
        $this->apiKey = getenv('OPENAI_API_KEY');
        if (!$this->apiKey) {
            $this->markTestSkipped('No OPENAI_API_KEY environment variable found');
        }
    }
    
    /**
     * Test a simple prompt to ensure the library can communicate with OpenAI
     */
    public function testBasicPrompt(): void
    {
        $execute = LabsLLM::text()
            ->using(new OpenAI($this->apiKey, model: 'gpt-4o-mini')) // Using cheaper model for tests
            ->executePrompt('Return only the number 42 without any other text');
            
        $response = $execute->getResponseData();
        
        $this->assertNotEmpty($response->response);
        $this->assertStringContainsString('42', $response->response);
    }
    
    /**
     * Test a function call to ensure the library can handle tool usage
     */
    public function testFunctionCall(): void
    {
        // Define a simple function that the AI is likely to call
        $dateFunction = FunctionHelper::create('getCurrentDate', 'Get the current date')
            ->callable(function() {
                return date('Y-m-d');
            });
        
        $execute = LabsLLM::text()
            ->using(new OpenAI($this->apiKey, 'gpt-4o-mini')) 
            ->addTool($dateFunction)
            ->withMaxSteps(2)
            ->executePrompt('What is today\'s date? Use the getCurrentDate function to find out.');
            
        $response = $execute->getResponseData();
        
        // Check that the function was called
        $this->assertNotEmpty($response->calledTools);
        $this->assertEquals('getCurrentDate', $response->calledTools[0]['name']);
        
        // Check that we got a meaningful response that includes today's date
        $this->assertNotEmpty($response->response);
        $this->assertStringContainsString(date('Y'), $response->response);
    }


    /**
     * Test a  prompt with structure response
     */
    public function testStructureResponse(): void
    {
        $execute = LabsLLM::text()
            ->using(new OpenAI($this->apiKey, model: 'gpt-4o-mini'))
            ->withOutputSchema(new ObjectParameter('response', 'The response to the user', [
                new StringParameter('name', 'The name of the user'),
                new NumberParameter('age', 'The age of the user'),
            ]))
            ->executePrompt('Return a JSON object with the keys "name" and "age"');

        $response = $execute->getStructureResponse();

        $this->assertNotEmpty($response->response);
        $this->assertObjectHasProperty('name', $response->response);
        $this->assertObjectHasProperty('age', $response->response);
    }
} 