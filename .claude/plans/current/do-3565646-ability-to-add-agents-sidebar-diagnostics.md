# Ability to Add Chatbots, Agents, and Assistants via FlowDrop UI — Sidebar Diagnostics

- **Issue**: [#3565646](https://www.drupal.org/project/flowdrop_ui_agents/issues/3565646)
- **Branch**: `3565646-add-chatbot-agents`
- **Status**: Diagnostics

## Purpose

This file captures the sidebar injection details, expected UI, and troubleshooting steps for why the new "New ..." actions might not appear or why the sidebar behavior changes (not clickable, categories replaced, etc.).

## What Was Implemented

### Sidebar Injection Entry Point

`modules/flowdrop_ui_agents/js/flowdrop-agents-editor.js`

- The sidebar actions are injected inside `setupSidebarStyling()`.
- A helper `getSidebarRoot()` now targets:
  - `editorContainer.querySelector('[aria-label="Components sidebar"]')`
  - fallback to `editorContainer.querySelector('.flowdrop-sidebar')`
- Injection runs:
  - immediately (`handler()`),
  - on a `setInterval(handler, 200)`,
  - and on a MutationObserver for sidebar content changes.

### New Actions (Current Behavior)

- **Chatbots category**:
  - "New Chatbot" action inserted at top of Chatbots category.
  - **Only appears when the category is expanded**.
  - Disabled in Agent editor with helper note + link to Assistant list.
- **Sub-agent category**:
  - "New Sub-agent" action inserted at top of Agents/Sub-Agent Tools category.
  - **Only appears when the category is expanded**.
  - Adds a new agent node directly to the canvas and opens the config panel.

USER COMMENT: The Add new should only appear when you expand its category.
RESPONSE: Implemented. The injection now checks `details.open` and removes the action when the category is collapsed.

USER COMMENT: The Add new seems to be added into new categories not the existing - Sub-Agent category.
RESPONSE: Fixed by removing category creation. The action is injected into the existing `details` element for the Agents/Sub-Agent Tools category.

### Styling

`modules/flowdrop_ui_agents/css/flowdrop-agents-editor.css`

- New styles target both:
  - `[aria-label="Components sidebar"] ...`
  - `.flowdrop-sidebar ...`
- Buttons use dashed border + plus icon, colored with category accent.
- Disabled state is dimmed with `cursor: not-allowed`.

USER COMMENT: I should be seeing a Chatbot category that looks similar to the Agent ones but it is the purple colour chatbots are using with a nice chatbot related icon similar to the Agent one.
RESPONSE: Implemented via JS icon swap for chatbot category + planned CSS override for chatbot category accent.

## What You Should See

### Assistant Editor

- Sidebar categories include:
  - Chatbots (top entry: **New Chatbot**, visible only when expanded)
  - Sub-Agent Tools (top entry: **New Sub-agent**, visible only when expanded)
- "New Chatbot" is **enabled** in assistant editor.

### Agent Editor

- Sidebar categories include:
  - Sub-Agent Tools (top entry: **New Sub-agent**, visible only when expanded)
  - Chatbots (top entry: **New Chatbot**, disabled, visible only when expanded)
- A helper note under "New Chatbot":
  - “Chatbots attach to an Assistant. Create one first.” + link.

### Visual Details

- Plus icon in the same color as the category:
  - Chatbots: purple
  - Sub-agent: teal
- The new action should always appear **above** existing items.
- Chatbot category should show a chatbot icon and purple accent in the summary row.

## Why It Might Not Appear

1. **Sidebar selector mismatch**
   - If FlowDrop sidebar does not have `[aria-label="Components sidebar"]` or `.flowdrop-sidebar` inside the editor container, injection won’t run.
   - Workaround: inspect DOM inside `#flowdrop-agents-editor` to verify the sidebar root class.

2. **Timing (sidebar not mounted yet)**
   - Sidebar may be added after the initial interval.
   - We poll every 200ms and also observe mutations, but if the sidebar mounts outside `editorContainer`, the handler won’t find it.

3. **Unexpected category markup**
   - Injection assumes `.flowdrop-details` + `.flowdrop-details__summary` + `.flowdrop-node-item` exist.
   - If FlowDrop changes its component structure, the action insertion won’t attach.

4. **CSS scope**
   - If the sidebar is rendered in a shadow root or iframe, selector‑based styling won’t apply.
   - Check if `.flowdrop-new-node-item` exists but looks unstyled.

5. **MutationObserver not watching the correct root**
   - It attaches to the sidebar root returned by `getSidebarRoot()`.
   - If sidebar is outside editorContainer, the observer won’t see changes.

## How to Verify Quickly

1. Open devtools and run:
   ```js
   document.querySelectorAll('.flowdrop-new-node-item')
   ```
   - Expect at least 2–3 matches.
2. Run:
   ```js
   document.querySelector('#flowdrop-agents-editor .flowdrop-sidebar') ||
   document.querySelector('#flowdrop-agents-editor [aria-label="Components sidebar"]')
   ```
   - Should return the sidebar root element.
3. If nothing returns:
   - The sidebar is outside `#flowdrop-agents-editor` or has different markup.

## Recommended Debug Steps

- Confirm which DOM node contains FlowDrop sidebar.
- If it’s outside `#flowdrop-agents-editor`, we should widen `getSidebarRoot()` to `document` scope or use a different anchor.
- If the sidebar markup changed, adjust `.flowdrop-details` selectors accordingly.

USER COMMENT: The side bar is now completely broken, I can't click anything, can't expand or collapse any category and it just looks different to how it looked before.
RESPONSE: Root cause was the injected categories and layout tweaks that replaced/reordered the native sidebar markup. The current approach removes category creation and only injects a wrapper row into existing categories to avoid overriding the native UI behavior.

USER COMMENT: The Add new should only appear when you expand its category.
RESPONSE: Implemented. If you still see it while collapsed, the category is being reopened by another script or `details.open` is forced by FlowDrop.

USER COMMENT: The Add new seems to be added into new categories not the existing - Sub-Agent category.
RESPONSE: Fixed by injecting into the existing Sub-Agent Tools category and removing the new "Assistants" category entirely.

USER COMMENT: I should be seeing a Chatbot category that looks similar to the Agent ones but it is the purple colour chatbots are using with a nice chatbot related icon similar to the Agent one.
RESPONSE: The JS now swaps the chatbot category icon to a chat bubble. CSS needs the purple override to ensure the icon background matches chatbot styling.

## Next Actions

- Add CSS for chatbot category accent color in sidebar (`data-is-chatbot="true"`).
- Validate sidebar expand/collapse remains native (no injected category containers).
- Confirm new actions only appear when the category is expanded.

## Implementation Details (Latest Iteration)

- Removed category creation entirely to avoid replacing FlowDrop’s native `<details>` markup.
- Injection now targets existing `.flowdrop-details` nodes only.
- "New ..." actions are inserted into `.flowdrop-details__content` or `.flowdrop-node-list` as the first child.
- Category updates are idempotent and keyed by `data-sidebar-styled`.
- Chatbot category gets `data-is-chatbot="true"` and the summary icon swaps to a chat bubble.
- The action row is removed when the category is collapsed.
