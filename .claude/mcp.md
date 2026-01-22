# MCP Server Integration for Claude Code

This document describes the MCP (Model Context Protocol) server integration that allows Claude Code to directly interact with Drupal's AI agents and tools.

## Quick Start

```bash
# Enable MCP (when doing agent development)
ddev flowdrop-dev-mcp enable

# Restart Claude Code to connect

# Disable when done
ddev flowdrop-dev-mcp disable
```

## What is MCP?

The Model Context Protocol (MCP) is an open standard that allows AI assistants like Claude to interact with external tools and data sources. When enabled, Claude Code can:

- **Create and modify AI agents** using the `ai_agent_agent` tools
- **Invoke AI agents** with natural language prompts
- **Execute Drupal tools** for entity operations, field management, etc.
- **Test agents** without manual browser interaction

## Commands

### Enable MCP

```bash
# Enable all plugins (default)
ddev flowdrop-dev-mcp enable

# Enable only specific plugins
ddev flowdrop-dev-mcp enable --plugins aia          # Only AI agents
ddev flowdrop-dev-mcp enable --plugins tools        # Only Drupal tools
ddev flowdrop-dev-mcp enable --plugins aia,tools    # Both (same as default)
```

### Disable MCP

```bash
ddev flowdrop-dev-mcp disable
```

### Check Status

```bash
ddev flowdrop-dev-mcp status
```

## Available Plugins

### `aia` - AI Agent Calling (14 tools)

Invoke Drupal AI agents with natural language prompts. Each agent is exposed as a tool that takes a `prompt` parameter.

| Tool | Description |
|------|-------------|

| Unique to this repo| |
| `aia_agent_handler__agent_handler` | The main agent that creates/modifies other agents |
| `aia_bundle_lister_agent__bundle_lister_agent` | Lists entity bundles |
| `aia_bundle_lister_assistant__bundle_lister_assistant` | Assistant for bundle listing |
| `aia_evaluation_agent__evaluation_agent` | Evaluates alt text quality |
| `aia_evaluation_assistant__evaluation_assistant` | Assistant for evaluation |
| Standard in Drupal CMS| |
| `aia_content_type_agent_triage__content_type_agent_triage` | Triages content type operations |
| `aia_content_type_agent__content_type_agent` | Manages content types |
| `aia_drupal_cms_assistant__drupal_cms_assistant` | Main Drupal CMS assistant |
| `aia_field_agent_triage__field_agent_triage` | Triages field operations |
| `aia_field_type_agent__field_type_agent` | Manages field types |
| `aia_node_content_type_agent__node_content_type_agent` | Manages node content types |
| `aia_taxonomy_agent_config__taxonomy_agent_config` | Configures taxonomy |

### `tools` - Drupal Tool API (42 tools)

Direct access to Drupal's Tool API for CRUD operations.

#### Agent Creation Tools (8)

| Tool | Description |
|------|-------------|
| `tools_ai_agent_agent___get_agent_info` | Get information about an agent |
| `tools_ai_agent_agent___get_tool_info` | Get information about a tool |
| `tools_ai_agent_agent___add_tool` | Add a tool to an agent |
| `tools_ai_agent_agent___remove_tool` | Remove a tool from an agent |
| `tools_ai_agent_agent___modify_agent` | Modify agent configuration |
| `tools_ai_agent_agent___add_default_information_tool` | Add default info tool |
| `tools_ai_agent_agent___remove_default_information_tool` | Remove default info tool |
| `tools_ai_agent_agent___add_property_definitions_to_tool` | Configure tool properties |

#### Entity Operations (16)

| Tool | Description |
|------|-------------|
| `tools_entity_type_list` | List all entity types |
| `tools_entity_bundle_list` | List bundles for an entity type |
| `tools_entity_bundle_add` | Create a new bundle |
| `tools_entity_bundle_update` | Update a bundle |
| `tools_entity_bundle_delete` | Delete a bundle |
| `tools_entity_bundle_definition` | Get bundle definition |
| `tools_entity_bundle_field_definitions` | Get field definitions for a bundle |
| `tools_entity_list` | List entities |
| `tools_entity_load_by_id` | Load entity by ID |
| `tools_entity_load_by_property` | Load entities by property |
| `tools_entity_stub` | Create entity stub |
| `tools_entity_save` | Save an entity |
| `tools_entity_delete` | Delete an entity |
| `tools_entity_revision_add` | Add entity revision |
| `tools_entity_translation_get` | Get entity translation |
| `tools_entity_field_values` | Get entity field values |
| `tools_entity_field_value_definitions` | Get field value definitions |

#### Field Operations (8)

| Tool | Description |
|------|-------------|
| `tools_field_type_definitions` | List available field types |
| `tools_field_storage_add` | Add field storage |
| `tools_field_storage_update` | Update field storage |
| `tools_field_storage_delete` | Delete field storage |
| `tools_field_add` | Add field to bundle |
| `tools_field_update` | Update field |
| `tools_field_delete` | Delete field |
| `tools_field_set_value` | Set field value on entity |

#### User Operations (4)

| Tool | Description |
|------|-------------|
| `tools_user_add_role` | Add role to user |
| `tools_user_remove_role` | Remove role from user |
| `tools_user_block` | Block user |
| `tools_user_unblock` | Unblock user |

#### System Tools (5)

| Tool | Description |
|------|-------------|
| `tools_system_status` | Get system status |
| `tools_log_message` | Log a message |
| `tools_display_message` | Display a message |
| `tools_send_email` | Send an email |
| `tools_vision` | Vision/image analysis |

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│  Claude Code                                                │
│  Reads: .mcp.json                                           │
└───────────────────────┬─────────────────────────────────────┘
                        │ stdio (MCP protocol)
                        ▼
┌─────────────────────────────────────────────────────────────┐
│  mcp-server-drupal (bridge binary)                          │
│  Location: ./bin/mcp-server-drupal                          │
│  Converts: stdio ↔ HTTP POST                                │
└───────────────────────┬─────────────────────────────────────┘
                        │ HTTP POST (JSON-RPC)
                        ▼
┌─────────────────────────────────────────────────────────────┐
│  Drupal (DDEV)                                              │
│  Endpoint: {DDEV_URL}/mcp/post                              │
│                                                             │
│  drupal/mcp module                                          │
│  ├── aia plugin (AI Agent Calling)                          │
│  └── tools plugin (Drupal Tool API)                         │
└─────────────────────────────────────────────────────────────┘
```

## Configuration Details

### Drupal MCP Settings

The enable command configures `mcp.settings` with:

```yaml
enable_auth: false
plugins:
  general:
    enabled: true
  tools:
    enabled: true
    roles: [anonymous, authenticated]
  aia:
    enabled: true
    roles: [anonymous, authenticated]
```

### Required Permissions

For local development (anonymous access):

- `use mcp server` - Base MCP access
- `administer tool` - Tool module access
- `administer ai_agent` - ai_agent_agent tools
- `administer content types` - Entity bundle tools
- `administer site configuration` - Various entity tools

### .mcp.json Format

Generated dynamically with the current DDEV URL:

```json
{
  "mcpServers": {
    "drupal-local": {
      "command": "./bin/mcp-server-drupal",
      "args": ["--drupal-url", "https://your-project.ddev.site"],
      "env": {}
    }
  }
}
```

## Advanced Usage

### Filtering Specific Tools

By default, all tools in enabled plugins are available. To disable specific tools, you can configure them in Drupal's MCP settings:

```php
$config = \Drupal::configFactory()->getEditable('mcp.settings');
$plugins = $config->get('plugins');

// Disable specific tools in the 'tools' plugin
$plugins['tools']['tools']['tools_send_email']['enabled'] = FALSE;
$plugins['tools']['tools']['tools_user_block']['enabled'] = FALSE;

$config->set('plugins', $plugins)->save();
```

### Testing Tools via curl

```bash
# List all available tools
curl -X POST "http://your-project.ddev.site/mcp/post" \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"tools/list","params":{},"id":1}'

# Call a tool
curl -X POST "http://your-project.ddev.site/mcp/post" \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"tools/call","params":{"name":"tools_entity_bundle_list","arguments":{"entity_type_id":"node"}},"id":2}'

# Call an AI agent
curl -X POST "http://your-project.ddev.site/mcp/post" \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"tools/call","params":{"name":"aia_bundle_lister_agent__bundle_lister_agent","arguments":{"prompt":"List all node bundles"}},"id":3}'
```

## Troubleshooting

### MCP not connecting

1. Check status: `ddev flowdrop-dev-mcp status`
2. Verify DDEV is running: `ddev status`
3. Check .mcp.json exists and has correct URL
4. Restart Claude Code after enabling

### Tool access denied

Check Drupal permissions are granted:
```bash
ddev drush role:perm:list anonymous | grep -i "mcp\|tool\|agent"
```

### Binary not working

Re-download for your platform:
```bash
rm bin/mcp-server-drupal
ddev flowdrop-dev-mcp enable
```

## Security Note

The anonymous permissions granted are for **local development only**. Never use this configuration in production. The MCP module is not installed on DrupalForge deployments.

## References

- [drupal/mcp module](https://www.drupal.org/project/mcp)
- [MCP Server Drupal binary](https://github.com/Omedia/mcp-server-drupal)
- [Model Context Protocol specification](https://modelcontextprotocol.io/)
- [ai_agent_agent module](https://www.drupal.org/project/ai_agent_agent)
