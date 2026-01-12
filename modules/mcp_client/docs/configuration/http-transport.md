# HTTP Transport Configuration

HTTP transport allows you to connect to MCP servers that are accessible via HTTP or HTTPS endpoints. This is ideal for remote servers, cloud-hosted services, or any MCP server that exposes an HTTP API.

## When to Use HTTP Transport

Use HTTP transport when:

- Connecting to cloud-hosted MCP servers
- Accessing MCP servers on your network
- Using managed MCP services
- The MCP server is running on a different machine
- You need load balancing or high availability

## Configuration Fields

### Endpoint URL

**Required**: Yes  
**Type**: URL  
**Example**: `https://mcp.example.com/api` or `http://localhost:3000/mcp`

The full URL to your MCP server's HTTP endpoint. Must include the protocol (`http://` or `https://`).

**Best practices:**

- Use HTTPS in production for security
- Ensure the endpoint is accessible from your Drupal server
- Verify SSL certificates are valid (for HTTPS)

### Timeout

**Required**: No  
**Type**: Integer (seconds)  
**Default**: 30  
**Range**: 1-300

Maximum time to wait for a response from the MCP server before timing out.

**Recommendations:**

- Use 30 seconds for most operations
- Increase to 60-120 seconds for long-running operations
- Decrease to 10-15 seconds for fast, critical operations

## Authentication

For authenticated HTTP MCP servers, use environment variables to pass credentials:

1. Configure environment variables in the server settings
2. The MCP server should accept these via headers or query parameters
3. Use the Key module for sensitive credentials

Example environment variables:

```
API_KEY: [Key reference]
AUTH_TOKEN: [Key reference]
```

## Example Configurations

### Example 1: Public MCP Server

```yaml
Label: Public Weather MCP
Transport: HTTP
Endpoint: https://mcp.weather-api.com/v1
Timeout: 30
Environment Variables: (none)
```

### Example 2: Authenticated Private Server

```yaml
Label: Internal API MCP
Transport: HTTP
Endpoint: https://internal.example.com/mcp
Timeout: 45
Environment Variables:
  - API_KEY: [Reference to Key]
  - CLIENT_ID: my-drupal-client
```

### Example 3: Local Development

```yaml
Label: Local Dev MCP
Transport: HTTP
Endpoint: http://localhost:3000/mcp
Timeout: 15
Environment Variables: (none)
```

## Network Requirements

### Firewall Rules

Ensure your server can make outbound connections to the MCP server:

- Allow outbound HTTP (port 80) and/or HTTPS (port 443)
- Configure firewall rules if needed
- Check with your hosting provider about outbound connection restrictions

### DNS Resolution

- Verify the hostname resolves correctly from your Drupal server
- Test with: `curl -I https://your-mcp-server.com`

### SSL/TLS

For HTTPS connections:

- Ensure SSL certificates are valid
- Check certificate chain is complete
- Self-signed certificates may require additional configuration

## Testing Your Configuration

After configuring an HTTP transport MCP server:

### 1. Test Connection

```bash
curl -X POST https://your-mcp-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}'
```

### 2. Check Drupal Logs

Navigate to **Administration » Reports » Recent log messages** and check for MCP-related entries.

### 3. Use API Explorer

1. Go to **Administration » Configuration » AI » API Explorer**
2. Select a tool from your HTTP MCP server
3. Execute a test call
4. Verify the response

## Troubleshooting

### Connection Timeout

**Problem**: Connection times out before receiving a response.

**Solutions**:

- Increase the timeout value
- Check network connectivity
- Verify the MCP server is running and accessible
- Check firewall rules

### SSL Certificate Errors

**Problem**: SSL certificate verification fails.

**Solutions**:

- Ensure certificates are valid and not expired
- Check the certificate chain
- Verify the hostname matches the certificate
- For development only, you may need to adjust SSL verification settings

### Authentication Failures

**Problem**: MCP server returns authentication errors.

**Solutions**:

- Verify environment variables are set correctly
- Check that Key references are valid
- Ensure the MCP server receives authentication headers
- Review MCP server logs for details

### 404 or Route Not Found

**Problem**: HTTP 404 error when connecting.

**Solutions**:

- Verify the endpoint URL is correct
- Check the MCP server's routing configuration
- Ensure the path includes any required API version or prefix

## Performance Optimization

### Connection Pooling

The HTTP transport reuses connections when possible. No additional configuration needed.

### Caching

Consider implementing caching for frequently called tools:

- Use Drupal's cache API
- Implement cache tags for selective invalidation
- Cache responses based on input parameters

### Monitoring

Monitor HTTP transport performance:

- Track response times
- Monitor timeout occurrences
- Log failed requests
- Set up alerts for connectivity issues

## Security Considerations

### HTTPS

Always use HTTPS in production:

- Encrypts data in transit
- Prevents man-in-the-middle attacks
- Validates server identity

### API Keys

Store API keys securely:

- Use the Key module
- Never commit keys to version control
- Rotate keys regularly
- Use different keys for different environments

### Network Segmentation

- Restrict MCP server access to authorized clients
- Use VPNs or private networks when possible
- Implement rate limiting on the MCP server

## Next Steps

- [STDIO Transport](stdio-transport.md) - For local process-based servers
- [Environment Variables](environment-variables.md) - Secure credential management
- [Tool Management](tool-management.md) - Enable and configure tools
