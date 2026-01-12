# Installation

## Requirements

Before installing the MCP Client module, ensure your system meets these requirements:

* PHP 8.2 or higher (required by SwisNL MCP Client library)
* Drupal core 10.3+ or 11+
* Composer for dependency management

## Required Modules

The following modules will be automatically installed as dependencies:

* [AI module](https://www.drupal.org/project/ai) 1.2.x or higher
* [AI Agents module](https://www.drupal.org/project/ai) 1.2.x or higher
* [Key module](https://www.drupal.org/project/key) 1.18 or higher

## Installation Steps

### 1. Install via Composer

The recommended way to install the MCP Client module is via Composer:

```bash
composer require drupal/mcp_client
```

This command will:

- Download the MCP Client module
- Install all required dependencies including the SwisNL MCP Client library
- Add the module to your Drupal installation

### 2. Enable the Module

Enable the module using Drush:

```bash
drush en mcp_client -y
```

Or enable it through the Drupal admin interface:

1. Navigate to **Administration » Extend** (`/admin/modules`)
2. Find "MCP Client" in the module list
3. Check the box next to it
4. Click "Install"

### 3. Verify Installation

After installation, verify that the module is properly installed:

```bash
drush pm:list --type=module --status=enabled | grep mcp_client
```

You should see `mcp_client` listed as enabled.

## Post-Installation

After successful installation, proceed to:

1. [Configure your first MCP server](configuration.md)
2. [Quick Start Guide](quick-start.md)

## Troubleshooting

### PHP Version Error

If you encounter an error about PHP version requirements:

```
MCP Client requires PHP 8.2 or higher
```

You need to upgrade your PHP version. The SwisNL MCP Client library requires PHP 8.2 or higher.

### Composer Memory Issues

If you encounter memory errors during installation:

```bash
COMPOSER_MEMORY_LIMIT=-1 composer require drupal/mcp_client
```

### Missing Dependencies

If dependencies are not automatically installed, try:

```bash
composer update drupal/mcp_client --with-dependencies
```

## Next Steps

Once installed, continue to the [Configuration Guide](configuration.md) to set up your first MCP server.
