<?php

namespace ReviewSystem\Tests\Feature;

use ReviewSystem\Tests\TestCase;
use ReviewSystem\Engine\RuleRunner;
use ReviewSystem\Engine\ConfigurationLoader;

/**
 * Feature tests for CodeMentor AI system
 */
class ExampleTest extends TestCase
{
    private RuleRunner $runner;
    private ConfigurationLoader $configLoader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configLoader = new ConfigurationLoader();
        $this->runner = new RuleRunner($this->configLoader->getConfiguration());
    }

    /**
     * Test that the system can run a basic code review
     */
    public function test_basic_code_review_workflow(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

class TestController extends Controller
{
    public function index()
    {
        return response()->json([]);
    }
}';

        $tempFile = $this->createTempFile($code);
        
        $result = $this->runner->run(['rules' => ['ReviewSystem\Rules\CodeStyleRule']]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('violations', $result);
        $this->assertArrayHasKey('statistics', $result);
        
        unlink($tempFile);
    }

    /**
     * Test that the system can handle configuration loading
     */
    public function test_configuration_loading(): void
    {
        $config = $this->configLoader->getConfiguration();
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('rules', $config);
        $this->assertArrayHasKey('reporting', $config);
    }
}
