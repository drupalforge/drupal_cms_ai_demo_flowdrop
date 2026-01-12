# Configuration

This guide covers the basic configuration of MCP Client module. For detailed information about specific transport types, see the [Configuration](../configuration/http-transport.md) section.

## Accessing MCP Server Configuration

Navigate to the MCP Servers administration page:

**Administration » Configuration » AI » MCP Servers** (`/admin/structure/mcp-server`)

## Adding Your First MCP Server

1. Click "Add MCP Server"
2. Fill in the basic information:
   - **Label**: A descriptive name for your server
   - **Machine name**: Auto-generated from the label
   - **Transport Type**: Choose between HTTP or STDIO

## Transport Types

### HTTP Transport

Use for remote MCP servers accessible via HTTP/HTTPS.

**When to use:**

- Connecting to cloud-hosted MCP servers
- Accessing MCP servers on your network
- Using managed MCP services

[Learn more about HTTP Transport configuration →](../configuration/http-transport.md)

### STDIO Transport

Use for running MCP servers as local processes (Node.js, Python, etc.).

**When to use:**

- Running MCP servers locally
- Using filesystem-based MCP servers
- Developing and testing MCP servers

[Learn more about STDIO Transport configuration →](../configuration/stdio-transport.md)

## Enabling Tools

After creating an MCP server:

1. Edit the MCP server configuration
2. Navigate to the "Enabled tools" section
3. Check the boxes for tools you want to enable
4. Click "Save"

Only enabled tools will be available to AI agents.

[Learn more about Tool Management →](../configuration/tool-management.md)

## Environment Variables

For secure handling of API keys and sensitive data:

1. Install and enable the Key module (automatically required)
2. Create keys at **Administration » Configuration » System » Keys**
3. Reference keys in MCP server environment variables

[Learn more about Environment Variables →](../configuration/environment-variables.md)

## Testing Your Configuration

After configuration, test your MCP server connection:

1. Navigate to **Administration » Configuration » AI » API Explorer** (`/admin/config/ai/api-explorer`)
2. Your enabled MCP tools should appear in the function list
3. Try calling a tool to verify connectivity

## Common Configuration Patterns

### Pattern 1: Remote API Server

```
Transport: HTTP
URL: https://api.example.com/mcp
Timeout: 30 seconds
Authentication: Via environment variables
```

### Pattern 2: Local Node.js Server

```
Transport: STDIO
Command: node /path/to/mcp-server/index.js
Working Directory: /path/to/mcp-server
Environment: NODE_ENV=production
```

### Pattern 3: Python MCP Server

```
Transport: STDIO
Command: python3 /path/to/server.py
Working Directory: /path/to
Environment: PYTHONPATH=/path/to/lib
```

## Next Steps

- [Quick Start Guide](quick-start.md) - Get up and running quickly
- [HTTP Transport Details](../configuration/http-transport.md)
- [STDIO Transport Details](../configuration/stdio-transport.md)
- [Environment Variables](../configuration/environment-variables.md)
