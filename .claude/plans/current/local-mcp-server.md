# Local MCP Server for Claude Code Integration

- **Branch**: `feature/mcpserver`
- **Status**: In Progress
- **Repository**: This demo repo (not flowdrop_ui_agents module)

## Goal

Set up a local MCP server during `ddev setup-flowdrop-dev` that allows Claude Code to:
1. Connect to the `ai_agent_agent` (Agent Handler Agent) to create/modify AI agents
2. Access individual tools directly for testing and development
3. Test newly created agents to verify they work

This setup is **local development only** - it should NOT run on DrupalForge production.

## Architecture (Verified Working)

```
┌─────────────────────────────────────────────────────────────┐
│  Claude Code (MCP Client)                                   │
│                                                             │
│  Uses: .mcp.json in project root                            │
└───────────────────────┬─────────────────────────────────────┘
                        │ stdio (MCP protocol)
                        ▼
┌─────────────────────────────────────────────────────────────┐
│  mcp-server-drupal binary (bridge)                          │
│                                                             │
│  Location: ./bin/mcp-server-drupal                          │
│  Converts: stdio ↔ HTTP POST                                │
└───────────────────────┬─────────────────────────────────────┘
                        │ HTTP POST
                        ▼
┌─────────────────────────────────────────────────────────────┐
│  DDEV Container (Drupal)                                    │
│                                                             │
│  Endpoint: http://drupal-cms-ai-demo-flowdrop.ddev.site/mcp/post
│                                                             │
│  ┌─────────────────────────────────────────────────────────┐│
│  │  MCP Module (drupal/mcp v1.2.3)                         ││
│  │                                                         ││
│  │  Plugins:                                               ││
│  │  - tools: Exposes ALL Drupal Tool API tools             ││
│  │  - aia: AI Agent Calling (invoke agents with prompts)   ││
│  │  - general: General MCP info                            ││
│  └─────────────────────────────────────────────────────────┘│
│                                                             │
│  55 Tools Exposed (verified):                               │
│  - aia_*: 13 AI agents (invoke with prompts)                │
│  - tools_*: 42 Drupal tools (direct tool calls)             │
└─────────────────────────────────────────────────────────────┘
```

## Completed Research

### Phase 1: MCP Module ✅

- [x] **Installed MCP module**: `composer require drupal/mcp` (v1.2.3)
- [x] **Tested endpoint**: `/mcp/post` works with JSON-RPC over HTTP POST
- [x] **Tool discovery**: MCP discovers tools via plugin system
  - `tools` plugin: Exposes Tool API tools (entity_*, ai_agent_agent_*, etc.)
  - `aia` plugin: Exposes AI Agents as callable tools with prompt input
- [x] **Tool filtering**: Per-plugin enabled/disabled + role-based access

### Phase 2: ai_agent_agent Integration ✅

- [x] **Tools exposed**: All 8 ai_agent_agent tools work via MCP
  - `tools_ai_agent_agent___get_agent_info` ✅
  - `tools_ai_agent_agent___add_tool` ✅
  - `tools_ai_agent_agent___modify_agent` ✅
  - etc.
- [x] **Agent calling works**: Can invoke agents directly
  - `aia_bundle_lister_agent__bundle_lister_agent` with prompt ✅
  - Returns agent response as text content

### Phase 3: Claude Code Integration 🔄

- [x] **Downloaded bridge binary**: `mcp-server-drupal_darwin_arm`
- [x] **Created .mcp.json**: Project-level MCP config
- [ ] **Test Claude Code connection**: Restart Claude Code to pick up config
- [ ] **Verify tool invocation**: Test calling tools from Claude Code

## Required Configuration

### MCP Settings (Drupal Config)

```yaml
# mcp.settings
enable_auth: false
plugins:
  general:
    enabled: true
    config: {}
  tools:
    enabled: true
    roles: [anonymous, authenticated]
    config: {}
  aia:
    enabled: true
    roles: [anonymous, authenticated]
    config: {}
```

### Required Permissions (Anonymous User)

For local development without auth, anonymous needs:
- `use mcp server` - Base MCP access
- `administer tool` - Tool module access
- `administer ai_agent` - ai_agent_agent tools
- `administer content types` - Entity bundle tools
- `administer site configuration` - Various entity tools

### .mcp.json (Project Config)

```json
{
  "mcpServers": {
    "drupal-local": {
      "command": "./bin/mcp-server-drupal",
      "args": ["--drupal-url", "http://drupal-cms-ai-demo-flowdrop.ddev.site"],
      "env": {}
    }
  }
}
```

## Available Tools (55 Total)

### AI Agents (aia_* prefix) - 13 tools
Call agents with natural language prompts:
- `aia_agent_handler__agent_handler` - The agent that creates/modifies agents
- `aia_agent_creation_assistant__agent_creation_assistant`
- `aia_bundle_lister_agent__bundle_lister_agent`
- `aia_evaluation_agent__evaluation_agent`
- etc.

### Drupal Tools (tools_* prefix) - 42 tools
Direct tool execution:

**ai_agent_agent tools (8)**:
- `tools_ai_agent_agent___get_agent_info`
- `tools_ai_agent_agent___add_tool`
- `tools_ai_agent_agent___remove_tool`
- `tools_ai_agent_agent___modify_agent`
- `tools_ai_agent_agent___get_tool_info`
- `tools_ai_agent_agent___add_default_information_tool`
- `tools_ai_agent_agent___remove_default_information_tool`
- `tools_ai_agent_agent___add_property_definitions_to_tool`

**Entity tools**:
- `tools_entity_bundle_list`, `tools_entity_type_list`
- `tools_entity_list`, `tools_entity_load_by_id`
- `tools_entity_save`, `tools_entity_delete`
- `tools_field_set_value`, etc.

## Implementation Tasks

- [x] Install MCP module
- [x] Configure MCP plugins and roles
- [x] Test tools/call via curl
- [x] Download mcp-server-drupal binary
- [x] Create .mcp.json config
- [x] Create separate enable/disable command
- [ ] Test end-to-end with Claude Code

## Usage

MCP is **opt-in** - not enabled by default. This keeps the 55 tools out of normal conversations.

```bash
# Normal dev setup (no MCP)
ddev setup-flowdrop-dev

# When doing agent development work, enable MCP:
ddev flowdrop-dev-mcp enable
# >>> Restart Claude Code <<<

# When done, disable MCP:
ddev flowdrop-dev-mcp disable
# >>> Restart Claude Code <<<

# Check current status:
ddev flowdrop-dev-mcp status
```

The enable command:
1. Installs MCP module (if needed)
2. Configures plugins and permissions
3. Downloads the bridge binary (platform-specific)
4. Generates `.mcp.json` with the **current DDEV URL** (dynamic!)

## Files to Commit

- `.gitignore` update - Add `bin/` and `.mcp.json`
- `.ddev/commands/host/flowdrop-dev-mcp` - New enable/disable command
- `.ddev/commands/host/setup-flowdrop-dev` - Minor update (hint about MCP)

## Files NOT to Commit (generated during enable)

- `bin/mcp-server-drupal` - Downloaded binary (platform-specific)
- `.mcp.json` - Generated with dynamic DDEV URL
- `composer.json` changes - MCP is installed via script

## Reference Links

- [MCP Module on drupal.org](https://www.drupal.org/project/mcp)
- [MCP Server Drupal Binary](https://github.com/Omedia/mcp-server-drupal/releases)
- [Model Context Protocol Spec](https://modelcontextprotocol.io/)
