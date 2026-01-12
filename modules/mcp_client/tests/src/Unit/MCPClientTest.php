<?php

namespace Drupal\Tests\mcp_client\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\mcp_client\MCPClient;
use Swis\McpClient\Client as SwisClient;
use Swis\McpClient\Results\ListToolsResult;
use Swis\McpClient\Results\CallToolResult;
use Swis\McpClient\Schema\Tool;
use Swis\McpClient\Schema\Content\TextContent;

/**
 * Tests the MCPClient class.
 *
 * @group mcp_client
 * @coversDefaultClass \Drupal\mcp_client\MCPClient
 */
class MCPClientTest extends UnitTestCase {

  /**
   * The MCP client under test.
   *
   * @var \Drupal\mcp_client\MCPClient
   */
  protected $mcpClient;

  /**
   * The mocked SwisClient.
   *
   * @var \Swis\McpClient\Client|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $mockSwisClient;

  /**
   * Creates a mocked MCPClient with a mocked SwisClient.
   *
   * @return \Drupal\mcp_client\MCPClient
   *   The mocked client.
   */
  protected function createMockClient(): MCPClient {
    // Create a mock SwisClient.
    $this->mockSwisClient = $this->createMock(SwisClient::class);

    // Mock the connect method - it returns void.
    $this->mockSwisClient->method('connect');

    // Create MCPClient and inject the mock.
    $mcpClient = $this->getMockBuilder(MCPClient::class)
      ->setConstructorArgs(['https://example.com/api', 'http'])
      ->onlyMethods(['initializeClient'])
      ->getMock();

    // Use reflection to set the protected client property.
    $reflection = new \ReflectionClass($mcpClient);
    $property = $reflection->getProperty('client');
    $property->setAccessible(TRUE);
    $property->setValue($mcpClient, $this->mockSwisClient);

    return $mcpClient;
  }

  /**
   * Tests the listTools method.
   *
   * @covers ::listTools
   */
  public function testListTools(): void {
    $this->mcpClient = $this->createMockClient();

    // Create mock tools.
    $tool1 = $this->createMock(Tool::class);
    $tool1->method('getName')->willReturn('text-generator');
    $tool1->method('getDescription')->willReturn('Generates text based on provided prompt');
    $tool1->method('getSchema')->willReturn(['type' => 'object']);

    $tool2 = $this->createMock(Tool::class);
    $tool2->method('getName')->willReturn('image-analyzer');
    $tool2->method('getDescription')->willReturn('Analyzes images and extracts information');
    $tool2->method('getSchema')->willReturn(['type' => 'object']);

    // Create mock result.
    $mockResult = $this->createMock(ListToolsResult::class);
    $mockResult->method('getTools')->willReturn([$tool1, $tool2]);

    // Set up the mock to return our result.
    $this->mockSwisClient->expects($this->once())
      ->method('listTools')
      ->willReturn($mockResult);

    $tools = $this->mcpClient->listTools();

    // Verify response was processed correctly.
    $this->assertCount(2, $tools);
    $this->assertEquals('text-generator', $tools[0]['name']);
    $this->assertEquals('Generates text based on provided prompt', $tools[0]['description']);
    $this->assertEquals(['type' => 'object'], $tools[0]['inputSchema']);

    $this->assertEquals('image-analyzer', $tools[1]['name']);
    $this->assertEquals('Analyzes images and extracts information', $tools[1]['description']);
  }

  /**
   * Tests the executeTool method.
   *
   * @covers ::executeTool
   */
  public function testExecuteTool(): void {
    $this->mcpClient = $this->createMockClient();

    // Create mock content item.
    $mockContentItem = $this->createMock(TextContent::class);
    $mockContentItem->method('getText')->willReturn('This is generated text from the tool execution');

    // Create mock result.
    $mockResult = $this->createMock(CallToolResult::class);
    $mockResult->method('getContent')->willReturn([$mockContentItem]);
    $mockResult->method('isError')->willReturn(FALSE);

    $params = [
      'prompt' => 'Generate a short poem about coding',
      'max_length' => 100,
    ];

    // Set up the mock to return our result.
    $this->mockSwisClient->expects($this->once())
      ->method('callTool')
      ->willReturn($mockResult);

    $result = $this->mcpClient->executeTool('text-generator', $params);

    // Verify response.
    $this->assertFalse($result['isError']);
    $this->assertCount(1, $result['content']);
    $this->assertEquals('text', $result['content'][0]['type']);
    $this->assertEquals('This is generated text from the tool execution', $result['content'][0]['text']);
  }

}
