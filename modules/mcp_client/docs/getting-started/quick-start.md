# Quick Start Guide

This guide will help you get up and running with MCP Client in minutes.

## Prerequisites

- MCP Client module installed and enabled
- AI module configured with at least one provider
- An MCP server to connect to (or use the example below)

## Example: HTTP MCP Server

This example shows how to connect to a hypothetical HTTP-based MCP server.

### Step 1: Add the Server

1. Navigate to **Administration » Configuration » AI » MCP Servers** (`/admin/structure/mcp-server`)
2. Click "Add MCP Server"
3. Fill in:
   - **Label**: GitHub MCP Server
   - **Transport Type**: HTTP (Streamable HTTP)
   - **Endpoint URL**: `https://example.com/mcp`
   - **Timeout**: 30 seconds
4. Click "Save"

### Step 2: Enable Tools

1. Edit the "GitHub MCP Server" configuration
2. Under "Enabled tools", check the tools you want to use
3. Click "Save"

### Step 3: Test in API Explorer

1. Navigate to **Administration » Configuration » AI » API Explorer** (`/admin/config/ai/api-explorer`)
2. Select an enabled tool from the function list
3. Fill in required parameters
4. Click "Execute"
5. View the response

## Example: Local STDIO Server

This example shows how to run a local MCP server using Node.js.

### Step 1: Add the Server

1. Navigate to **Administration » Configuration » AI » MCP Servers**
2. Click "Add MCP Server"
3. Fill in:
   - **Label**: Local Filesystem MCP
   - **Transport Type**: STDIO (Process)
   - **Command**: `node /usr/local/bin/mcp-filesystem-server/index.js`
   - **Working Directory**: `/usr/local/bin/mcp-filesystem-server`
4. Click "Save"

### Step 2: Add Environment Variables (if needed)

1. Edit the server configuration
2. Under "Environment Variables", click "Add another item"
3. For each variable:
   - **Name**: Variable name (e.g., `API_KEY`)
   - **Type**: Key (for sensitive data) or Plain text
   - **Value/Key**: Select a key or enter plain text
4. Click "Save"

### Step 3: Enable Tools

Same as HTTP example above.

## Using MCP Tools in AI Agents

Once configured, MCP tools are automatically available to AI agents:

1. Navigate to **Administration » Configuration » AI » Agents**
2. Create or edit an agent
3. In the agent configuration, enabled MCP tools appear as available functions
4. The AI agent can now call these tools during execution

## Common Tasks

### Viewing Available Tools

Navigate to the API Explorer to see all available tools from all enabled MCP servers.

### Disabling a Server Temporarily

1. Edit the MCP server configuration
2. Uncheck "Enabled"
3. Click "Save"

### Updating Server Configuration

1. Edit the MCP server configuration
2. Modify settings as needed
3. Click "Save"
4. The connection will be re-established automatically

## Example Use Cases

### Use Case 1: File Operations

Configure a filesystem MCP server to let AI agents read and write files within controlled directories.

### Use Case 2: Database Queries

Set up an MCP server that provides database query tools, allowing AI agents to retrieve information from your database.

### Use Case 3: External APIs

Connect to an MCP server that wraps external APIs (weather, news, etc.) to enrich AI agent capabilities.

## Troubleshooting

### Server Not Connecting

- Verify the endpoint URL or command path is correct
- Check server logs for errors
- Ensure firewall/network settings allow connections (HTTP)
- Test the command manually (STDIO)

### Tools Not Appearing

- Verify the server is enabled
- Check that tools are enabled in the server configuration
- Clear Drupal cache: `drush cr`

### Authentication Errors

- Verify environment variables are set correctly
- For sensitive values, use Key module references
- Check MCP server logs for authentication details

## Next Steps

- [HTTP Transport Configuration](../configuration/http-transport.md)
- [STDIO Transport Configuration](../configuration/stdio-transport.md)
- [Environment Variables](../configuration/environment-variables.md)
