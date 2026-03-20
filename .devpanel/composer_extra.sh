#!/usr/bin/env bash
set -eu -o pipefail
cd $APP_ROOT

# Currently the recipe is beta.
composer config minimum-stability dev

# Get FlowDrop UI and FlowDrop UI Agents (use 1.0.x-dev to match local dev branches)
composer require 'drupal/flowdrop_ui:1.0.x-dev@dev'
composer require 'drupal/flowdrop_ui_agents:1.0.x-dev@dev'
composer require 'drupal/ai_provider_openai:^1.2'
composer require 'drupal/tool:^1.0@alpha'

# Get the MCP Client
composer require 'drupal/mcp_client:^1.0@alpha' -W

# Get the AI Agents module
composer require 'drupal/ai_agent_agent:dev-1.0.x'

# Get the AI module and AI Agents dev version for latest features
composer require 'drupal/ai:1.4.x-dev@dev'
composer require 'drupal/ai_agents:1.3.x-dev@dev'

# Get AI Agents Experimental Collection (all 31 agent submodules)
# Installed via git clone + path repo due to d.o packagist namespace issue.
# See: https://www.drupal.org/project/project_composer/issues/3553545
if [ ! -d "modules/ai_agents_experimental_collection" ]; then
  git clone --branch 1.0.x --depth 1 https://git.drupalcode.org/project/ai_agents_experimental_collection.git modules/ai_agents_experimental_collection
fi
composer config repositories.ai-agents-experimental-collection path modules/ai_agents_experimental_collection
composer config --json repositories.drupal '{"name": "drupal", "type": "composer", "url": "https://packages.drupal.org/8", "exclude": ["drupal/ai_agents_experimental_collection"]}'
composer require 'drupal/ai_agents_experimental_collection:@dev' -W
