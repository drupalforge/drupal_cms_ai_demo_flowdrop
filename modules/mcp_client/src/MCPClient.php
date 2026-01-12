<?php

declare(strict_types=1);

namespace Drupal\mcp_client;

use Drupal\mcp_client\Transporter\CustomHeadersHttpTransporter;
use Psr\Log\LoggerInterface;
use Swis\McpClient\Client as SwisClient;
use Swis\McpClient\EventDispatcher;
use Swis\McpClient\Requests\CallToolRequest;
use Swis\McpClient\Results\JsonRpcError;

/**
 * MCP Client wrapper around SwisNL MCP Client library.
 *
 * This class provides a backward-compatible interface while using the SwisNL
 * library internally. It supports multiple transport types: HTTP and STDIO.
 */
class MCPClient {

  /**
   * The SwisNL MCP client instance.
   *
   * @var \Swis\McpClient\Client
   */
  protected SwisClient $client;

  /**
   * The process instance (for STDIO transport).
   *
   * @var mixed
   */
  protected mixed $process = NULL;

  /**
   * The server endpoint URL.
   *
   * @var string
   */
  protected string $endpoint;

  /**
   * The transport type.
   *
   * @var string
   */
  protected string $transportType;

  /**
   * Constructor.
   *
   * @param string $endpoint
   *   The MCP server endpoint URL.
   * @param string $transportType
   *   The transport type (http, stdio).
   * @param array<string, mixed> $config
   *   Additional configuration for the transport.
   * @param \Psr\Log\LoggerInterface|null $logger
   *   Optional logger instance.
   */
  public function __construct(
    string $endpoint,
    string $transportType = 'http',
    array $config = [],
    ?LoggerInterface $logger = NULL,
  ) {
    $this->endpoint = rtrim($endpoint, '/');
    $this->transportType = $transportType;
    $this->initializeClient($config, $logger);
  }

  /**
   * Initialize the SwisNL client based on transport type.
   *
   * @param array<string, mixed> $config
   *   Configuration array.
   * @param \Psr\Log\LoggerInterface|null $logger
   *   Optional logger.
   *
   * @throws \InvalidArgumentException
   *   If transport type is not supported.
   */
  protected function initializeClient(array $config, ?LoggerInterface $logger): void {
    $this->client = match($this->transportType) {
      'http' => $this->createHttpClient($config, $logger),
      'stdio' => $this->createStdioClient($config, $logger),
      default => throw new \InvalidArgumentException("Unsupported transport type: {$this->transportType}"),
    };

    // Connect to the server.
    try {
      $this->client->connect();
    }
    catch (\Exception $e) {
      // For STDIO transport, try to capture stderr output for better
      // error messages.
      $stderrErrors = $this->getStderrErrors();
      $errorMessage = $e->getMessage();

      if (!empty($stderrErrors)) {
        $errorMessage .= '. Latest errors: ' . implode(' ', $stderrErrors);
      }

      $logger?->error('Failed to connect to MCP server at @endpoint using @transport: @message', [
        '@endpoint' => $this->endpoint,
        '@transport' => $this->transportType,
        '@message' => $errorMessage,
      ]);
      throw new \RuntimeException(
        "Failed to connect to MCP server at {$this->endpoint} using {$this->transportType} transport: {$errorMessage}",
        0,
        $e
      );
    }
  }

  /**
   * Create an HTTP transport client.
   *
   * @param array<string, mixed> $config
   *   Configuration array containing 'headers'.
   * @param \Psr\Log\LoggerInterface|null $logger
   *   Optional logger.
   *
   * @return \Swis\McpClient\Client
   *   The client instance.
   */
  protected function createHttpClient(array $config, ?LoggerInterface $logger): SwisClient {
    $headers = $config['headers'] ?? [];
    $transporter = new CustomHeadersHttpTransporter($this->endpoint, $headers, $logger);
    $eventDispatcher = new EventDispatcher();

    return new SwisClient($transporter, $eventDispatcher, $logger);
  }

  /**
   * Create a STDIO transport client.
   *
   * @param array<string, mixed> $config
   *   Configuration array containing 'command', 'env', 'cwd'.
   * @param \Psr\Log\LoggerInterface|null $logger
   *   Optional logger.
   *
   * @return \Swis\McpClient\Client
   *   The client instance.
   *
   * @throws \InvalidArgumentException
   *   If command is not provided.
   * @throws \RuntimeException
   *   If the process cannot be created.
   */
  protected function createStdioClient(array $config, ?LoggerInterface $logger): SwisClient {
    if (empty($config['command'])) {
      throw new \InvalidArgumentException('STDIO transport requires a command to be specified.');
    }

    try {
      [$client, $process] = SwisClient::withProcess(
        command: $config['command'],
        env: $config['env'] ?? [],
        cwd: $config['cwd'] ?? NULL,
        logger: $logger
      );

      // Store process reference to prevent garbage collection.
      $this->process = $process;

      return $client;
    }
    catch (\Exception $e) {
      $logger?->error('Failed to create STDIO process: @message', [
        '@message' => $e->getMessage(),
        'command' => $config['command'],
        'cwd' => $config['cwd'],
        'env_count' => count($config['env'] ?? []),
      ]);
      throw new \RuntimeException('Failed to create STDIO process: ' . $e->getMessage(), 0, $e);
    }
  }

  /**
   * Lists all available tools on the MCP server.
   *
   * @return array<int, array<string, mixed>>
   *   An array of tool information in the legacy format.
   *
   * @throws \RuntimeException
   *   If the request fails.
   */
  public function listTools(): array {
    $result = $this->client->listTools();

    if ($result instanceof JsonRpcError) {
      throw new \RuntimeException($result->getMessage());
    }

    // Convert SwisNL format to legacy format.
    $tools = [];
    foreach ($result->getTools() as $tool) {
      $tools[] = [
        'name' => $tool->getName(),
        'description' => (string) $tool->getDescription(),
        'inputSchema' => $tool->getSchema(),
      ];
    }

    return $tools;
  }

  /**
   * Executes a specific tool on the MCP server.
   *
   * @param string $toolId
   *   The ID of the tool to execute.
   * @param array<string, mixed>|\stdClass $parameters
   *   The parameters to pass to the tool.
   *
   * @return array<string, mixed>
   *   The tool execution result in legacy format.
   *
   * @throws \RuntimeException
   *   If the request fails.
   */
  public function executeTool(string $toolId, array|\stdClass $parameters = []): array {
    // Convert stdClass to array if needed.
    $arguments = is_array($parameters) ? $parameters : (array) $parameters;

    // Create the request.
    $request = new CallToolRequest(
      name: $toolId,
      arguments: $arguments
    );

    $result = $this->client->callTool($request);

    if ($result instanceof JsonRpcError) {
      throw new \RuntimeException($result->getMessage());
    }

    // Convert to legacy format - extract text from content objects.
    $content = [];
    foreach ($result->getContent() as $content_item) {
      if (method_exists($content_item, 'getText')) {
        $content[] = [
          'type' => 'text',
          'text' => $content_item->getText(),
        ];
      }
      elseif (method_exists($content_item, 'toArray')) {
        $content[] = $content_item->toArray();
      }
    }

    return [
      'content' => $content,
      'isError' => $result->isError(),
    ];
  }

  /**
   * Get stderr errors from the STDIO transporter.
   *
   * @return array<string>
   *   Array of stderr error messages, or empty array if not available.
   */
  protected function getStderrErrors(): array {
    // Only available for STDIO transport.
    if ($this->transportType !== 'stdio') {
      return [];
    }

    // Use reflection to access the protected transporter.
    try {
      $reflection = new \ReflectionClass($this->client);
      $property = $reflection->getProperty('transporter');
      $property->setAccessible(TRUE);
      $transporter = $property->getValue($this->client);

      // Check if transporter has getErrorBag method.
      if (is_object($transporter) && method_exists($transporter, 'getErrorBag')) {
        $errors = $transporter->getErrorBag();
        return is_array($errors) ? $errors : [];
      }
    }
    catch (\Exception $e) {
      // Silently fail if reflection doesn't work.
    }

    return [];
  }

  /**
   * Disconnect from the server.
   *
   * This should be called when the client is no longer needed.
   */
  public function disconnect(): void {
    $this->client->disconnect();
  }

  /**
   * Destructor - ensures client is disconnected.
   */
  public function __destruct() {
    $this->disconnect();
  }

}
