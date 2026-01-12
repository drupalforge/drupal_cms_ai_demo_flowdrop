<?php

namespace Drupal\Tests\mcp_client\Kernel\Plugin\tool\Tool;

use Drupal\KernelTests\KernelTestBase;
use Drupal\mcp_client\Entity\McpServer;
use Drupal\tool\Tool\ToolDefinition;

/**
 * Kernel tests for MCP Tool integration.
 *
 * @group mcp_client
 */
class McpToolIntegrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'key',
    'mcp_client',
    'tool',
  ];

  /**
   * The tool manager.
   *
   * @var \Drupal\tool\Tool\ToolManager
   */
  protected $toolManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['mcp_client']);
    $this->installEntitySchema('user');

    $this->toolManager = $this->container->get('plugin.manager.tool');
  }

  /**
   * Tests that MCP tools are discovered by ToolManager.
   */
  public function testMcpToolsAreDiscoveredByToolManager(): void {
    // Create a test MCP server with tools.
    $server = McpServer::create([
      'id' => 'test_filesystem',
      'label' => 'Test Filesystem Server',
      'status' => TRUE,
      'transport' => 'stdio',
      'command' => 'npx',
      'args' => ['-y', '@modelcontextprotocol/server-filesystem', '/tmp'],
      'env' => [],
      'tools' => [
        'read_file' => [
          'name' => 'read_file',
          'description' => 'Read the complete contents of a file',
          'inputSchema' => [
            'type' => 'object',
            'properties' => [
              'path' => [
                'type' => 'string',
                'description' => 'Path to the file to read',
                'required' => TRUE,
              ],
            ],
            'required' => ['path'],
          ],
        ],
        'list_directory' => [
          'name' => 'list_directory',
          'description' => 'List contents of a directory',
          'inputSchema' => [
            'type' => 'object',
            'properties' => [
              'path' => [
                'type' => 'string',
                'description' => 'Path to directory',
                'required' => TRUE,
              ],
            ],
            'required' => ['path'],
          ],
        ],
      ],
      'enabled_tools' => ['read_file', 'list_directory'],
    ]);
    $server->save();

    // Clear plugin cache to ensure discovery.
    $this->toolManager->clearCachedDefinitions();

    // Get all tool definitions.
    $definitions = $this->toolManager->getDefinitions();

    // Verify our MCP tools are discovered.
    $this->assertArrayHasKey('mcp_tool:test_filesystem:read_file', $definitions);
    $this->assertArrayHasKey('mcp_tool:test_filesystem:list_directory', $definitions);

    // Verify they are ToolDefinition objects.
    $read_file_def = $definitions['mcp_tool:test_filesystem:read_file'];
    $this->assertInstanceOf(ToolDefinition::class, $read_file_def);

    // Verify tool properties.
    $this->assertEquals('Read the complete contents of a file', $read_file_def->getDescription());

    // Verify input definitions.
    $input_definitions = $read_file_def->getInputDefinitions();
    $this->assertArrayHasKey('path', $input_definitions);
    $this->assertEquals('string', $input_definitions['path']->getDataType());
  }

  /**
   * Tests that disabled servers are not discovered.
   */
  public function testDisabledServersAreNotDiscovered(): void {
    // Create a disabled MCP server.
    $server = McpServer::create([
      'id' => 'disabled_server',
      'label' => 'Disabled Server',
      'status' => FALSE,
      'transport' => 'stdio',
      'command' => 'test',
      'args' => [],
      'env' => [],
      'tools' => [
        'test_tool' => [
          'name' => 'test_tool',
          'description' => 'Test tool',
          'inputSchema' => [
            'type' => 'object',
            'properties' => [],
          ],
        ],
      ],
      'enabled_tools' => ['test_tool'],
    ]);
    $server->save();

    // Clear plugin cache.
    $this->toolManager->clearCachedDefinitions();

    // Get all tool definitions.
    $definitions = $this->toolManager->getDefinitions();

    // Verify disabled server's tools are NOT discovered.
    $this->assertArrayNotHasKey('mcp_tool:disabled_server:test_tool', $definitions);
  }

  /**
   * Tests that only enabled tools are discovered.
   */
  public function testOnlyEnabledToolsAreDiscovered(): void {
    // Create server with some tools enabled, some not.
    $server = McpServer::create([
      'id' => 'partial_server',
      'label' => 'Partial Server',
      'status' => TRUE,
      'transport' => 'stdio',
      'command' => 'test',
      'args' => [],
      'env' => [],
      'tools' => [
        'enabled_tool' => [
          'name' => 'enabled_tool',
          'description' => 'Enabled tool',
          'inputSchema' => [
            'type' => 'object',
            'properties' => [],
          ],
        ],
        'disabled_tool' => [
          'name' => 'disabled_tool',
          'description' => 'Disabled tool',
          'inputSchema' => [
            'type' => 'object',
            'properties' => [],
          ],
        ],
      ],
      'enabled_tools' => ['enabled_tool'],
    ]);
    $server->save();

    // Clear plugin cache.
    $this->toolManager->clearCachedDefinitions();

    // Get all tool definitions.
    $definitions = $this->toolManager->getDefinitions();

    // Verify only enabled tool is discovered.
    $this->assertArrayHasKey('mcp_tool:partial_server:enabled_tool', $definitions);
    $this->assertArrayNotHasKey('mcp_tool:partial_server:disabled_tool', $definitions);
  }

  /**
   * Tests that MCP tool can be instantiated.
   */
  public function testMcpToolCanBeInstantiated(): void {
    // Create a test MCP server.
    $server = McpServer::create([
      'id' => 'test_server',
      'label' => 'Test Server',
      'status' => TRUE,
      'transport' => 'stdio',
      'command' => 'test',
      'args' => [],
      'env' => [],
      'tools' => [
        'simple_tool' => [
          'name' => 'simple_tool',
          'description' => 'Simple test tool',
          'inputSchema' => [
            'type' => 'object',
            'properties' => [
              'message' => [
                'type' => 'string',
                'description' => 'A message',
                'required' => FALSE,
              ],
            ],
          ],
        ],
      ],
      'enabled_tools' => ['simple_tool'],
    ]);
    $server->save();

    // Clear plugin cache.
    $this->toolManager->clearCachedDefinitions();

    // Try to instantiate the tool plugin.
    $tool = $this->toolManager->createInstance('mcp_tool:test_server:simple_tool');

    $this->assertInstanceOf('\Drupal\tool\Tool\ToolInterface', $tool);
  }

  /**
   * Tests type mapping from MCP to Drupal types.
   */
  public function testTypeMappingInDiscoveredTools(): void {
    // Create server with tools using different types.
    $server = McpServer::create([
      'id' => 'type_test_server',
      'label' => 'Type Test Server',
      'status' => TRUE,
      'transport' => 'stdio',
      'command' => 'test',
      'args' => [],
      'env' => [],
      'tools' => [
        'type_test_tool' => [
          'name' => 'type_test_tool',
          'description' => 'Tool with various types',
          'inputSchema' => [
            'type' => 'object',
            'properties' => [
              'string_param' => [
                'type' => 'string',
                'description' => 'String param',
              ],
              'number_param' => [
                'type' => 'number',
                'description' => 'Number param',
              ],
              'integer_param' => [
                'type' => 'integer',
                'description' => 'Integer param',
              ],
              'boolean_param' => [
                'type' => 'boolean',
                'description' => 'Boolean param',
              ],
              'array_param' => [
                'type' => 'array',
                'description' => 'Array param',
              ],
              'object_param' => [
                'type' => 'object',
                'description' => 'Object param',
              ],
            ],
          ],
        ],
      ],
      'enabled_tools' => ['type_test_tool'],
    ]);
    $server->save();

    // Clear plugin cache.
    $this->toolManager->clearCachedDefinitions();

    // Get the tool definition.
    $definitions = $this->toolManager->getDefinitions();
    $this->assertArrayHasKey('mcp_tool:type_test_server:type_test_tool', $definitions);

    $tool_def = $definitions['mcp_tool:type_test_server:type_test_tool'];
    $input_defs = $tool_def->getInputDefinitions();

    // Verify type mappings.
    $this->assertEquals('string', $input_defs['string_param']->getDataType());
    $this->assertEquals('float', $input_defs['number_param']->getDataType());
    $this->assertEquals('integer', $input_defs['integer_param']->getDataType());
    $this->assertEquals('boolean', $input_defs['boolean_param']->getDataType());
    $this->assertEquals('list', $input_defs['array_param']->getDataType());
    $this->assertEquals('string', $input_defs['object_param']->getDataType());
  }

  /**
   * Tests that updating a server triggers cache clear.
   */
  public function testServerUpdateTriggersDiscovery(): void {
    // Create server with one tool.
    $server = McpServer::create([
      'id' => 'dynamic_server',
      'label' => 'Dynamic Server',
      'status' => TRUE,
      'transport' => 'stdio',
      'command' => 'test',
      'args' => [],
      'env' => [],
      'tools' => [
        'tool1' => [
          'name' => 'tool1',
          'description' => 'Tool 1',
          'inputSchema' => ['type' => 'object', 'properties' => []],
        ],
        'tool2' => [
          'name' => 'tool2',
          'description' => 'Tool 2',
          'inputSchema' => ['type' => 'object', 'properties' => []],
        ],
      ],
      'enabled_tools' => ['tool1'],
    ]);
    $server->save();

    // Clear cache and verify only tool1.
    $this->toolManager->clearCachedDefinitions();
    $definitions = $this->toolManager->getDefinitions();
    $this->assertArrayHasKey('mcp_tool:dynamic_server:tool1', $definitions);
    $this->assertArrayNotHasKey('mcp_tool:dynamic_server:tool2', $definitions);

    // Update server to enable tool2.
    $server->set('enabled_tools', ['tool1', 'tool2']);
    $server->save();

    // Clear cache and verify both tools.
    $this->toolManager->clearCachedDefinitions();
    $definitions = $this->toolManager->getDefinitions();
    $this->assertArrayHasKey('mcp_tool:dynamic_server:tool1', $definitions);
    $this->assertArrayHasKey('mcp_tool:dynamic_server:tool2', $definitions);
  }

  /**
   * Tests multiple servers can coexist.
   */
  public function testMultipleServersCoexist(): void {
    // Create two servers.
    $server1 = McpServer::create([
      'id' => 'server1',
      'label' => 'Server 1',
      'status' => TRUE,
      'transport' => 'stdio',
      'command' => 'test',
      'args' => [],
      'env' => [],
      'tools' => [
        'tool_a' => [
          'name' => 'tool_a',
          'description' => 'Tool A',
          'inputSchema' => ['type' => 'object', 'properties' => []],
        ],
      ],
      'enabled_tools' => ['tool_a'],
    ]);
    $server1->save();

    $server2 = McpServer::create([
      'id' => 'server2',
      'label' => 'Server 2',
      'status' => TRUE,
      'transport' => 'stdio',
      'command' => 'test',
      'args' => [],
      'env' => [],
      'tools' => [
        'tool_b' => [
          'name' => 'tool_b',
          'description' => 'Tool B',
          'inputSchema' => ['type' => 'object', 'properties' => []],
        ],
      ],
      'enabled_tools' => ['tool_b'],
    ]);
    $server2->save();

    // Clear plugin cache.
    $this->toolManager->clearCachedDefinitions();

    // Verify both servers' tools are discovered.
    $definitions = $this->toolManager->getDefinitions();
    $this->assertArrayHasKey('mcp_tool:server1:tool_a', $definitions);
    $this->assertArrayHasKey('mcp_tool:server2:tool_b', $definitions);
  }

}
