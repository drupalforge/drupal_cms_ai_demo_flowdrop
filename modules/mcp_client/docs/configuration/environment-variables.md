# Environment Variables

Environment variables allow you to pass configuration and credentials to MCP servers securely. The MCP Client module integrates with Drupal's Key module to manage sensitive values.

## Overview

Environment variables are key-value pairs passed to MCP servers:

- **For HTTP transport**: Sent as custom headers or included in requests
- **For STDIO transport**: Set as process environment variables

## Variable Types

### Plain Text

Use for non-sensitive configuration values:

- Feature flags
- Non-sensitive URLs
- Timeout values
- Log levels
- Paths

**Example:**

```
Name: LOG_LEVEL
Type: Plain text
Value: info
```

### Key Reference

Use for sensitive values:

- API keys
- Authentication tokens
- Passwords
- Client secrets
- Private keys

**Example:**

```
Name: API_KEY
Type: Key
Key: github_api_key
```

## Setting Up Keys

### 1. Create a Key

1. Navigate to **Administration » Configuration » System » Keys** (`/admin/config/system/keys`)
2. Click "Add key"
3. Fill in:
   - **Key name**: Descriptive name (e.g., "GitHub API Key")
   - **Key type**: Authentication (or appropriate type)
   - **Key provider**: Choose based on your needs:
     - **Configuration**: Store in Drupal configuration
     - **File**: Store in a file outside web root
     - **Environment**: Read from server environment variable
4. Enter or configure the key value
5. Click "Save"

### 2. Reference in MCP Server

1. Edit your MCP server configuration
2. Under "Environment Variables", click "Add another item"
3. Fill in:
   - **Name**: The environment variable name expected by the MCP server
   - **Type**: Select "Key"
   - **Key**: Choose the key you created
4. Click "Save"

## Key Storage Options

### Configuration Storage

**Pros:**
- Simple setup
- Easy to manage
- Portable across environments

**Cons:**
- Stored in database (encrypted)
- Exported in configuration files
- Requires careful handling of config exports

**Best for:** Development and testing

### File Storage

**Pros:**
- Not in database
- Not in configuration exports
- Can use file permissions for security

**Cons:**
- Requires file system access
- Must manage file deployment
- File must be readable by web server

**Best for:** Production environments

**Example setup:**

```bash
# Create key file
echo "your-secret-key" > /var/www/keys/github-api.key

# Set permissions
chmod 400 /var/www/keys/github-api.key
chown www-data:www-data /var/www/keys/github-api.key

# In Key configuration
Path: /var/www/keys/github-api.key
```

### Environment Storage

**Pros:**
- Not in database or files
- Follows 12-factor app principles
- Easy to manage in containerized environments

**Cons:**
- Requires server/container configuration
- Different setup per environment
- May need hosting provider support

**Best for:** Cloud deployments, containerized apps

**Example setup:**

```bash
# In .env file (not in web root)
GITHUB_API_KEY=your-secret-key

# Or in Apache virtual host
SetEnv GITHUB_API_KEY "your-secret-key"

# Or in docker-compose.yml
environment:
  - GITHUB_API_KEY=your-secret-key
```

## Common Environment Variables

### Authentication

```
API_KEY: [Key reference]
API_SECRET: [Key reference]
AUTH_TOKEN: [Key reference]
CLIENT_ID: client-identifier
CLIENT_SECRET: [Key reference]
BEARER_TOKEN: [Key reference]
```

### Database Connections

```
DB_HOST: localhost
DB_PORT: 5432
DB_NAME: drupal_db
DB_USER: mcp_user
DB_PASSWORD: [Key reference]
DB_SSL: true
```

### Service Configuration

```
SERVICE_URL: https://api.example.com
SERVICE_TIMEOUT: 30
SERVICE_REGION: us-east-1
SERVICE_RETRY_COUNT: 3
```

### Feature Flags

```
ENABLE_CACHING: true
DEBUG_MODE: false
LOG_LEVEL: info
MAX_CONNECTIONS: 10
```

### Filesystem Paths

```
ALLOWED_PATHS: /var/www/files,/tmp/uploads
UPLOAD_DIR: /var/www/uploads
TEMP_DIR: /tmp/mcp
```

## Security Best Practices

### Do's

✅ Use Key module for all sensitive values  
✅ Use different keys for different environments  
✅ Rotate keys regularly  
✅ Use strong, random values for secrets  
✅ Restrict file permissions (400 or 600)  
✅ Store key files outside web root  
✅ Use environment variables in production  
✅ Document required environment variables

### Don'ts

❌ Never commit secrets to version control  
❌ Don't use plain text for sensitive values  
❌ Don't share keys between environments  
❌ Don't expose keys in error messages or logs  
❌ Don't store keys in publicly accessible locations  
❌ Don't use weak or predictable values  
❌ Don't hardcode secrets in code

## Environment Variable Examples

### Example 1: GitHub MCP Server

```yaml
Environment Variables:
  - Name: GITHUB_TOKEN
    Type: Key
    Key: github_personal_token
  
  - Name: GITHUB_OWNER
    Type: Plain text
    Value: myorganization
  
  - Name: RATE_LIMIT_DELAY
    Type: Plain text
    Value: 1000
```

### Example 2: Database MCP Server

```yaml
Environment Variables:
  - Name: DB_HOST
    Type: Plain text
    Value: localhost
  
  - Name: DB_NAME
    Type: Plain text
    Value: production_db
  
  - Name: DB_USER
    Type: Plain text
    Value: mcp_reader
  
  - Name: DB_PASSWORD
    Type: Key
    Key: db_mcp_password
  
  - Name: DB_SSL
    Type: Plain text
    Value: true
```

### Example 3: API Gateway MCP Server

```yaml
Environment Variables:
  - Name: API_ENDPOINT
    Type: Plain text
    Value: https://api.internal.com/v1
  
  - Name: API_KEY
    Type: Key
    Key: internal_api_key
  
  - Name: TIMEOUT_MS
    Type: Plain text
    Value: 5000
  
  - Name: MAX_RETRIES
    Type: Plain text
    Value: 3
```

## Troubleshooting

### Key Not Found

**Problem**: Error message "Key [name] not found"

**Solutions**:
- Verify the key exists at `/admin/config/system/keys`
- Check the key machine name matches exactly
- Ensure the key is not disabled
- Clear Drupal cache: `drush cr`

### Permission Denied (File Keys)

**Problem**: Cannot read key file

**Solutions**:
- Check file permissions: `ls -la /path/to/key`
- Ensure web server user can read: `chmod 400 /path/to/key`
- Set correct ownership: `chown www-data:www-data /path/to/key`
- Verify path is correct and absolute

### Environment Variable Not Set (STDIO)

**Problem**: MCP server doesn't receive environment variable

**Solutions**:
- Check variable name matches what the server expects
- Verify the variable type is set correctly
- Review process environment: Check Drupal logs
- Test manually: `sudo -u www-data env | grep VAR_NAME`

### Invalid Key Value

**Problem**: Key value is empty or invalid

**Solutions**:
- Edit the key and verify the value
- For file keys, check file contents
- For environment keys, verify the server environment variable is set
- Regenerate the key if compromised

## Testing Environment Variables

### Test STDIO Variables

Create a test script to verify environment variables:

```bash
#!/bin/bash
# test-env.sh
echo "API_KEY: $API_KEY"
echo "DEBUG_MODE: $DEBUG_MODE"
```

Configure as MCP server:
```
Command: /path/to/test-env.sh
Environment Variables:
  - API_KEY: [Key]
  - DEBUG_MODE: true
```

### Test HTTP Variables

Check HTTP headers sent to MCP server using the server's logs or a proxy.

## Advanced Configuration

### Variable Interpolation

Some MCP servers support variable interpolation:

```
DB_CONNECTION_STRING: postgresql://${DB_USER}:${DB_PASSWORD}@${DB_HOST}/${DB_NAME}
```

Check your MCP server's documentation for support.

### Conditional Variables

Set different variables based on environment:

```php
// In settings.php
if (getenv('ENVIRONMENT') === 'production') {
  $config['mcp_server.github']['env_vars']['LOG_LEVEL'] = 'error';
} else {
  $config['mcp_server.github']['env_vars']['LOG_LEVEL'] = 'debug';
}
```

## Next Steps

- [Tool Management](tool-management.md) - Enable and configure MCP tools
- [HTTP Transport](http-transport.md) - Configure HTTP servers
- [STDIO Transport](stdio-transport.md) - Configure STDIO servers
- [Troubleshooting](../troubleshooting/index.md) - Common issues and solutions
