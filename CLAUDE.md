# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Overview

This is a **Drupal CMS demo site** that showcases AI + FlowDrop integration. It has two distinct purposes:

1. **Live Demo**: Automatically deploys to DrupalForge cloud hosting
2. **Local Development**: Environment for developing the `flowdrop_ui_agents` module

**Critical**: The `flowdrop_ui_agents` module has its **own git repository** on drupal.org GitLab at `git@git.drupal.org:project/flowdrop_ui_agents.git`. Module development must follow Drupal.org workflow (issue → feature branch → merge request).

### Git Commands

**IMPORTANT: Never commit without explicit user confirmation.** When changes are ready:
1. Show the user what files changed (`git status`, `git diff --stat`)
2. Write a proposed commit message
3. Wait for user to confirm the changes work and approve the commit
4. Only then execute the commit command

Reminder: Do not commit anything unless the user explicitly asks you to commit.

Do not execute git commands that require authentication. Instead, provide the exact commands to the user for manual execution, ensuring they push to the correct remote (GitHub for this repo, drupal.org GitLab for the module).

## Two-Repo Workflow

```
┌─────────────────────────────────────────┐
│  This Repo (GitHub)                     │
│  drupal_cms_ai_demo_flowdrop            │
│                                         │
│  Purpose: Demo site, DrupalForge deploy │
│  Branch: main                           │
└─────────────────────────────────────────┘
              │
              │ ddev setup-flowdrop-dev
              ▼
┌─────────────────────────────────────────┐
│  modules/flowdrop_ui_agents/ (cloned)   │
│  ↓ symlinked to                         │
│  web/modules/contrib/flowdrop_ui_agents │
│                                         │
│  Origin: git.drupal.org                 │
│  Workflow: Issue → Feature branch → MR  │
└─────────────────────────────────────────┘
```

## Development Commands

### DDEV Environment

```bash
# Environment management
ddev start                    # Start DDEV environment
ddev stop                     # Stop DDEV environment
ddev restart                  # Restart all containers

# Drupal commands
ddev drush cr                 # Clear all caches
ddev drush cex                # Export configuration to config/sync/
ddev drush cim                # Import configuration from config/sync/
ddev drush updb               # Run database updates
ddev drush recipe <path>      # Apply a recipe

# Development setup
ddev setup-flowdrop-dev       # Set up FlowDrop development environment (see below)

# MCP Server (Claude Code integration)
ddev flowdrop-dev-mcp enable  # Enable MCP tools for agent development
ddev flowdrop-dev-mcp disable # Disable MCP tools
ddev flowdrop-dev-mcp status  # Check MCP status
```

**Site URL**: `http://drupal-cms-ai-demo-flowdrop.ddev.site`
**Default credentials**: admin / admin

### Environment Variables

OpenAI API key is required for AI features. Configured globally for DDEV and documented in the readme.md

## FlowDrop Module Development

### Initial Setup

```bash
# Clone the module and set up development environment
ddev setup-flowdrop-dev
```
This command:
1. Clones `flowdrop_ui_agents` from drupal.org into `modules/`
2. Removes the Composer-installed version from `web/modules/contrib/`
3. Symlinks `modules/flowdrop_ui_agents` → `web/modules/contrib/flowdrop_ui_agents`
4. Applies the AI recipe with your OpenAI API key
5. Enables development modules: Tool suite, AI explorer, logging, observability
6. Applies the Bundle Lister Demo recipe
7. Configures AI logging to capture all requests/responses

### Working with the Module

The module is now at `modules/flowdrop_ui_agents/` with its own git repository.

**Working directory**: Always `cd modules/flowdrop_ui_agents/` before git operations on the module.

### Drupal.org Workflow

1. Create issue at https://www.drupal.org/project/issues/flowdrop_ui_agents
2. Create feature branch: `git checkout -b {issue-number}-{short-description}`
   - Example: `3512345-add-deepchat-node`
3. Develop and test changes in `modules/flowdrop_ui_agents/`
4. Test changes are reflected in Drupal (via symlink to `web/modules/contrib/`)
5. Provide user the git commands to push to drupal.org GitLab
6. Create Merge Request on drupal.org referencing the issue

## Architecture

### Build Processes

This repository supports two deployment methods:

1. **DrupalForge (Production)**: Uses `.devpanel/init.sh` script for automated cloud deployment
2. **DDEV (Local)**: Uses `.ddev/config.yaml` hooks and custom commands for local development

Both processes run similar setup steps but with different environment variables and AI provider configurations.

### Key Directories

| Directory                          | Purpose                                              |
|------------------------------------|------------------------------------------------------|
| `modules/`                         | Local dev modules (git-ignored, cloned during setup) |
| `patches/`                         | Patches used during the build, README.me for process |
| `web/modules/contrib/`             | Composer-managed contrib modules (committed)         |
| `recipes/`                         | Drupal recipes for content types and AI agent demos  |
| `recipes/bundle_lister_demo/`      | Demo AI agent that lists content bundles             |
| `recipes/alt_text_evaluator_demo/` | Demo AI agent that evaluate image alt text           |
| `config/sync/`                     | Exported Drupal configuration                        |
| `.devpanel/`                       | DrupalForge deployment scripts                       |
| `.ddev/`                           | DDEV configuration and custom commands               |
| `.claude/`                         | Claude Code documentation and plans                  |

### Module Architecture

This demo showcases the integration of several AI and FlowDrop modules:

- **ai_agents**: Core AI agent framework with tools and orchestration
- **ai_assistant_api**: Wraps agents with LLM provider configuration
- **flowdrop**: Flow-based visual programming framework
- **flowdrop_ui**: React-based UI for FlowDrop visual editor
- **flowdrop_ui_agents**: Modeler API integration for visual AI agent design
- **ai_agents_explorer**: UI for browsing and testing agents
- **ai_logging**: Request/response logging for debugging
- **ai_observability**: Performance metrics for AI operations

### Common Pitfalls

Found in `/.claude/common-issues.md` including.

- Blue line under FlowDrop UI Components categories
- Flowdrop UI Component category styling (icon color, plus icon) (such as Chatbot)
- Sidebar category order (Sub-Agent Tools, Chatbots first)

#### Patches

Local patches that Composer applies via `patches.json` **and** local patches for vendored source (FlowDrop). Each patch comes with a corresponding md file that details the issue describing the patch that we will upload and try and get committed upstream.
The process and documentation for applying patches are detailed in `patches/README.md`. We use patches when:

- We need to update something that is gitignored and built during install such as contrib modules.
- We need to update something locally that is committed but would ideally be committed upstream such as with FlowDrop's source code.

#### FlowDrop JavaScript Build Files

FlowDrop ships TWO JavaScript builds in `web/modules/contrib/flowdrop/modules/flowdrop_ui/build/flowdrop/`:

| File | Format | Used By |
|------|--------|---------|
| `flowdrop.es.js` | ES Modules (`import`/`export`) | Bundlers (Vite, Webpack) |
| `flowdrop.iife.js` | IIFE (browser-compatible) | **Drupal** |

**CRITICAL**: Drupal's library system uses traditional `<script>` tags, NOT ES modules. When patching FlowDrop JavaScript, **always patch `flowdrop.iife.js`**, not `.es.js`.

To verify which file is loaded, check browser DevTools Network tab for `flowdrop.iife.js`.

#### FlowDrop Sidebar Data Source

The FlowDrop sidebar fetches nodes from `/api/flowdrop-agents/nodes` (flat list), NOT `/api/flowdrop-agents/nodes/by-category`. If you need data on each node for sidebar rendering (like `categoryWeight` for sorting), add it to `NodesController::getNodes()`.

#### FlowDrop Source Code - Reading and Editing

Sometimes you will need to see the source code and documentation for FlowDrop itself to make the changes that the User is requesting. Other times you may want to make changes to the source code and build the JS files directly.

Information about this process is documents in `.claude/flowdrop-build.md` - Instructions for finding the flowdrop source code and building a new copy

You should then create a patch and issue md file in the /patches/ directory following the rules in the readme.md.

- Source lives in `packages/flowdrop/` (vendored snapshot of `d34dman/flowdrop`).
- Drupal loads **built assets** from:
  - `web/modules/contrib/flowdrop/modules/flowdrop_ui/build/flowdrop/`
- DrupalForge deployments use the committed build assets (no npm build during
  deploy).

## Recipes System

Drupal CMS uses a "recipe" system for installing pre-configured functionality. Recipes define modules to install, configuration to import, and content to create.

### Key Recipes

#### Core AI Setup

- **drupal_cms_ai**: AI provider setup (OpenAI or LiteLLM), installs base AI modules and creates the main `drupal_cms_assistant` orchestration agent

#### Demo Recipes

- **bundle_lister_demo**: Demonstrates entity bundle listing
  - Agent: `bundle_lister_agent` - Lists content bundles (e.g., node types)
  - Tool used: `tool:entity_bundle_list`
  - Includes: Assistant configuration and DeepChat chatbot block
  - Purpose: Simple example of agent using a single tool

- **alt_text_evaluator_demo**: Demonstrates multi-tool agent with field operations
  - Agent: `evaluation_agent` - Evaluates alt text quality for media images
  - Tools used: `tool:entity_list`, `tool:entity_field_values`, `tool:field_set_value`, `tool:entity_save`
  - Adds fields: `field_alt_text_score` (integer 0-10), `field_ai_evaluation_explanation` (text)
  - Includes: Editorial workflow for media, form/view display configuration
  - Purpose: Complex example showing entity CRUD operations and field manipulation

### Recipe Structure

```
recipes/{recipe_name}/
├── recipe.yml           # Metadata, dependencies, modules to install
├── config/              # Configuration files to import
│   └── {module}.{config_type}.{config_id}.yml
└── content/            # Optional default content
    └── {entity_type}/{entity}.yml
```

### Applying Recipes

```bash
# From within DDEV
ddev drush recipe ../recipes/bundle_lister_demo
ddev drush recipe ../recipes/alt_text_evaluator_demo

# With input variables
ddev drush recipe ../recipes/drupal_cms_ai --input=drupal_cms_ai.provider=openai
```

### Creating New Recipes

When creating a new AI agent demo recipe:

1. **Create directory structure**:
   ```bash
   mkdir -p recipes/your_demo/config
   ```

2. **Create recipe.yml** with required sections:
   ```yaml
   name: 'Your Demo Name'
   description: 'Description of what this demo does'
   type: 'AI Demo'

   install:
     - ai_agents          # Always required for agents
     - tool               # Required if using tool modules
     - tool_entity        # Required for entity operations
     # Add other module dependencies

   config:
     # Be strict about field storages (database changes)
     strict:
       - field.storage.*.your_field_name
     import:
       ai_agents:
         - ai_agents.ai_agent.your_agent
       field:
         - field.storage.*.your_field
         - field.field.*.your_field
     actions:
       # Use actions (not imports) for form/view displays
       core.entity_form_display.*.bundle.*:
         setComponents:
           - name: your_field
             options:
               type: field_widget_type
               weight: 10
   ```

3. **Export agent configuration**:
   ```bash
   ddev drush config:export --destination=../recipes/your_demo/config
   # Then move only the relevant configs to the recipe config folder
   ```

4. **Key patterns**:
   - Use `config: import:` for new configs (agents, fields, workflows)
   - Use `config: actions:` for modifying existing configs (form/view displays)
   - Use `strict:` to list only field storages (avoids conflicts with existing configs)
   - Use wildcards in actions (`core.entity_form_display.*`) to apply to all display modes

5. **Test the recipe**:
   ```bash
   ddev drush recipe ../recipes/your_demo --no-interaction
   ```

## Issue Trackers

| Project | Issues |
|---------|--------|
| flowdrop_ui_agents module | https://www.drupal.org/project/issues/flowdrop_ui_agents |
| This demo repository | https://github.com/drupalforge/drupal_cms_ai_demo_flowdrop/issues |

## Claude Documentation Structure

The `.claude/` directory contains documentation and context for AI-assisted development.

### File Structure

```
.claude/
├── branch.md                    # Current branch context (update when switching)
├── branch-review.md             # Review agent findings (wiped each review)
├── common-issues.md             # Common issues and solutions
├── flowdrop-ui-agents.md        # Module architecture reference for the primary module we are developing
├── mcp.md                       # MCP server integration docs (tool list, architecture)
├── planning.md                  # Historical roadmap and phases
├── flowdrop-build.md            # Instructions for finding the flowdrop source code and building a new copy of it.
├── plans/                       # Issue-linked implementation plans
│   ├── README.md                # Naming conventions & template
│   ├── do-XXXXX-*.md            # Drupal.org issue plans
│   └── gh-XXXXX-*.md            # GitHub issue plans
└── screenshots/                 # Screenshots for AI analysis
    └── README.md
```

### Plan File Naming

Plans are named to match the branch name in the module repository:

- **Drupal.org issues**: `do-{issue-number}-{short-description}.md`
  - Example: `do-3512345-add-deepchat-node.md`
  - Matches branch: `3512345-add-deepchat-node`
  - Issue: https://www.drupal.org/project/flowdrop_ui_agents/issues/3512345

- **GitHub issues**: `gh-{issue-number}-{short-description}.md`
  - Example: `gh-42-update-demo-config.md`
  - Issue: https://github.com/drupalforge/drupal_cms_ai_demo_flowdrop/issues/42

### Multi-Agent Workflow

1. **Primary agent**: Works on branch, updates `branch.md`, creates/updates plan file - although often they work directly in the issue plan file now.
2. **Review agent**: Reviews work, documents findings in `branch-review.md` - Although often they work directly in the issue plan file now in a new section.
3. **Technical notes**: All implementation notes and discoveries go in the plan file
4. **Screenshots**: Place in `.claude/screenshots/` for AI visual analysis

### Key Reference Files

- **flowdrop-ui-agents.md**: Detailed module architecture, data structures, APIs, and common gotchas
- **mcp.md**: MCP server integration documentation (tool list, architecture)
- **branch.md**: Current working context, updated when switching branches (tend to use the issue plan md files more than branch.md now)
- **plans/{issue}.md**: Issue-specific implementation plan with tasks and technical notes
- **common-issues.md**: Common issues and solutions encountered during development

### Keeping mcp.md Updated

The tool/agent list in `.claude/mcp.md` is **reference documentation** - a snapshot for human readers. Claude Code sees tools **dynamically** via the MCP protocol's `tools/list` method, so new agents/tools are automatically available when MCP is enabled.

**When to update mcp.md:**
- When a new module adds significant tools (e.g., installing a new `tool_*` module)
- When creating agents that will be part of the standard install (not test-only agents)
- When the MCP architecture or configuration changes

**You don't need to update mcp.md for:**
- Temporary test agents created during development
- Agents that won't be committed to the repository
