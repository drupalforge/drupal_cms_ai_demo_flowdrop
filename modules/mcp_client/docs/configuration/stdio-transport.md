# STDIO Transport Configuration

STDIO (Standard Input/Output) transport allows you to run MCP servers as local processes. This is ideal for filesystem access, local databases, or when you need to run MCP servers written in Node.js, Python, or other languages.

## When to Use STDIO Transport

Use STDIO transport when:

- Running MCP servers locally on the same machine
- Using filesystem-based MCP servers
- Developing and testing MCP servers
- The MCP server doesn't expose an HTTP interface
- You need direct process communication
- Working with language-specific MCP implementations

## Configuration Fields

### Command

**Required**: Yes
**Type**: String
**Example**: `node /usr/local/bin/mcp-server/index.js` or `python3 /opt/mcp/server.py`

The full command to execute the MCP server process. Should include:

- The interpreter/runtime (node, python3, etc.)
- The absolute path to the server script
- Any required command-line arguments

**Best practices:**

- Always use absolute paths
- Test the command manually first
- Ensure the interpreter is in the PATH or use absolute path to it
- Include necessary arguments for the MCP server

### Working Directory

**Required**: No
**Type**: Directory path
**Example**: `/usr/local/bin/mcp-server` or `/opt/mcp`

The directory from which the process will be executed. This becomes the current working directory for the MCP server process.

**When to use:**

- The MCP server expects to run from a specific directory
- Relative paths in the server code need a specific base directory
- Configuration files are located relative to the working directory

### Environment Variables

**Required**: No
**Type**: Key-value pairs

Environment variables to set for the MCP server process. Each variable can be:

- **Plain text**: For non-sensitive values
- **Key reference**: For sensitive values (API keys, tokens, passwords)

See [Environment Variables](environment-variables.md) for detailed configuration.

## Example Configurations

### Example 1: Node.js MCP Server

```yaml
Label: Filesystem MCP
Transport: STDIO
Command: node /usr/local/bin/mcp-filesystem/index.js
Working Directory: /usr/local/bin/mcp-filesystem
Environment Variables:
  - ALLOWED_PATHS: /var/www/files
  - MAX_FILE_SIZE: 10485760
```

### Example 2: Python MCP Server

```yaml
Label: Database MCP
Transport: STDIO
Command: python3 /opt/mcp-servers/database/server.py
Working Directory: /opt/mcp-servers/database
Environment Variables:
  - DB_HOST: localhost
  - DB_NAME: drupal
  - DB_USER: mcp_reader
  - DB_PASSWORD: [Key reference]
```

### Example 3: Go MCP Server

```yaml
Label: API Gateway MCP
Transport: STDIO
Command: /opt/mcp/bin/api-gateway --config /opt/mcp/config.yaml
Working Directory: /opt/mcp
Environment Variables:
  - API_KEY: [Key reference]
  - LOG_LEVEL: info
```

### Example 4: Development MCP Server

```yaml
Label: Dev MCP Server
Transport: STDIO
Command: /usr/bin/npx tsx /home/user/dev/mcp-server/src/index.ts
Working Directory: /home/user/dev/mcp-server
Environment Variables:
  - NODE_ENV: development
  - DEBUG: mcp:*
```

## Process Management

### Process Lifecycle

The MCP Client module manages the process lifecycle:

1. **Startup**: Process starts when needed (lazy initialization)
2. **Communication**: Uses STDIO for JSON-RPC messages
3. **Monitoring**: Monitors process health
4. **Restart**: Automatically restarts if process crashes
5. **Shutdown**: Gracefully terminates when no longer needed

### Process Permissions

Ensure the web server user has appropriate permissions:

```bash
# Check permissions
ls -la /path/to/mcp-server/

# Set ownership (if needed)
chown -R www-data:www-data /path/to/mcp-server/

# Set execute permissions
chmod +x /path/to/mcp-server/executable
```

## Testing Your Configuration

### 1. Test Command Manually

Before configuring in Drupal, test the command manually:

```bash
# Switch to web server user
sudo -u www-data bash

# Test the command
node /usr/local/bin/mcp-server/index.js

# Check if it responds to STDIO
echo '{"jsonrpc":"2.0","id":1,"method":"tools/list"}' | node /usr/local/bin/mcp-server/index.js
```

### 2. Check Process Output

Monitor process output for errors:

```bash
# View Drupal logs
drush watchdog:show --type=mcp_client

# Or through the UI
# Administration » Reports » Recent log messages
```

### 3. Use API Explorer

1. Go to **Administration » Configuration » AI » API Explorer**
2. Select a tool from your STDIO MCP server
3. Execute a test call
4. Verify the response

## Troubleshooting

### Command Not Found

**Problem**: "command not found" or "No such file or directory"

**Solutions**:

- Use absolute paths for both interpreter and script
- Verify files exist: `ls -la /path/to/file`
- Check execute permissions: `chmod +x /path/to/file`
- Test as web server user: `sudo -u www-data which node`

### Permission Denied

**Problem**: Permission errors when starting the process.

**Solutions**:

- Check file ownership: `ls -la /path/to/mcp-server/`
- Set correct ownership: `chown www-data:www-data /path/to/files`
- Check execute permissions: `chmod +x /path/to/executable`
- Verify directory permissions for working directory

### Process Crashes

**Problem**: MCP server process crashes or exits unexpectedly.

**Solutions**:

- Check Drupal logs for error messages
- Test the command manually to see error output
- Review MCP server logs (if it has logging)
- Check for missing dependencies
- Verify environment variables are set correctly

### No Response from Process

**Problem**: Process starts but doesn't respond.

**Solutions**:

- Verify the server implements MCP protocol correctly
- Check for buffering issues in the implementation
- Review process output for error messages
- Ensure STDIO is being used (not file I/O)

### Working Directory Issues

**Problem**: MCP server can't find configuration or data files.

**Solutions**:

- Set the working directory appropriately
- Use absolute paths in the MCP server code
- Verify files exist relative to working directory
- Check the server's documentation for path requirements

## Performance Optimization

### Process Pooling

For frequently used MCP servers, the module maintains process pools:

- Processes stay alive between requests
- Reduces startup overhead
- Configurable pool size (future enhancement)

### Resource Limits

Monitor and limit resource usage:

```bash
# Check process resource usage
ps aux | grep mcp

# Set ulimits if needed (in systemd service or process)
ulimit -m 512000  # Memory limit
ulimit -t 60      # CPU time limit
```

### Logging

Control logging verbosity:

- Set appropriate LOG_LEVEL environment variable
- Rotate log files to prevent disk usage issues
- Use DEBUG mode only during development

## Security Considerations

### Sandboxing

Limit what the MCP server can access:

- Use restricted file permissions
- Set working directory carefully
- Limit allowed paths for filesystem access
- Use SELinux or AppArmor policies

### User Permissions

Run MCP servers with minimal permissions:

- Don't run as root
- Use dedicated user accounts when possible
- Restrict file system access
- Limit network access

### Environment Variables

Secure sensitive data:

- Use Key module for secrets
- Never hardcode credentials
- Rotate secrets regularly
- Use different credentials per environment

### Code Execution

Be aware of security implications:

- Only run trusted MCP server code
- Review third-party MCP servers
- Keep MCP server dependencies updated
- Monitor for security advisories

## Common MCP Server Runtimes

### Node.js

```bash
Command: node /path/to/server.js
# or for TypeScript with tsx
Command: npx tsx /path/to/server.ts
```

Requirements:
- Node.js installed
- Dependencies installed in server directory
- `node` in PATH or use absolute path

### Python

```bash
Command: python3 /path/to/server.py
# or with virtual environment
Command: /path/to/venv/bin/python /path/to/server.py
```

Requirements:
- Python 3 installed
- Dependencies installed (pip)
- `python3` in PATH or use absolute path

### Go

```bash
Command: /path/to/compiled-binary
```

Requirements:
- Binary compiled for your platform
- Execute permissions set
- Required dependencies bundled or available

### Rust

```bash
Command: /path/to/compiled-binary
```

Requirements:
- Binary compiled for your platform
- Execute permissions set
- Linked libraries available

## Next Steps

- [HTTP Transport](http-transport.md) - For remote MCP servers
- [Environment Variables](environment-variables.md) - Secure credential management
- [Tool Management](tool-management.md) - Enable and configure tools
