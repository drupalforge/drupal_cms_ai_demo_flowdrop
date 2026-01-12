# Troubleshooting

This guide covers common issues and their solutions when working with the MCP Client module.

## Connection Issues

### "Could not fetch tools from server"

This error appears when the module cannot connect to or communicate with an MCP server.

#### For HTTP Transport

**Symptoms:**
- Error when saving MCP server configuration
- Tools list is empty
- Connection timeout errors

**Solutions:**

1. **Verify endpoint URL**
   ```bash
   curl -X POST https://your-mcp-server.com/mcp \
     -H "Content-Type: application/json" \
     -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}'
   ```

2. **Check server is running**
   - Verify the MCP server process is running
   - Check server logs for errors
   - Ensure server is listening on the correct port

3. **Verify network connectivity**
   - Check firewall rules allow outbound connections
   - Verify DNS resolves correctly: `nslookup your-server.com`
   - Test from the Drupal server: `ping your-server.com`

4. **Check SSL certificates** (for HTTPS)
   - Verify certificate is valid: `curl -vI https://your-server.com`
   - Check certificate is not expired
   - Ensure certificate chain is complete

5. **Review timeout settings**
   - Increase timeout value if server is slow
   - Try 60-120 seconds for initial testing

#### For STDIO Transport

**Symptoms:**
- Error when saving MCP server configuration
- Process starts but no tools appear
- "Command not found" errors

**Solutions:**

1. **Test command manually**
   ```bash
   # Switch to web server user
   sudo -u www-data bash

   # Test the command
   node /path/to/mcp-server/index.js

   # Test with STDIO
   echo '{"jsonrpc":"2.0","id":1,"method":"tools/list"}' | node /path/to/server.js
   ```

2. **Verify file paths**
   ```bash
   # Check file exists
   ls -la /path/to/mcp-server/index.js

   # Check execute permissions
   ls -la $(which node)
   ```

3. **Check permissions**
   ```bash
   # Verify ownership
   ls -la /path/to/mcp-server/

   # Fix if needed
   chown -R www-data:www-data /path/to/mcp-server/
   chmod +x /path/to/mcp-server/index.js
   ```

4. **Review process output**
   - Check Drupal logs: **Administration » Reports » Recent log messages**
   - Look for stderr output from the process
   - Check for missing dependencies

5. **Verify working directory**
   - Ensure working directory exists
   - Check web server user can access it
   - Set correct working directory in configuration

## Server Status Issues

### "MCP server is not enabled"

**Problem**: Trying to use a disabled MCP server.

**Solution:**

1. Navigate to **Administration » Configuration » AI » MCP Servers**
2. Find your server in the list
3. Click "Edit"
4. Check the "Enabled" checkbox
5. Click "Save"

### Server Appears Enabled But Tools Don't Work

**Solutions:**

1. **Clear cache**
   ```bash
   drush cr
   ```

2. **Verify connection**
   - Test in API Explorer
   - Check recent log messages
   - Review server logs

## Tool Issues

### "MCP tool is not enabled"

**Problem**: Trying to call a disabled tool.

**Solution:**

1. Navigate to server configuration
2. Scroll to "Enabled tools" section
3. Check the box for the desired tool
4. Click "Save"

### Tools Don't Appear in API Explorer

**Symptoms:**
- MCP server is configured and enabled
- Tools list shows tools are enabled
- Tools don't appear in API Explorer

**Solutions:**

1. **Clear cache**
   ```bash
   drush cr
   ```

2. **Verify server is enabled**
   - Check server status in MCP Server list
   - Ensure "Enabled" is checked

3. **Check AI module integration**
   - Verify AI module is enabled
   - Check AI module configuration
   - Review AI module logs

4. **Refresh plugin cache**
   ```bash
   drush cr
   drush php-eval "\\Drupal::service('plugin.manager.ai.function')->clearCachedDefinitions();"
   ```

### Tool Execution Fails

**Symptoms:**
- Tool appears in API Explorer
- Calling tool returns an error
- Error messages in logs

**Solutions:**

1. **Check parameters**
   - Verify all required parameters are provided
   - Check parameter types match schema
   - Review parameter format

2. **Review error messages**
   - Check Drupal logs for detailed errors
   - Look for MCP server error responses
   - Review parameter validation errors

3. **Test with minimal parameters**
   - Start with required parameters only
   - Add optional parameters one at a time
   - Identify which parameter causes issues

4. **Verify MCP server health**
   - Check server logs
   - Test tool directly on MCP server
   - Verify server isn't overloaded

## Authentication Issues

### Environment Variables Not Working

**Symptoms:**
- MCP server returns authentication errors
- API keys seem not to be passed
- "Unauthorized" or "403" errors

**Solutions:**

1. **Verify Key module configuration**
   ```bash
   drush config:get key.key.your_key_name
   ```

2. **Check key exists and is accessible**
   - Navigate to **Administration » Configuration » System » Keys**
   - Verify key exists
   - Test key value retrieval
   - Check key permissions

3. **Review environment variable configuration**
   - Edit MCP server configuration
   - Verify variable names match what server expects
   - Ensure "Type" is set to "Key" for sensitive values
   - Confirm correct key is selected

4. **For STDIO: Test environment**
   ```bash
   # Create test script
   echo '#!/bin/bash' > /tmp/test-env.sh
   echo 'env' >> /tmp/test-env.sh
   chmod +x /tmp/test-env.sh

   # Run as web server user
   sudo -u www-data /tmp/test-env.sh
   ```

5. **For HTTP: Check headers**
   - Review MCP server logs to see what headers are received
   - Verify authentication method matches server expectations
   - Test with curl including headers

## Performance Issues

### Slow Tool Execution

**Symptoms:**
- Tools take long time to execute
- Timeout errors
- Agent operations are slow

**Solutions:**

1. **Increase timeout**
   - Edit MCP server configuration
   - Increase timeout value (60-120 seconds)
   - Save and test

2. **Check server performance**
   - Monitor MCP server resource usage
   - Check for slow database queries
   - Review server logs for performance issues

3. **Optimize tool calls**
   - Reduce unnecessary tool calls
   - Cache results when possible
   - Use batch operations if available

4. **Monitor network latency** (HTTP)
   ```bash
   curl -w "@curl-format.txt" -o /dev/null -s https://your-server.com
   ```

### High Memory Usage

**Symptoms:**
- PHP memory errors
- Server running out of memory
- Slow performance

**Solutions:**

1. **Increase PHP memory limit**
   ```php
   // In settings.php
   ini_set('memory_limit', '512M');
   ```

2. **Limit concurrent connections**
   - Reduce number of enabled MCP servers
   - Disable unused tools
   - Implement queueing for heavy operations

3. **Monitor process memory** (STDIO)
   ```bash
   ps aux | grep mcp
   top -p $(pgrep -f "mcp-server")
   ```

## Installation Issues

### PHP Version Error

**Error**: "MCP Client requires PHP 8.2 or higher"

**Solution:**

Upgrade PHP to version 8.2 or higher:

```bash
# Check current version
php -v

# Ubuntu/Debian
sudo apt-get install php8.2

# Update Drupal to use new PHP version
# (varies by web server configuration)
```

### Composer Installation Fails

**Solutions:**

1. **Memory issues**
   ```bash
   COMPOSER_MEMORY_LIMIT=-1 composer require drupal/mcp_client
   ```

2. **Dependency conflicts**
   ```bash
   composer require drupal/mcp_client --with-dependencies
   composer update --with-dependencies
   ```

3. **Clear Composer cache**
   ```bash
   composer clear-cache
   composer require drupal/mcp_client
   ```

## Debugging

### Enable Debug Logging

1. **Enable Drupal logging**
   ```php
   // In settings.php
   $config['system.logging']['error_level'] = 'verbose';
   ```

2. **Watch logs**
   ```bash
   drush watchdog:tail
   # or
   tail -f /path/to/drupal/sites/default/files/logs/drupal.log
   ```

3. **For STDIO processes**
   - Add DEBUG environment variable
   - Review process output in logs
   - Use process monitoring tools

### Testing Checklist

When troubleshooting, work through this checklist:

- [ ] Module is installed and enabled
- [ ] Dependencies are met (PHP version, required modules)
- [ ] MCP server configuration is saved
- [ ] MCP server is enabled
- [ ] Connection test succeeds (manual curl/command test)
- [ ] Tools are discovered and listed
- [ ] Desired tools are enabled
- [ ] Environment variables are configured (if needed)
- [ ] Keys are created and accessible (if needed)
- [ ] Cache is cleared
- [ ] Logs show no errors
- [ ] Test in API Explorer works

## Getting Help

If you're still experiencing issues:

1. **Check issue queue**
   - Visit [https://www.drupal.org/project/issues/mcp_client](https://www.drupal.org/project/issues/mcp_client)
   - Search for similar issues
   - Review open and closed issues

2. **Gather information**
   - PHP version: `php -v`
   - Drupal version: `drush status`
   - Module version: `drush pm:list | grep mcp_client`
   - Error messages from logs
   - Steps to reproduce

3. **Create issue**
   - Provide detailed information
   - Include error messages
   - Describe expected vs actual behavior
   - List troubleshooting steps tried

4. **Community support**
   - Drupal Slack: #ai channel
   - Stack Exchange: [drupal.stackexchange.com](https://drupal.stackexchange.com)
   - Drupal forums

## Next Steps

- [Configuration Guide](../getting-started/configuration.md)
- [HTTP Transport](../configuration/http-transport.md)
- [STDIO Transport](../configuration/stdio-transport.md)
