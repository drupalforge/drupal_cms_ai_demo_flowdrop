<?php

declare(strict_types=1);

namespace Drupal\mcp_client\Service;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\key\KeyRepositoryInterface;
use Drupal\mcp_client\Entity\McpServer;
use Drupal\mcp_client\MCPClient;
use Psr\Log\LoggerInterface;

/**
 * Factory service for creating MCP clients.
 *
 * This service creates MCPClient instances from MCP Server entities
 * or from direct configuration.
 */
class McpClientFactory {

  /**
   * Constructs a new McpClientFactory.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\key\KeyRepositoryInterface $keyRepository
   *   The key repository service.
   */
  public function __construct(
    protected LoggerInterface $logger,
    protected KeyRepositoryInterface $keyRepository,
  ) {}

  /**
   * Create a client from a server entity.
   *
   * @param \Drupal\mcp_client\Entity\McpServer $server
   *   The MCP Server entity.
   *
   * @return \Drupal\mcp_client\MCPClient
   *   The configured MCP client.
   *
   * @throws \InvalidArgumentException
   *   If the transport type is not supported or required config is missing.
   */
  public function createFromEntity(McpServer $server): MCPClient {
    $transportType = $server->get('transport_type') ?? 'http';
    $endpoint = $server->get('endpoint') ?? '';
    $httpHeaders = $server->get('http_headers') ?? [];

    $config = match($transportType) {
      'http' => [
        'headers' => $this->resolveHttpHeaders($httpHeaders),
      ],
      'stdio' => [
        'command' => $server->get('stdio_command'),
        'env' => $this->resolveEnvironmentVariables($server->get('stdio_env') ?? []),
        'cwd' => $server->get('stdio_cwd'),
      ],
      default => throw new \InvalidArgumentException("Unsupported transport type: {$transportType}"),
    };

    // For STDIO, endpoint is not used but command is required.
    if ($transportType === 'stdio' && empty($config['command'])) {
      throw new \InvalidArgumentException('STDIO transport requires a command to be specified.');
    }

    // For HTTP, endpoint is required.
    if (in_array($transportType, ['http']) && empty($endpoint)) {
      throw new \InvalidArgumentException("{$transportType} transport requires an endpoint URL.");
    }

    return new MCPClient(
    // STDIO doesn't use endpoint.
      endpoint: $endpoint ?: 'placeholder',
      transportType: $transportType,
      config: $config,
      logger: $this->logger
    );
  }

  /**
   * Create a client directly with configuration.
   *
   * @param string $endpoint
   *   The endpoint URL (or placeholder for STDIO).
   * @param string $transportType
   *   The transport type (http, stdio).
   * @param array<string, mixed> $config
   *   Additional configuration (command, env, cwd for STDIO).
   *
   * @return \Drupal\mcp_client\MCPClient
   *   The configured MCP client.
   */
  public function create(string $endpoint, string $transportType = 'http', array $config = []): MCPClient {
    return new MCPClient(
      endpoint: $endpoint,
      transportType: $transportType,
      config: $config,
      logger: $this->logger
    );
  }

  /**
   * Resolve HTTP headers, fetching values from Key module if needed.
   *
   * @param array<string, string> $http_headers
   *   The HTTP headers configuration.
   *
   * @return array<string, string>
   *   Resolved HTTP headers as key-value pairs.
   */
  protected function resolveHttpHeaders(array $http_headers): array {
    $resolved = [];

    foreach ($http_headers as $header_name => $header_value) {
      if (empty($header_value)) {
        continue;
      }

      // Special handling for Authorization header with prefix.
      if ($header_name === 'Authorization_key') {
        $prefix = $http_headers['Authorization_prefix'] ?? '';
        try {
          $key = $this->keyRepository->getKey($header_value);
          if ($key !== NULL) {
            $token = $key->getKeyValue();
            // Prefix already includes space (e.g., 'Bearer ', 'Token ').
            $resolved['Authorization'] = $prefix . $token;
            $this->logger->debug('Resolved Authorization header from Key "@key_id" with prefix "@prefix".', [
              '@key_id' => $header_value,
              '@prefix' => $prefix,
            ]);
          }
          else {
            $this->logger->warning('Authorization Key "@key_id" not found. Skipping Authorization header.', [
              '@key_id' => $header_value,
            ]);
          }
        }
        catch (InvalidDataTypeException $e) {
          // Not a key reference, treat as literal value.
          $resolved['Authorization'] = $prefix . $header_value;
        }
        continue;
      }

      // Skip the prefix field (already handled above).
      if ($header_name === 'Authorization_prefix') {
        continue;
      }

      // Check if this is a key reference for other headers.
      // This allows headers to either reference secure keys from the Key module
      // or contain literal values, providing flexible configuration options.
      try {
        $key = $this->keyRepository->getKey($header_value);
        if ($key !== NULL) {
          $resolved[$header_name] = $key->getKeyValue();
          $this->logger->debug('Resolved HTTP header "@header_name" from Key "@key_id".', [
            '@header_name' => $header_name,
            '@key_id' => $header_value,
          ]);
        }
        else {
          $this->logger->debug('HTTP header "@header_name" Key "@key_id" not found. Using as literal value.', [
            '@header_name' => $header_name,
            '@key_id' => $header_value,
          ]);
          $resolved[$header_name] = $header_value;
        }
      }
      catch (InvalidDataTypeException $e) {
        // Not a key reference, treat as literal value.
        $resolved[$header_name] = $header_value;
      }
    }

    $this->logger->info('Resolved @count HTTP headers.', [
      '@count' => count($resolved),
    ]);

    return $resolved;
  }

  /**
   * Resolve environment variables, fetching values from Key module if needed.
   *
   * @param array<string, mixed> $stdio_env
   *   The environment variables configuration.
   *
   * @return array<string, string>
   *   Resolved environment variables as key-value pairs.
   */
  protected function resolveEnvironmentVariables(array $stdio_env): array {
    $resolved = [];

    foreach ($stdio_env as $var_name => $var_config) {
      // Handle both old format (plain string) and new format (array with type).
      if (is_string($var_config)) {
        // Legacy format: plain string value.
        $resolved[$var_name] = $var_config;
      }
      elseif (is_array($var_config)) {
        $type = $var_config['type'] ?? 'plain';

        if ($type === 'key' && !empty($var_config['key_id'])) {
          // Fetch value from Key module.
          try {
            $key = $this->keyRepository->getKey($var_config['key_id']);
            if ($key) {
              $resolved[$var_name] = $key->getKeyValue();
              $this->logger->debug('Resolved environment variable "@var_name" from Key "@key_id".', [
                '@var_name' => $var_name,
                '@key_id' => $var_config['key_id'],
              ]);
            }
            else {
              $this->logger->warning('Key "@key_id" not found for environment variable "@var_name".', [
                '@key_id' => $var_config['key_id'],
                '@var_name' => $var_name,
              ]);
            }
          }
          catch (InvalidDataTypeException $e) {
            $this->logger->error('Error fetching key "@key_id" for environment variable "@var_name": @message', [
              '@key_id' => $var_config['key_id'],
              '@var_name' => $var_name,
              '@message' => $e->getMessage(),
            ]);
          }
        }
        else {
          // Plain text value.
          $resolved[$var_name] = $var_config['value'] ?? '';
          $this->logger->debug('Resolved environment variable "@var_name" from plain text value.', [
            '@var_name' => $var_name,
          ]);
        }
      }
    }

    $this->logger->info('Resolved @count environment variables for STDIO process.', [
      '@count' => count($resolved),
    ]);

    return $resolved;
  }

}
