# Add MCP Tools to FlowDrop UI

- **Issue**: [#3567096](https://www.drupal.org/project/flowdrop_ui_agents/issues/3567096)
- **Branch**: `3567096-add-mcp-tools`
- **Status**: Future

## Goal

Allow MCP servers to provide tools and resources to FlowDrop UI for agents, with dynamic category generation, green styling, proper icons, and tool-like behavior (drag-to-canvas + auto-attach). MCP tool configuration should be built dynamically and saved through the existing FlowDrop UI agent workflow save path.

## Requirements

- MCP servers can provide **tools** and **resources** for FlowDrop UI.
- New **category type** for MCP tools/resources.
- MCP categories appear **after standard tools**.
- Some MCP servers need their **own category** if they return many tools.
- MCP tool config forms are generated dynamically.
- MCP tool config is saved with the rest of the FlowDrop workflow.
- MCP tools behave like tools (drag in, auto-attach to nearest agent).
- MCP category styling: **green** icon, plus consistent summary styling.
- Avoid the **blue focus line** issue for category headers.

## Discovery (what to check first)

- Where MCP server data is currently surfaced (if at all).
- Whether MCP resources should be represented as FlowDrop nodes or as tool configuration values.
- Current tool metadata schema (fields used by the FlowDrop UI tool config panel).
- Any existing MCP integration modules in the project.

## Implementation Plan

### 1) Data Source + Category Type

- Extend the API endpoint that provides sidebar nodes (currently `NodesController::getNodes()` + `getNodesByCategory()`).
- Add a **new category type** for MCP (e.g., `mcp`) in:
  - `modules/flowdrop_ui_agents/src/Controller/Api/NodesController.php`
  - `CATEGORY_TYPE_WEIGHTS` (ensure MCP categories sort **after tools**).
- Define how MCP categories are named:
  - Single category for all MCP tools (e.g., `MCP Tools`).
  - Or category per MCP server when tool count exceeds a threshold (e.g., `MCP: {server name}`).

**Ordering requirement**:
- MCP categories must appear **after tools**, not before. Use category type weights so `tools` (0) and `mcp` (e.g., 10) are ordered properly.

### 2) Category Construction (Dynamic)

- Build MCP categories dynamically using MCP server metadata:
  - Group by server name if tool count is high.
  - Otherwise use a single category.
- Populate MCP tool nodes with:
  - `id`, `label`, `description`, `category`, `nodeType` (tool-like), `configSchema`.
- Mirror existing tool metadata format to reuse the tool config UI.

### 3) UI Styling (Green + Icon)

- Sidebar category styling should use **green** accent:
  - Add a CSS rule for MCP categories similar to chatbot/agent categories.
  - Use a green palette (e.g., `var(--color-ref-emerald-500)` or `#10b981`).
- Set a distinct icon (e.g., plug/cube/server) for MCP categories:
  - Inject icon in `setupSidebarStyling()` in `modules/flowdrop_ui_agents/js/flowdrop-agents-editor.js`.
- Ensure the category header doesn’t show the blue focus ring:
  - Apply the same focus override approach used for the Chatbots category (`.flowdrop-details__summary:focus` with `box-shadow: none`).

### 4) Drag + Auto-Attach Behavior

- MCP tools should behave exactly like standard tools:
  - Drag onto canvas.
  - Auto-attach to nearest agent using the existing tool auto-attach logic.
- Ensure MCP nodes are detected as tools, not agents:
  - Use the same `nodeType` and `category` logic as standard tools.

### 5) Dynamic Config Form + Saving

- MCP tool configuration should use existing tool config panel logic:
  - Provide `configSchema` and defaults in tool metadata.
  - Ensure schema supports dynamic fields from MCP server definitions.
- Saving should be through the existing workflow save path:
  - No new save endpoint required.
  - Validate MCP tool config on save.

### 6) Tests

- Add tests for MCP category appearance and ordering.
- Add tests for MCP tool nodes appearing in sidebar.
- Add tests for MCP tool drag + auto-attach to agent.
- Add tests for MCP tool config schema (dynamic) and save.

## Technical Notes

### Creating a new category type

- Add a new entry in `CATEGORY_TYPE_WEIGHTS` and `CATEGORY_TYPE_MAP` in:
  - `modules/flowdrop_ui_agents/src/Controller/Api/NodesController.php`
- Ensure MCP categories are mapped to the new type.
- Use the weights to force MCP categories **after tools**.

### Blue focus line fix

- Known fix pattern in:
  - `modules/flowdrop_ui_agents/css/flowdrop-agents-editor.css`
  - Example used for Chatbots:
    ```css
    .flowdrop-chatbot-category .flowdrop-details__summary:focus { box-shadow: none !important; }
    ```
- Apply the same for MCP category classes.

### Styling (green + icon)

- CSS for category icon background:
  ```css
  [aria-label="Components sidebar"] .flowdrop-details[data-is-mcp="true"] .flowdrop-node-icon,
  .flowdrop-sidebar .flowdrop-details[data-is-mcp="true"] .flowdrop-node-icon {
      background-color: var(--color-ref-emerald-500, #10b981) !important;
  }
  ```

- JS to swap icon in `setupSidebarStyling()`:
  - Add `mcpSvg` and apply it for MCP category summaries.

### Dynamic config schema

- MCP tools should emit a config schema similar to standard tools:
  - `configSchema` + default values.
  - Fields should map to the FlowDrop config panel format.

## Validation Plan

- Sidebar shows MCP category after tools.
- MCP categories show green icon and correct label.
- MCP tools drag into canvas and auto-attach to nearest agent.
- MCP tool config form renders dynamic fields.
- Saving workflow persists MCP tool config without errors.

## Notes from Chatbot Integration

- Category styling and focus ring fixes are handled in `setupSidebarStyling()` + CSS.
- The sidebar reorders categories client-side; be prepared to reapply ordering if FlowDrop re-renders.
- Cache clear needed after JS/CSS edits (`ddev drush cr`).

## Git Commands

```
git remote add flowdrop_ui_agents-3567096 git@git.drupal.org:issue/flowdrop_ui_agents-3567096.git
git fetch flowdrop_ui_agents-3567096

git checkout -b '3567096-add-mcp-tools' --track flowdrop_ui_agents-3567096/'3567096-add-mcp-tools'
```
