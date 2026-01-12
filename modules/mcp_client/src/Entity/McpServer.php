<?php

declare(strict_types=1);

namespace Drupal\mcp_client\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\mcp_client\McpServerInterface;

/**
 * Defines the MCP Server entity type.
 *
 * @ConfigEntityType(
 *   id = "mcp_server",
 *   label = @Translation("MCP Server"),
 *   label_collection = @Translation("MCP Servers"),
 *   label_singular = @Translation("MCP Server"),
 *   label_plural = @Translation("MCP Servers"),
 *   label_count = @PluralTranslation(
 *     singular = "@count MCP Server",
 *     plural = "@count MCP Servers",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\mcp_client\McpServerListBuilder",
 *     "form" = {
 *       "add" = "Drupal\mcp_client\Form\McpServerForm",
 *       "edit" = "Drupal\mcp_client\Form\McpServerForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *   },
 *   config_prefix = "mcp_server",
 *   admin_permission = "administer mcp_server",
 *   links = {
 *     "collection" = "/admin/structure/mcp-server",
 *     "add-form" = "/admin/structure/mcp-server/add",
 *     "edit-form" = "/admin/structure/mcp-server/{mcp_server}",
 *     "delete-form" = "/admin/structure/mcp-server/{mcp_server}/delete",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "endpoint",
 *     "transport_type",
 *     "stdio_command",
 *     "stdio_env",
 *     "stdio_cwd",
 *     "timeout",
 *     "http_headers",
 *     "tools",
 *     "enabled_tools",
 *   },
 * )
 */
final class McpServer extends ConfigEntityBase implements McpServerInterface {

  /**
   * The MCP Server ID.
   */
  protected string $id;

  /**
   * The MCP Server label.
   */
  protected string $label;

  /**
   * The MCP Server description.
   */
  protected string $description = '';

  /**
   * The MCP server endpoint URL.
   */
  protected string $endpoint = '';

  /**
   * The transport type (http, stdio).
   */
  protected string $transport_type = 'http';

  /**
   * STDIO command (for stdio transport).
   */
  protected ?string $stdio_command = NULL;

  /**
   * STDIO environment variables.
   *
   * @var array<string, string>
   */
  protected array $stdio_env = [];

  /**
   * STDIO working directory.
   */
  protected ?string $stdio_cwd = NULL;

  /**
   * HTTP timeout in seconds.
   */
  protected int $timeout = 30;

  /**
   * HTTP headers.
   *
   * @var array<string, string>
   */
  protected array $http_headers = [];

  /**
   * The available tools.
   *
   * @var array<string, mixed>
   */
  protected array $tools = [];

  /**
   * The enabled tools.
   *
   * @var array<string>
   */
  protected array $enabled_tools = [];

}
