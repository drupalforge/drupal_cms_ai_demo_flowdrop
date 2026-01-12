# MCP Client

The MCP Client module enables Drupal to connect to Model Context Protocol
(MCP) servers and expose their tools to AI modules. MCP is a standardized
protocol for communication between AI applications and external tools/data
sources.

This module integrates with the Drupal AI module to provide seamless access to
MCP server capabilities, allowing AI agents to use external tools like file
systems, databases, APIs, and more.

## What is MCP?

Model Context Protocol (MCP) is an open protocol that standardizes how AI applications communicate with external data sources and tools. It enables:

- Standardized tool integration
- Secure context sharing
- Multi-modal data exchange
- Provider-agnostic connectivity

## Key Features

* **Multiple Transport Types**: Supports both HTTP and STDIO (local process) connections
* **No Middleware Required**: Direct connection to MCP servers for HTTP transport
* **AI Module Integration**: Seamlessly integrates with Drupal AI module's function calling system
* **Tool Management**: Easy-to-use UI for enabling/disabling specific tools per server
* **Multiple Servers**: Connect to multiple MCP servers simultaneously
* **Secure Credential Management**: Integration with Key module for secure storage of API keys and sensitive environment variables
* **Dynamic Plugin System**: Tools from MCP servers are automatically discovered and exposed as AI function call plugins

## Requirements

This module requires the following:

* PHP 8.2 or higher (required by SwisNL MCP Client library)
* Drupal core 10.3+ or 11+
* [AI module](https://www.drupal.org/project/ai) 1.2.x or higher
* [AI Agents module](https://www.drupal.org/project/ai) 1.2.x or higher
* [Key module](https://www.drupal.org/project/key) 1.18 or higher
* SwisNL MCP Client library (automatically installed via Composer)

## Quick Links

* [Project page](https://www.drupal.org/project/mcp_client)
* [Issue queue](https://www.drupal.org/project/issues/mcp_client)
* [Getting Started](getting-started/installation.md)
* [Configuration Guide](getting-started/configuration.md)

## Getting Started

1. Install the module using Composer
2. Enable the module
3. Configure at least one MCP server
4. Enable desired tools
5. Start using MCP tools in your AI agents

For detailed instructions, see the [Installation Guide](getting-started/installation.md).

## Documentation

This documentation is generated using MkDocs from the source files located in
the `docs/` directory. To build the docs locally:

1. Install MkDocs: `pip install mkdocs mkdocs-material`
2. Run `mkdocs serve` in the project root
3. Open `http://localhost:8000` in your browser

## Support

For bug reports and feature requests, please use the [issue queue](https://www.drupal.org/project/issues/mcp_client).

## Maintainers

* Marcus Johansson ([marcus_johansson](https://www.drupal.org/u/marcus_johansson))
* Roberto Peruzzo ([robertoperuzzo](https://www.drupal.org/u/robertoperuzzo))
* James Abrahams ([yautja_cetanu](https://www.drupal.org/u/yautja_cetanu))
* Giorgi Jibladze ([jibla](https://www.drupal.org/u/jibla))
* Michael Lander ([michaellander](https://www.drupal.org/u/michaellander))
* Rob Loach ([robloach](https://www.drupal.org/u/robloach))

### Supporting organizations

* [FreelyGive](https://www.drupal.org/freelygive)
* [Sparkfabrik](https://www.drupal.org/sparkfabrik)
