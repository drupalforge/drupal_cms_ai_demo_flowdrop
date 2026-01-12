<?php

declare(strict_types=1);

namespace Drupal\mcp_client\Transporter;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use Swis\McpClient\Exceptions\ConnectionFailedException;
use Swis\McpClient\Requests\InitializeRequest;
use Swis\McpClient\Transporters\StreamableHttpTransporter;
use function React\Async\await;

/**
 * Extended StreamableHttpTransporter that supports custom HTTP headers.
 */
class CustomHeadersHttpTransporter extends StreamableHttpTransporter {

  /**
   * Custom headers to include in requests.
   *
   * @var array<string, string>
   */
  protected array $customHeaders = [];

  /**
   * Constructor.
   *
   * @param string $endpoint
   *   The endpoint URL.
   * @param array<string, string> $customHeaders
   *   Custom headers to include in requests.
   * @param \Psr\Log\LoggerInterface|null $logger
   *   Optional logger.
   * @param \React\EventLoop\LoopInterface|null $loop
   *   Optional event loop.
   */
  public function __construct(
    string $endpoint,
    array $customHeaders = [],
    ?LoggerInterface $logger = NULL,
    ?LoopInterface $loop = NULL,
  ) {
    parent::__construct($endpoint, $logger, $loop);
    $this->customHeaders = $customHeaders;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return array<string, string>
   */
  protected function getDefaultHeaders(): array {
    $headers = parent::getDefaultHeaders();

    return array_merge($headers, $this->customHeaders);
  }

  /**
   * {@inheritdoc}
   *
   * Override to fix premature event loop termination in Drupal context.
   *
   * The parent StreamableHttpTransporter::initializeConnection() registers
   * shutdown handlers that stop the ReactPHP event loop immediately after
   * initialization completes. This causes "Connection aborted early" errors
   * in Drupal because:
   *
   * 1. Drupal has its own request lifecycle and shutdown management
   * 2. The event loop stops before subsequent requests (like listTools) can
   *    complete their promise chains
   * 3. The await() call in ClientRequestTrait fails with an AssertionError
   *    because the callback hasn't been invoked yet
   *
   * This override removes the problematic shutdown handlers:
   * - register_shutdown_function(fn () => $this->loop->stop())
   * - register_shutdown_function([$this, 'disconnect'])
   *
   * For Streamable HTTP, we handle the connection ourselves via POST with
   * initialize request.
   *
   * Instead, we let Drupal manage the lifecycle naturally, with disconnect()
   * being called in MCPClient::__destruct() when the object is garbage
   * collected at the end of the request.
   *
   * @phpstan-param array<string, mixed> $capabilities
   * @phpstan-param array<string, mixed> $clientInfo
   * @phpstan-return array<string, mixed>
   *
   * @see \Swis\McpClient\Transporters\StreamableHttpTransporter::initializeConnection()
   * @see \Drupal\mcp_client\MCPClient::__destruct()
   */
  public function initializeConnection(EventDispatcherInterface $eventDispatcher, array $capabilities, array $clientInfo, string $protocolVersion): array {
    // Set request endpoint to initial endpoint for StreamableHTTP.
    $this->requestEndpoint = $this->initialEndpoint;

    $initRequest = new InitializeRequest(
      capabilities: $capabilities,
      clientInfo: $clientInfo,
      protocolVersion: $protocolVersion
    );

    try {
      $serverInfo = [];

      await(
        $this
          ->doSendRequest($initRequest)
          ->then(function (ResponseInterface $result) use (&$serverInfo) {
            // Use reflection to call private method.
            $reflectionMethod = new \ReflectionMethod(StreamableHttpTransporter::class, 'updateSessionIdFromResponse');
            $reflectionMethod->setAccessible(TRUE);
            $reflectionMethod->invoke($this, $result);

            $serverInfo = @json_decode((string) $result->getBody(), TRUE) ?: [];
          })
          // Don't register shutdown handlers here,
          // let Drupal manage lifecycle.
          ->then(fn () => $this->afterInitialization())
      );

      // Mark as connected BEFORE calling listen().
      $this->connected = TRUE;

      // Set the event dispatcher so responses can be dispatched properly.
      $this->listen($eventDispatcher);

      return $serverInfo;
    }
    catch (\Throwable $e) {
      throw new ConnectionFailedException('Failed to connect to MCP server', 0, $e);
    }
  }

}
