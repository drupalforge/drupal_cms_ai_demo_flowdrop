<?php

namespace Drupal\Tests\mcp_client\Unit\Plugin\tool\Tool\Derivative;

use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\mcp_client\Plugin\tool\Tool\Derivative\McpToolDeriver;
use Drupal\Tests\UnitTestCase;
use Drupal\tool\Tool\ToolDefinition;
use Drupal\tool\TypedData\InputDefinition;

/**
 * Tests the McpToolDeriver class.
 *
 * @group mcp_client
 * @coversDefaultClass \Drupal\mcp_client\Plugin\tool\Tool\Derivative\McpToolDeriver
 */
class McpToolDeriverTest extends UnitTestCase {

  /**
   * The entity type manager mock.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The entity storage mock.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityStorage;

  /**
   * The deriver under test.
   *
   * @var \Drupal\mcp_client\Plugin\tool\Tool\Derivative\McpToolDeriver
   */
  protected $deriver;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up string translation for TranslatableMarkup.
    $string_translation = $this->createMock('Drupal\Core\StringTranslation\TranslationInterface');
    $string_translation->method('translateString')
      ->willReturnCallback(function ($arg) {
        return $arg->getUntranslatedString();
      });
    $container = new Container();
    $container->set('string_translation', $string_translation);
    \Drupal::setContainer($container);

    // Mock entity storage.
    $this->entityStorage = $this->createMock(EntityStorageInterface::class);

    // Mock entity type manager.
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->method('getStorage')
      ->with('mcp_server')
      ->willReturn($this->entityStorage);

    // Create the deriver.
    $this->deriver = new McpToolDeriver($this->entityTypeManager);
  }

  /**
   * Tests getDerivativeDefinitions with no MCP servers.
   *
   * @covers ::getDerivativeDefinitions
   */
  public function testGetDerivativeDefinitionsWithNoServers(): void {
    // Mock empty server list.
    $this->entityStorage->method('loadMultiple')
      ->willReturn([]);

    $base_definition = $this->createBaseDefinition();
    $derivatives = $this->deriver->getDerivativeDefinitions($base_definition);

    $this->assertEmpty($derivatives);
  }

  /**
   * Tests getDerivativeDefinitions with disabled server.
   *
   * @covers ::getDerivativeDefinitions
   */
  public function testGetDerivativeDefinitionsWithDisabledServer(): void {
    $server = $this->createMockServer('test_server', FALSE, [], []);

    $this->entityStorage->method('loadMultiple')
      ->willReturn(['test_server' => $server]);

    $base_definition = $this->createBaseDefinition();
    $derivatives = $this->deriver->getDerivativeDefinitions($base_definition);

    $this->assertEmpty($derivatives);
  }

  /**
   * Tests getDerivativeDefinitions with enabled server and tools.
   *
   * @covers ::getDerivativeDefinitions
   */
  public function testGetDerivativeDefinitionsWithEnabledServerAndTools(): void {
    $tools = [
      'list_files' => [
        'name' => 'list_files',
        'description' => 'List files in a directory',
        'inputSchema' => [
          'properties' => [
            'path' => [
              'type' => 'string',
              'description' => 'Directory path',
              'required' => TRUE,
            ],
            'limit' => [
              'type' => 'integer',
              'description' => 'Maximum number of files',
              'required' => FALSE,
              'default_value' => 100,
            ],
          ],
        ],
      ],
      'read_file' => [
        'name' => 'read_file',
        'description' => 'Read file contents',
        'inputSchema' => [
          'properties' => [
            'path' => [
              'type' => 'string',
              'description' => 'File path',
              'required' => TRUE,
            ],
          ],
        ],
      ],
    ];

    $enabled_tools = ['list_files', 'read_file'];
    $server = $this->createMockServer('filesystem', TRUE, $tools, $enabled_tools);

    $this->entityStorage->method('loadMultiple')
      ->willReturn(['filesystem' => $server]);

    $base_definition = $this->createBaseDefinition();
    $derivatives = $this->deriver->getDerivativeDefinitions($base_definition);

    // Should have 2 derivatives.
    $this->assertCount(2, $derivatives);

    // Check first derivative (list_files).
    $this->assertArrayHasKey('filesystem:list_files', $derivatives);
    $list_files = $derivatives['filesystem:list_files'];
    $this->assertEquals('List files in a directory', $list_files->getDescription());

    // Check input definitions for list_files.
    $input_definitions = $list_files->getInputDefinitions();
    $this->assertCount(2, $input_definitions);
    $this->assertArrayHasKey('path', $input_definitions);
    $this->assertArrayHasKey('limit', $input_definitions);
    $this->assertInstanceOf(InputDefinition::class, $input_definitions['path']);
    $this->assertEquals('string', $input_definitions['path']->getDataType());
    $this->assertEquals('integer', $input_definitions['limit']->getDataType());

    // Check second derivative (read_file).
    $this->assertArrayHasKey('filesystem:read_file', $derivatives);
    $read_file = $derivatives['filesystem:read_file'];
    $this->assertEquals('Read file contents', $read_file->getDescription());
  }

  /**
   * Tests getDerivativeDefinitions with partially enabled tools.
   *
   * @covers ::getDerivativeDefinitions
   */
  public function testGetDerivativeDefinitionsWithPartiallyEnabledTools(): void {
    $tools = [
      'tool1' => [
        'name' => 'tool1',
        'description' => 'Tool 1',
        'inputSchema' => ['properties' => []],
      ],
      'tool2' => [
        'name' => 'tool2',
        'description' => 'Tool 2',
        'inputSchema' => ['properties' => []],
      ],
      'tool3' => [
        'name' => 'tool3',
        'description' => 'Tool 3',
        'inputSchema' => ['properties' => []],
      ],
    ];

    // Only tool1 and tool3 are enabled.
    $enabled_tools = ['tool1', 'tool3'];
    $server = $this->createMockServer('test_server', TRUE, $tools, $enabled_tools);

    $this->entityStorage->method('loadMultiple')
      ->willReturn(['test_server' => $server]);

    $base_definition = $this->createBaseDefinition();
    $derivatives = $this->deriver->getDerivativeDefinitions($base_definition);

    // Should only have 2 derivatives (tool2 is not enabled).
    $this->assertCount(2, $derivatives);
    $this->assertArrayHasKey('test_server:tool1', $derivatives);
    $this->assertArrayNotHasKey('test_server:tool2', $derivatives);
    $this->assertArrayHasKey('test_server:tool3', $derivatives);
  }

  /**
   * Tests type mapping for different MCP types.
   *
   * @covers ::getDerivativeDefinitions
   */
  public function testTypeMappingForDifferentMcpTypes(): void {
    $tools = [
      'test_tool' => [
        'name' => 'test_tool',
        'description' => 'Test tool with various types',
        'inputSchema' => [
          'properties' => [
            'string_param' => ['type' => 'string', 'description' => 'String parameter'],
            'number_param' => ['type' => 'number', 'description' => 'Number parameter'],
            'integer_param' => ['type' => 'integer', 'description' => 'Integer parameter'],
            'boolean_param' => ['type' => 'boolean', 'description' => 'Boolean parameter'],
            'array_param' => ['type' => 'array', 'description' => 'Array parameter'],
            'object_param' => ['type' => 'object', 'description' => 'Object parameter'],
          ],
        ],
      ],
    ];

    $enabled_tools = ['test_tool'];
    $server = $this->createMockServer('test_server', TRUE, $tools, $enabled_tools);

    $this->entityStorage->method('loadMultiple')
      ->willReturn(['test_server' => $server]);

    $base_definition = $this->createBaseDefinition();
    $derivatives = $this->deriver->getDerivativeDefinitions($base_definition);

    $this->assertCount(1, $derivatives);
    $tool_definition = $derivatives['test_server:test_tool'];
    $input_definitions = $tool_definition->getInputDefinitions();

    // Verify type mappings.
    $this->assertEquals('string', $input_definitions['string_param']->getDataType());
    $this->assertEquals('float', $input_definitions['number_param']->getDataType());
    $this->assertEquals('integer', $input_definitions['integer_param']->getDataType());
    $this->assertEquals('boolean', $input_definitions['boolean_param']->getDataType());
    $this->assertEquals('list', $input_definitions['array_param']->getDataType());
    $this->assertEquals('string', $input_definitions['object_param']->getDataType());
  }

  /**
   * Tests getDerivativeDefinitions with array base definition.
   *
   * @covers ::getDerivativeDefinitions
   */
  public function testGetDerivativeDefinitionsWithArrayBaseDefinition(): void {
    $tools = [
      'simple_tool' => [
        'name' => 'simple_tool',
        'description' => 'Simple tool',
        'inputSchema' => ['properties' => []],
      ],
    ];

    $enabled_tools = ['simple_tool'];
    $server = $this->createMockServer('test_server', TRUE, $tools, $enabled_tools);

    $this->entityStorage->method('loadMultiple')
      ->willReturn(['test_server' => $server]);

    // Pass array instead of ToolDefinition.
    $base_definition = [
      'id' => 'mcp_tool',
      'class' => 'Drupal\mcp_client\Plugin\tool\Tool\McpToolBase',
    ];

    $derivatives = $this->deriver->getDerivativeDefinitions($base_definition);

    $this->assertCount(1, $derivatives);
    $this->assertArrayHasKey('test_server:simple_tool', $derivatives);
  }

  /**
   * Tests server with no enabled tools.
   *
   * @covers ::getDerivativeDefinitions
   */
  public function testGetDerivativeDefinitionsWithNoEnabledTools(): void {
    $tools = [
      'tool1' => [
        'name' => 'tool1',
        'description' => 'Tool 1',
        'inputSchema' => ['properties' => []],
      ],
    ];

    // Empty enabled tools array.
    $enabled_tools = [];
    $server = $this->createMockServer('test_server', TRUE, $tools, $enabled_tools);

    $this->entityStorage->method('loadMultiple')
      ->willReturn(['test_server' => $server]);

    $base_definition = $this->createBaseDefinition();
    $derivatives = $this->deriver->getDerivativeDefinitions($base_definition);

    $this->assertEmpty($derivatives);
  }

  /**
   * Tests server with no tools defined.
   *
   * @covers ::getDerivativeDefinitions
   */
  public function testGetDerivativeDefinitionsWithNoTools(): void {
    // Empty tools array.
    $tools = [];
    $enabled_tools = ['tool1'];
    $server = $this->createMockServer('test_server', TRUE, $tools, $enabled_tools);

    $this->entityStorage->method('loadMultiple')
      ->willReturn(['test_server' => $server]);

    $base_definition = $this->createBaseDefinition();
    $derivatives = $this->deriver->getDerivativeDefinitions($base_definition);

    $this->assertEmpty($derivatives);
  }

  /**
   * Tests multiple servers with tools.
   *
   * @covers ::getDerivativeDefinitions
   */
  public function testGetDerivativeDefinitionsWithMultipleServers(): void {
    $server1_tools = [
      'tool1' => [
        'name' => 'tool1',
        'description' => 'Server 1 Tool 1',
        'inputSchema' => ['properties' => []],
      ],
    ];

    $server2_tools = [
      'tool2' => [
        'name' => 'tool2',
        'description' => 'Server 2 Tool 2',
        'inputSchema' => ['properties' => []],
      ],
    ];

    $server1 = $this->createMockServer('server1', TRUE, $server1_tools, ['tool1']);
    $server2 = $this->createMockServer('server2', TRUE, $server2_tools, ['tool2']);

    $this->entityStorage->method('loadMultiple')
      ->willReturn([
        'server1' => $server1,
        'server2' => $server2,
      ]);

    $base_definition = $this->createBaseDefinition();
    $derivatives = $this->deriver->getDerivativeDefinitions($base_definition);

    // Should have 2 derivatives from 2 different servers.
    $this->assertCount(2, $derivatives);
    $this->assertArrayHasKey('server1:tool1', $derivatives);
    $this->assertArrayHasKey('server2:tool2', $derivatives);
    $this->assertEquals('Server 1 Tool 1', $derivatives['server1:tool1']->getDescription());
    $this->assertEquals('Server 2 Tool 2', $derivatives['server2:tool2']->getDescription());
  }

  /**
   * Creates a base ToolDefinition for testing.
   *
   * @return \Drupal\tool\Tool\ToolDefinition
   *   The base definition.
   */
  protected function createBaseDefinition(): ToolDefinition {
    return new ToolDefinition([
      'id' => 'mcp_tool',
      'label' => new TranslatableMarkup('MCP Tool'),
      'description' => new TranslatableMarkup('MCP Tool Base'),
      'class' => 'Drupal\mcp_client\Plugin\tool\Tool\McpToolBase',
    ]);
  }

  /**
   * Creates a mock MCP server entity.
   *
   * @param string $id
   *   The server ID.
   * @param bool $status
   *   Whether the server is enabled.
   * @param array<string, mixed> $tools
   *   The tools configuration.
   * @param array<string> $enabled_tools
   *   The list of enabled tool names.
   *
   * @return object
   *   A test double for MCP server.
   */
  protected function createMockServer(string $id, bool $status, array $tools, array $enabled_tools) {
    return new class($id, $status, $tools, $enabled_tools) {

      /**
       * The constructor.
       *
       * @param string $id
       *   Server ID.
       * @param bool $status
       *   Server status.
       * @param array<string, mixed> $tools
       *   Tools configuration.
       * @param array<string> $enabled_tools
       *   Enabled tool names.
       */
      public function __construct(
        private string $id,
        private bool $status,
        private array $tools,
        private array $enabled_tools,
      ) {}

      /**
       * Mocks the get() method to return field values.
       *
       * @param string $key
       *   The field key.
       *
       * @return mixed
       *   The field value.
       */
      public function get(string $key): mixed {
        return match ($key) {
          'id' => $this->id,
          'status' => $this->status,
          'tools' => $this->tools,
          'enabled_tools' => $this->enabled_tools,
          default => NULL,
        };
      }

    };
  }

}
