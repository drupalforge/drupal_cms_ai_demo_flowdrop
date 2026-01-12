<?php

namespace Drupal\mcp_client\Plugin\tool\Tool;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\mcp_client\Plugin\tool\Tool\Derivative\McpToolDeriver;
use Drupal\mcp_client\Service\McpClientFactory;
use Drupal\tool\Attribute\Tool;
use Drupal\tool\ExecutableResult;
use Drupal\tool\Tool\ToolBase;
use Drupal\tool\Tool\ToolOperation;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Plugin implementation of the MCP tool wrapper.
 */
#[Tool(
  id: 'mcp_tool',
  label: new TranslatableMarkup('MCP Tool'),
  description: new TranslatableMarkup('Executes MCP server tools'),
  deriver: McpToolDeriver::class,
  // @todo add support to set operation dynamically based on tool metadata.
  operation: ToolOperation::Trigger,
)]
class McpToolBase extends ToolBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The MCP client factory.
   *
   * @var \Drupal\mcp_client\Service\McpClientFactory
   */
  protected McpClientFactory $clientFactory;

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $configuration
   * @phpstan-param array<string, mixed> $plugin_definition
   * @phpstan-param string $plugin_id
   * @phpstan-param string $plugin_definition
   * @phpstan-return static
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->clientFactory = $container->get('mcp_client.client_factory');

    return $instance;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $values
   * @phpstan-return \Drupal\tool\ExecutableResult
   */
  protected function doExecute(array $values): ExecutableResult {
    $derivative_id = $this->getDerivativeId();
    if (!$derivative_id) {
      return ExecutableResult::failure(new TranslatableMarkup('MCP tool derivative ID is missing.'));
    }
    [$config_id, $tool] = explode(':', $derivative_id);

    try {
      /** @var \Drupal\mcp_client\Entity\McpServer|null $server */
      $server = $this->entityTypeManager->getStorage('mcp_server')->load($config_id);
      if (!$server) {
        return ExecutableResult::failure(new TranslatableMarkup('MCP server not found.'));
      }
      if (!$server->get('status')) {
        return ExecutableResult::failure(new TranslatableMarkup('MCP server is not enabled.'));
      }
      $tools = $server->get('tools');
      if (empty($tools[$tool])) {
        return ExecutableResult::failure(new TranslatableMarkup('MCP tool is not enabled.'));
      }
      $enabled_tools = $server->get('enabled_tools');

      if (empty($enabled_tools)) {
        return ExecutableResult::failure(new TranslatableMarkup('MCP server has no enabled tools.'));
      }
      if (!in_array($tool, $enabled_tools)) {
        return ExecutableResult::failure(new TranslatableMarkup('MCP tool is not enabled.'));
      }
      $tool_info = $tools[$tool];

      // Create client via factory (supports all transports).
      $client = $this->clientFactory->createFromEntity($server);

      $parameters = [];
      foreach ($tool_info['inputSchema']['properties'] as $key => $info) {
        // Get from values.
        $value = $values[$key] ?? NULL;
        // Convert string json to array.
        if (is_string($value) && substr($value, 0, 1) == "{" && json_decode($value, TRUE) !== NULL) {
          $value = json_decode($value);
        }

        // Only set if not null.
        if ($value === NULL) {
          continue;
        }
        $parameters[$key] = $value;
      }
      if (empty($parameters)) {
        $parameters = new \stdClass();
      }
      $response = $client->executeTool($tool_info['name'], $parameters);

      // Format the response for better readability.
      if (isset($response['content']) && is_array($response['content'])) {
        $content_text = [];
        foreach ($response['content'] as $content_item) {
          if (isset($content_item['text'])) {
            $text = $content_item['text'];
            // Try to decode if it's JSON.
            $decoded = json_decode($text, TRUE);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
              // It's valid JSON, format it nicely.
              $content_text[] = Yaml::dump($decoded, 10, 2);
            }
            else {
              $content_text[] = $text;
            }
          }
        }
        $output = implode("\n", $content_text);
      }
      else {
        $output = Yaml::dump($response, 10, 2);
      }

      // Check if the response indicates an error.
      if (isset($response['isError']) && $response['isError']) {
        return ExecutableResult::failure(
          new TranslatableMarkup('MCP tool returned an error: @output', ['@output' => $output])
        );
      }

      return ExecutableResult::success(
        new TranslatableMarkup('MCP tool executed successfully: @output', ['@output' => $output])
      );
    }
    catch (\Exception $e) {
      return ExecutableResult::failure(
        new TranslatableMarkup('MCP tool execution failed: @message', ['@message' => $e->getMessage()])
      );
    }
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $values
   * @phpstan-param \Drupal\Core\Session\AccountInterface $account
   * @phpstan-param bool $return_as_object
   * @phpstan-return bool|\Drupal\Core\Access\AccessResultInterface
   */
  protected function checkAccess(
    array $values,
    AccountInterface $account,
    bool $return_as_object = FALSE,
  ): bool|AccessResultInterface {
    // Check if user has permission to use MCP client tools.
    $access = AccessResult::allowedIfHasPermission($account, 'administer mcp_client');

    return $return_as_object ? $access : $access->isAllowed();
  }

}
