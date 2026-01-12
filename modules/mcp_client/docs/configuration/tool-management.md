# Tool Management

MCP servers expose tools (functions) that AI agents can call. The MCP Client module provides a UI for managing which tools are enabled and available to your AI agents.

## Overview

Each MCP server may provide multiple tools. You can:

- View all available tools from a server
- Enable/disable specific tools
- Configure tool-specific settings (future enhancement)
- See tool metadata and schemas

## Viewing Available Tools

### Method 1: MCP Server Configuration

1. Navigate to **Administration » Configuration » AI » MCP Servers** (`/admin/structure/mcp-server`)
2. Click "Edit" on an MCP server
3. Scroll to "Enabled tools" section
4. View the list of available tools

### Method 2: API Explorer

1. Navigate to **Administration » Configuration » AI » API Explorer** (`/admin/config/ai/api-explorer`)
2. Tools from all enabled MCP servers appear in the function list
3. Each tool shows:
   - Name and description
   - Source MCP server
   - Input parameters
   - Output schema

## Enabling Tools

### Enable Individual Tools

1. Edit an MCP server configuration
2. In "Enabled tools" section, check the boxes for desired tools
3. Click "Save"

**Only enabled tools are available to AI agents.**

### Enable All Tools

To quickly enable all tools from a server:

1. Edit the MCP server
2. Check "Enable all tools" (if available)
3. Or manually check all boxes
4. Click "Save"

## Disabling Tools

### Temporary Disable

To temporarily disable tools without removing the server:

1. Edit the MCP server
2. Uncheck specific tools in "Enabled tools" section
3. Click "Save"

### Disable All Tools

To disable all tools from a server:

1. Edit the MCP server
2. Uncheck "Enabled" at the top of the form
3. Click "Save"

This disables the entire server and all its tools.

## Tool Discovery

The module automatically discovers tools when:

- An MCP server is first saved
- The MCP server configuration is edited
- Drupal cache is cleared
- Enable desired tools
- Click "Save"

## Tool Information

### Tool Schema

Each tool includes:

- **Name**: Unique identifier
- **Description**: What the tool does
- **Parameters**: Input parameters with types and descriptions
- **Return Type**: What the tool returns

### View Tool Schema

In API Explorer:

1. Select a tool from the dropdown
2. View the generated form showing:
   - Required parameters
   - Optional parameters
   - Parameter types
   - Descriptions

## Using Tools in AI Agents

Once enabled, tools are automatically available to AI agents.

### Automatic Registration

The MCP Client module:

1. Discovers tools from enabled MCP servers
2. Registers them as AI function call plugins
3. Makes them available to the AI module
4. Updates when tools are enabled/disabled

### In AI Agents

When configuring an AI agent:

1. Navigate to **Administration » Configuration » AI » Agents**
2. Create or edit an agent
3. In function selection, MCP tools appear alongside other functions
4. Enable desired MCP tools for the agent
5. The agent can now call these tools during execution

## Tool Naming

### Naming Convention

MCP tools are named with this pattern:

```
mcp_[server_id]_[tool_name]
```

Example:
- Server ID: `github`
- Tool name: `search_repositories`
- Full name: `mcp_github_search_repositories`

### Avoiding Conflicts

If multiple servers provide tools with the same name:

- Each gets a unique identifier based on server ID
- The full name includes the server ID
- Tools from different servers remain distinct

## Tool Categories

Common tool categories you might encounter:

### File System Tools

```
- read_file
- write_file
- list_directory
- delete_file
```

### Database Tools

```
- query_database
- execute_query
- list_tables
- get_schema
```

### API Tools

```
- search_repositories
- get_user
- create_issue
- list_commits
```

### Utility Tools

```
- calculate
- convert
- validate
- format
```

## Best Practices

### Enable Only What You Need

✅ Only enable tools that will be used
✅ Review tool descriptions before enabling
✅ Test tools individually before using in production
✅ Disable unused tools to reduce complexity

### Security Considerations

✅ Review tool permissions and capabilities
✅ Understand what data each tool can access
✅ Use appropriate access controls
✅ Monitor tool usage and logs
✅ Disable dangerous tools in production

### Performance

✅ Fewer enabled tools = faster agent initialization
✅ Disable tools from unused servers
✅ Consider tool execution time
✅ Monitor resource usage

## Tool Configuration

### Current Limitations

In the current version, tool-specific configuration is limited to enable/disable. Future enhancements may include:

- Tool-specific timeouts
- Rate limiting per tool
- Custom parameter validation
- Tool aliases
- Tool descriptions override

## Troubleshooting

### Tools Not Appearing

**Problem**: Expected tools don't appear in the list

**Solutions**:
- Verify the MCP server is enabled
- Check server connection is working
- Clear Drupal cache: `drush cr`
- Review server logs for errors
- Test server connection manually

### Tool Execution Fails

**Problem**: Enabled tool fails when called

**Solutions**:
- Verify MCP server is running (STDIO) or accessible (HTTP)
- Check tool parameters are correct
- Review error messages in Drupal logs
- Test tool in API Explorer first
- Verify server environment variables are set correctly

### Tool Not Available to Agent

**Problem**: Agent can't see/use an enabled tool

**Solutions**:
- Verify tool is enabled on the MCP server
- Check MCP server is enabled
- Ensure agent function permissions are set correctly
- Clear Drupal cache: `drush cr`
- Re-save the agent configuration

### Stale Tool List

**Problem**: Tool list doesn't reflect server changes

**Solutions**:
- Re-save the MCP server configuration
- Clear Drupal cache: `drush cr`
- Verify server is responding to tools/list request

## Monitoring Tool Usage

### Via Logs

View tool execution in logs:

1. Navigate to **Administration » Reports » Recent log messages**
2. Filter by "mcp_client"
3. Review tool execution logs

### Via AI Logging Module

If AI Logging module is enabled:

1. View detailed logs of AI operations including tool calls
2. See parameters passed to tools
3. Review tool responses
4. Track tool performance

## Advanced: Custom Tool Plugins

For advanced users, you can create custom tool plugins that wrap MCP tools.

## Next Steps

- [HTTP Transport](http-transport.md) - Configure HTTP servers
- [STDIO Transport](stdio-transport.md) - Configure STDIO servers
- [Quick Start Guide](../getting-started/quick-start.md) - Get started quickly
