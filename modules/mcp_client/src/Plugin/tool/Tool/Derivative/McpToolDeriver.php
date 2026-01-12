<?php

namespace Drupal\mcp_client\Plugin\tool\Tool\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tool\Tool\ToolDefinition;
use Drupal\tool\TypedData\InputDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a tool derivative for each MCP server tool.
 */
class McpToolDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * Constructs a new McpToolDeriver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param ToolDefinition|array<string, mixed> $base_plugin_definition
   * @phpstan-return array<string, ToolDefinition>
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    // Ensure we have a ToolDefinition object.
    if (is_array($base_plugin_definition)) {
      $base_plugin_definition = new ToolDefinition($base_plugin_definition);
    }

    if (empty($this->derivatives)) {
      $definitions = [];
      // Load all MCP Servers.
      $mcp_servers = $this->entityTypeManager->getStorage('mcp_server')->loadMultiple();
      if (empty($mcp_servers)) {
        return [];
      }
      foreach ($mcp_servers as $mcp_server) {
        /** @var \Drupal\mcp_client\Entity\McpServer $mcp_server */
        // Only enabled ones.
        if (!$mcp_server->get('status')) {
          continue;
        }
        // Get all tools.
        $tools = $mcp_server->get('tools');
        if (empty($tools)) {
          continue;
        }
        // Get all enabled tools.
        $enabled_tools = $mcp_server->get('enabled_tools');
        if (empty($enabled_tools)) {
          continue;
        }
        // Loop through them.
        foreach ($enabled_tools as $tool) {
          if (isset($tools[$tool])) {
            $id = $mcp_server->get('id') . ':' . $tools[$tool]['name'];

            $input_definitions = [];
            foreach ($tools[$tool]['inputSchema']['properties'] as $key => $info) {
              // Map MCP types to Drupal data types.
              $type = match ($info['type']) {
                'number' => 'float',
                'integer' => 'integer',
                'boolean' => 'boolean',
                'array' => 'list',
                'object' => 'string',
                default => 'string',
              };

              $is_required = $info['required'] ?? FALSE;
              $input_definitions[$key] = new InputDefinition(
                data_type: $type,
                label: new TranslatableMarkup('@key', ['@key' => $key]),
                required: $is_required,
                multiple: FALSE,
                description: new TranslatableMarkup('@desc', ['@desc' => $info['description'] ?? 'no description']),
                default_value: $info['default_value'] ?? NULL,
              );
            }

            $definitions[$id] = new ToolDefinition([
              'id' => $base_plugin_definition->id() . ':' . $id,
              'label' => new TranslatableMarkup('@name', ['@name' => $tools[$tool]['name']]),
              'description' => new TranslatableMarkup('@desc', ['@desc' => $tools[$tool]['description'] ?? 'no description']),
              'input_definitions' => $input_definitions,
              'class' => $base_plugin_definition->getClass(),
            ]);
          }
        }
        $this->derivatives = $definitions;
      }
    }

    // Convert ToolDefinition back to array for parent call.
    $base_array = [
      'id' => $base_plugin_definition->id(),
      'class' => $base_plugin_definition->getClass(),
    ];

    return parent::getDerivativeDefinitions($base_array);
  }

}
