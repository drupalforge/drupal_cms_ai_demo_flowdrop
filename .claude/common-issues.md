# Common Issues

## Blue line under FlowDrop UI Components categories

### Symptom
- A thin blue line (focus ring) appears under the a Component category such as chatbot header when clicked or focused.

### Cause
- The Chatbots category is injected via cloned markup and ends up getting a focus box-shadow on the category `<summary>`.
- Sub-Agent Tools does not show the same focus ring because its summary does not receive the same focus styles.

### Fix
- Remove the focus box-shadow for the Chatbots category summary only.
- Location:
  - `modules/flowdrop_ui_agents/css/flowdrop-agents-editor.css`
- Rule used:
  ```css
  [aria-label="Components sidebar"] .flowdrop-chatbot-category .flowdrop-details__summary:focus,
  [aria-label="Components sidebar"] .flowdrop-chatbot-category .flowdrop-details__summary:focus-visible,
  .flowdrop-sidebar .flowdrop-chatbot-category .flowdrop-details__summary:focus,
  .flowdrop-sidebar .flowdrop-chatbot-category .flowdrop-details__summary:focus-visible {
      box-shadow: none !important;
  }
  ```

## Flowdrop UI Component category styling (icon color, plus icon) (such as Chatbot)

### Where to change Chatbot sidebar styling
- **JS injection (category + icon)**:
  - `modules/flowdrop_ui_agents/js/flowdrop-agents-editor.js`
  - Inside `setupSidebarStyling()` and `injectCreateActions()`.
  - The Chatbot icon is set by `chatbotSvg` and applied to the category summary icon.
  - The “New Chatbot” button uses `plusSvg` for the icon.

### Where to change Flowdrop UI Component sidebar colors (such as Chatbot)
- **CSS for category icon color**:
  - `modules/flowdrop_ui_agents/css/flowdrop-agents-editor.css`
  - Selector:
    ```css
    [aria-label="Components sidebar"] .flowdrop-details[data-sidebar-styled][data-is-chatbot="true"] .flowdrop-node-icon,
    .flowdrop-sidebar .flowdrop-details[data-sidebar-styled][data-is-chatbot="true"] .flowdrop-node-icon {
        background-color: var(--color-ref-purple-500, #a855f7) !important;
    }
    ```

### Where to change Flowdrop UI Component sidebar colors
- **CSS for category icon color**:
  - `modules/flowdrop_ui_agents/css/flowdrop-agents-editor.css`
  - Selector:
    ```css
    [aria-label="Components sidebar"] .flowdrop-details[data-sidebar-styled][data-is-chatbot="true"] .flowdrop-node-icon,
    .flowdrop-sidebar .flowdrop-details[data-sidebar-styled][data-is-chatbot="true"] .flowdrop-node-icon {
        background-color: var(--color-ref-purple-500, #a855f7) !important;
    }
    ```

### Where to change Chatbot node styling on canvas
- **Canvas node styling**:
  - `modules/flowdrop_ui_agents/css/flowdrop-agents-editor.css`
  - Look for the block starting with:
    ```css
    .svelte-flow__node[data-id^="chatbot_"]
    ```

## Sidebar category order (Sub-Agent Tools, Chatbots first)

### Symptom
- Category order does not respect server-side sorting. Sub-Agent Tools/Chatbots are not at the top.

### Cause
- FlowDrop sidebar re-sorts categories client-side, ignoring the server order.

### Fix
- Reorder categories in the sidebar DOM after render.
- Location:
  - `modules/flowdrop_ui_agents/js/flowdrop-agents-editor.js`
  - `setupSidebarStyling()` → `reorderSidebarCategories()`
- Current behavior:
  - Sub-Agent Tools first, Chatbots second, then the rest.

## Cache note
- After JS/CSS edits run:
  - `ddev drush cr`
