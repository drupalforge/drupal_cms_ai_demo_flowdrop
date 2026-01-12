# MCP Client

The MCP Client module enables Drupal to connect to Model Context Protocol
(MCP) servers and expose their tools to AI modules. MCP is a standardized
protocol for communication between AI applications and external tools/data
sources.

## Features

* **Multiple Transport Types**: HTTP and STDIO (local process) connections
* **AI Module Integration**: Seamless integration with Drupal AI module
* **Tool Management**: Easy-to-use UI for enabling/disabling tools
* **Multiple Servers**: Connect to multiple MCP servers simultaneously
* **Secure Credential Management**: Integration with Key module
* **Dynamic Plugin System**: Auto-discovered tools exposed as AI function call plugins

## Quick Start

```bash
# Install the module
composer require drupal/mcp_client
drush en mcp_client -y

# Navigate to admin UI
# Administration » Configuration » AI » MCP Servers
```

## Documentation

**📚 [Complete Documentation](https://project.pages.drupalcode.org/mcp_client/)**

- [Installation Guide](https://project.pages.drupalcode.org/mcp_client/getting-started/installation/)
- [Configuration](https://project.pages.drupalcode.org/mcp_client/getting-started/configuration/)
- [Quick Start Tutorial](https://project.pages.drupalcode.org/mcp_client/getting-started/quick-start/)
- [HTTP Transport Setup](https://project.pages.drupalcode.org/mcp_client/configuration/http-transport/)
- [STDIO Transport Setup](https://project.pages.drupalcode.org/mcp_client/configuration/stdio-transport/)
- [Environment Variables](https://project.pages.drupalcode.org/mcp_client/configuration/environment-variables/)
- [Tool Management](https://project.pages.drupalcode.org/mcp_client/configuration/tool-management/)
- [Troubleshooting](https://project.pages.drupalcode.org/mcp_client/troubleshooting/)

## Requirements

* PHP 8.2 or higher
* Drupal core 10.3+ or 11+
* [AI module](https://www.drupal.org/project/ai) 1.2.x or higher
* [Key module](https://www.drupal.org/project/key) 1.18 or higher

## Links

* [Project page](https://www.drupal.org/project/mcp_client)
* [Issue queue](https://www.drupal.org/project/issues/mcp_client)
* [Documentation](https://project.pages.drupalcode.org/mcp_client/)


## Maintainers

* Marcus Johansson ([marcus_johansson](https://www.drupal.org/u/marcus_johansson))
* Roberto Peruzzo ([robertoperuzzo](https://www.drupal.org/u/robertoperuzzo))
* James Abrahams ([yautja_cetanu](https://www.drupal.org/u/yautja_cetanu))
* Giorgi Jibladze ([jibla](https://www.drupal.org/u/jibla))
* Michael Lander ([michaellander](https://www.drupal.org/u/michaellander))
* Rob Loach ([robloach](https://www.drupal.org/u/robloach))

### Supporting Organizations

* [FreelyGive](https://www.drupal.org/freelygive)
* [Sparkfabrik](https://www.drupal.org/sparkfabrik)
