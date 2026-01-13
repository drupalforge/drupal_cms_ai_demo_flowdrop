# Ability to Add Chatbots, Agents, and Assistants via FlowDrop UI

- **Issue**: [#3565646](https://www.drupal.org/project/flowdrop_ui_agents/issues/3565646)
- **Branch**: `3565646-add-chatbot-agents`
- **Status**: In progress

## Tool Saving Bugs (Regression)

### Reported symptoms
- FlowDrop UI does not reflect existing tool config on Bundle Lister Agent:
  - `node_type` property restriction (forced to "node") not shown.
  - "Override property description" (set to "cheese") not shown.
- Saving edits in the Agent route does not persist to tool config.

### Goals
- Ensure FlowDrop UI loads and displays tool restrictions/overrides for agents.
- Ensure saving from Agent route persists tool config changes.
- Add regression tests that would have caught this.

### Immediate investigation checklist
- Compare module code with `main` to pick up fixes from [#3566777](https://www.drupal.org/project/flowdrop_ui_agents/issues/3566777).
- Verify API payload for the Bundle Lister Agent includes the restriction/override data.
- Trace hydration of tool config into FlowDrop UI (agent route).
- Confirm save path maps UI changes back into the workflow/agent config.

### Hypotheses + falsifiable tests
1) **Hydration mismatch**: tool restriction fields exist in API response but are dropped in the UI mapper.
   - Test: log/inspect workflow payload in `WorkflowControllerTest` to confirm restriction data exists.
2) **Save mapper mismatch**: UI changes saved but dropped before persistence.
   - Test: create a kernel test that saves forced values + override description and confirms config is persisted.
3) **Old module code**: regression already fixed in `main` but not in this branch.
   - Test: git diff against `main` for `modules/flowdrop_ui_agents` and bring in missing commits.

### Tests to add
- Kernel test: tool restriction load + save for agent route (force value + description override).
- Kernel test: persisted config after save reflects restriction values.

## Goal

Add clear, category-level creation controls in the FlowDrop UI sidebar so users can create new Chatbots and Sub-agents directly from the editor, with the proper editing scope rules enforced.

## Progress Summary

### Done
- Chatbots category injected when missing; styled with purple icon.
- Sidebar "New Sub-agent" and "New Chatbot" actions added at top of their categories with plus icon.
- "New Chatbot" gated when no assistant exists; helper text links to assistant listing in a new tab.
- New Chatbot and New Sub-agent can be dragged onto the canvas (drop placement supported).
- Assistants category/actions removed from Agent route sidebar.
- Category ordering nudged so Sub-Agent Tools + Chatbots appear first.
- Cache clear before FlowDrop mount to reduce "No node types" startup flakiness.

### Not Done
- Dragging an existing agent on the canvas should carry downstream nodes and preserve relative positions.
- Tests for sidebar actions, drag-to-create, and downstream drag behavior.

## Requirements

### Create New Functionality
1. Ability to create a **new Chatbot** from sidebar
2. Ability to create a **new Sub-agent** from sidebar
3. UI makes "Create New" clear near components

USER COMMENT: Agents and Sub-Agents are the same thing in Drupal. We should call it sub-agent in FlowDrop because you get here via editing an assistant or agent and you attach it downstream. To create a new agent from scratch, use the Agent listing screen.
RESPONSE: Confirmed. I will label the sidebar action as "New Sub-agent" and keep "New Agent" out of FlowDrop UI, since full agent creation stays on the listing screen.

We need to figure out how someone working on an Agent would know they need an assistant if they want to make a chatbot. I Think maybe if you're in assistant the new chatbot will tell you that you need to create an assistant first and give you a link to the assistant listings page?
RESPONSE: Implemented: when in Agent editor, disable "New Chatbot" with inline text and a link to assistant listing (opens in new tab).

### Editing Scope Rules
This is about making it so when you drag in an existing agent, it will bring in and render all its attached tools.
6. **Agent editing**: Only edit downstream (agent + its tools), NOT upstream
7. **Assistant editing**: Edit BOTH upstream (chatbots) AND downstream (agents/tools)
8. When you edit an agent, you don't see/edit assistants that use it
9. When you edit an assistant, you see and can edit attached chatbots
10. When you drag an existing agent into the flow, its downstream nodes (tools/sub-agents) should be pulled in too and keep their relative positions.

### Constraints
10. Only 1 assistant per flow (validation)
11. Agents can be used by multiple assistants (but FlowDrop doesn't show this - each assistant is its own flow)

## UX Concepts

### Sidebar "Create New" UI

Creation controls should appear at the **top** of each category and use a **plus icon** colored to match the category.

**Option A: Category Headers with + Button**
```
┌─────────────────────────┐
│ CHATBOTS            [+] │
│   └ My Unassigned Bot   │
│ AGENTS              [+] │
│   └ Triage Agent        │
│   └ Content Agent       │
│ TOOLS                   │
│   └ Entity List         │
│   └ Entity Save         │
└─────────────────────────┘
```

**Option B: "Create New" as First Item**
```
┌─────────────────────────┐
│ CHATBOTS                │
│   [+] New Chatbot        │
│   └ My Unassigned Bot   │
│ AGENTS                  │
│   [+] New Sub-agent      │
│   └ Triage Agent        │
│ ASSISTANTS              │
│   [+] New Assistant      │
│   └ Main Assistant       │
└─────────────────────────┘
```

USER: Lets go with this: Not A sub-agent is no different to an Agent. Its just what we call it because in flowdrop UI you will be likely attaching it to an assistant.

**Option C: Floating Action Button**
```
┌─────────────────────────┐
│ CHATBOTS                │
│   └ My Unassigned Bot   │
│ AGENTS                  │
│   └ Triage Agent        │
│                   [+]   │  <-- Opens menu
└─────────────────────────┘
```

### Create Flow

When user clicks "New ...":
1. Adds a new node to the canvas immediately
2. Opens the existing edit panel for that node
3. Node is only persisted on Flow save

USER Comment: We should skip modal/inline forms and rely on the existing edit panel. Saving happens on Flow save.
RESPONSE: Agreed. I will add nodes directly to the canvas and auto-open the edit panel; no extra create form.



## Tasks

USER COMMENT: Might want to look into this with my feedback

### Phase 1: Backend - Save Support
- [ ] Ensure Flow save can persist newly created chatbot/sub-agent nodes
- [ ] Reuse existing save path (no pre-save create endpoints)

### Phase 2: Sidebar UI
- [x] Add "New ..." UI elements at top of sidebar categories
- [x] Use plus icon matching category color
- [x] Implement click handler to add node + open edit panel
- [x] Disable/empty-state "New Chatbot" when no assistant exists, with link to assistant list

### Phase 3: Tests (test-first)
- [ ] Add tests for sidebar "New ..." UI entries and behavior
- [ ] Add tests for "New Chatbot" disabled/empty-state when no assistant exists
- [ ] Add tests for drag-in existing agent including downstream nodes + preserved relative positions

USER COMMENT: We should create tests for all this work, preferably tests first.
RESPONSE: Understood. I will write the tests first, then implement the feature changes to satisfy them.

### Phase 4: Editing Scope
- [ ] Agent editor: Filter out upstream connections (don't load/show assistants)
- [ ] Assistant editor: Load upstream chatbots and include in graph
- [ ] Ensure save respects scope (agent save doesn't touch assistants)
- [ ] Move existing agent on canvas with downstream nodes keeping relative positions

### Implementation notes: downstream drag (move agent + subtree)
- **Downstream definition**: Starting from the dragged agent node, traverse outgoing edges to include tools and sub-agent nodes (recursive). Do **not** include upstream chatbots or assistant nodes.
- **Relative positioning**: When drag starts, capture the current positions of the agent and all downstream nodes. Store each downstream node’s offset from the agent (dx, dy).
- **During drag**: On agent drag move, update each downstream node position = agent position + stored (dx, dy). Use `FlowDrop.workflowActions.updateNodePosition(nodeId, position)` to avoid reinitializing the whole workflow.
- **Drag end**: Clear the cached offsets and ensure the final positions persist in the workflow state.
- **Guard rails**: 
  - Only apply this behavior for agent nodes (not tool nodes).
  - Skip nodes already being dragged directly (avoid double updates).
  - If a downstream node is missing from the current workflow (collapsed/hidden), ignore it.
- **Risks**: 
  - If FlowDrop emits no drag events, we may need to hook the DOM drag/transform updates or a `node:changed` event and detect position changes.
  - If FlowDrop re-renders nodes during drag, offsets may need to be recalculated on the next drag start.

### New Plan: Stabilize downstream drag (single-loop, DOM-driven)
Goal: stop fighting FlowDrop’s internal drag loop by using one authoritative update path.

1) **Use a single animation loop**
   - Remove pointer/mouse event updates and extra fallback loops.
   - Drive all child updates from one `requestAnimationFrame` loop while drag is active.

2) **Track actual rendered position**
   - Read the dragged agent’s DOM transform each frame (`.react-flow__node`/`.svelte-flow__node`).
   - Convert the transform to canvas coordinates if needed (matrix translate).

3) **Apply offsets from a fixed snapshot**
   - On drag start, snapshot `startPositions` for downstream nodes only.
   - On each frame, compute `dx/dy = rootDomPos - rootStartPos` and set each downstream node to `startPos + dx/dy`.

4) **Detect drag start/end reliably**
   - Start on pointerdown of agent node.
   - End on pointerup/blur or when the DOM transform stops changing for N frames.

5) **Guard rails**
   - Only run for agent nodes.
   - Never update the dragged root node itself.
   - Avoid double updates when FlowDrop events are present; use DOM loop only.

### Will this work? Why?
- **Why it should**: FlowDrop always updates the DOM transform on drag, even when events aren’t exposed. Reading that transform each frame uses the *actual* movement the user sees, so children should track exactly, regardless of drag speed.
- **Why it failed before**: multiple update paths (pointermove, DOM observer, RAF) fought each other and sometimes moved the root itself, causing drift and stalling.
- **Residual risk**: if FlowDrop uses a different transform target than the node wrapper, we might read the wrong element. We can switch to the inner transform element if needed.

### Experiments (scientist-style tests)
1) **Transform source check**
   - Log DOM transform for the node wrapper and its first child during drag.
   - Pass = one of them changes every frame; choose that as the source.
2) **Single-loop verification**
   - Disable all pointer/mouse listeners; keep only RAF loop.
   - Pass = subtree moves in sync when dragging slowly and quickly.
3) **Root-only guard**
   - Ensure dragged root is never updated by our code.
   - Pass = root position is controlled solely by FlowDrop; children follow.
4) **Frame stability**
   - Log `dx/dy` per frame during a fast drag.
   - Pass = values remain non-zero during the drag (no stalls).
5) **Regression check**
   - Drag a tool node: only that node moves.
   - Drag an agent with no children: no errors, no extra work.

USER COMMENT: Where is the task for the preserved relative positions? This should apply for moving agents already on the canvas.
RESPONSE: Included as the final task in Phase 4 and in Validation 6. I will treat it as a first-class requirement, not an optional polish item.

### Phase 5: Validation
- [ ] Validate required fields on save (existing edit panel rules)

### Phase 6: Polish
- [ ] Success/error toast notifications on save

## Test Strategy

### Scope
- Focus on `modules/flowdrop_ui_agents` client behavior.
- Prioritize regression coverage for sidebar creation, expansion, and relayout.

### Unit/JS Tests (preferred)
- **Sidebar creation actions**: verify "New Chatbot" + "New Sub-agent" appear at the top of their categories and are draggable.
- **Chatbot gating**: verify "New Chatbot" is disabled when no assistant exists, with link to assistants listing.
- **Drag-in expansion**: simulate adding an existing agent node to the workflow and assert expanded downstream nodes are added.
- **Tools output hydration**: confirm dragged-in agent node receives metadata outputs including "Tools".
- **Relayout on expansion**: confirm tools are laid out in grid positions after expansion and no longer stacked.
- **Relayout on agent drag end**: confirm tool positions rebuild from agent location.

### Integration/Behavior Tests (fallback)
- Use a workflow fixture with one agent + tools + sub-agent.
- Simulate expansion from `/api/flowdrop-agents/workflow/{id}?expansion=expanded` payloads.
- Validate edges and positions after insert.

### Manual Validation (if JS tests are limited)
- Drag "Bundle Lister Agent" into canvas: tools appear, attach to agent, then fan out into grid.
- Drag agent on canvas and release: tools snap to new grid relative to agent.
- Create new chatbot/sub-agent: node appears and opens edit panel; no modal.
- In agent editor with no assistant: "New Chatbot" disabled with link to assistants list.

## User Validation Plan (per-commit)

We will validate each chunk before committing. I will prepare changes and test locally first, then you verify. Each step includes a pass/fail checklist before you approve the commit.

### Validation 1: Sidebar "New ..." UI
- **What to test**: Sidebar shows "New Chatbot" and "New Sub-agent" at top of their categories with plus icon matching category color.
- **Pass**: Buttons are visible, ordered first, and styled correctly.

### Validation 2: Create Flow (no modal)
- **What to test**: Clicking "New ..." adds a node to canvas and opens edit panel immediately; no modal/inline create form appears.
- **Pass**: Node appears, edit panel opens, and nothing is persisted until Flow save.

### Validation 3: Chatbot gating (assistant required)
- **What to test**: In Agent editor, "New Chatbot" is disabled/empty-state with helper text and link to assistant listing.
- **Pass**: Clear guidance is shown and link works; no chatbot can be created without an assistant.

### Validation 4: Drag-in existing agent brings downstream nodes
- **What to test**: Drag an existing agent from sidebar into the canvas.
- **Pass**: Its downstream tools/sub-agents are added with their relative positions preserved.

### Validation 5: Move existing agent preserves downstream layout
- **What to test**: Move an agent already on the canvas.
- **Pass**: Connected downstream nodes move with it, maintaining relative positions.

### Validation 6: Save persistence
- **What to test**: Save the flow after creating nodes and editing fields.
- **Pass**: Entities persist correctly and reload with the same structure.

## Technical Notes

- New nodes should be created client-side and persisted through the existing Flow save.
- Server-side validation should reject multiple assistants in a single flow (handled outside this editor).

### Editing Scope Implementation

**Agent Editor** (`/admin/config/ai/agents/{agent}/edit_with/flowdrop_agents`):
```php
public function loadAgentWorkflow(AiAgent $agent) {
  // Only load:
  // - The agent itself
  // - Its tools (downstream)
  // - Sub-agents it calls (downstream)
  // DO NOT load:
  // - Assistants that use this agent
  // - Chatbots connected to those assistants
}
```

**Assistant Editor** (`/admin/config/ai/ai-assistant/{assistant}/edit-flowdrop`):
```php
public function loadAssistantWorkflow(AiAssistant $assistant) {
  // Load:
  // - The assistant (as root node)
  // - Backing agent and its tools (downstream)
  // - Sub-agents (downstream)
  // - Chatbots linked to this assistant (upstream)
}
```

## Blockers / Questions

1. **Q**: Should "Create New" open a modal or inline form?

- Use existing edit panel; no modal/inline forms.
RESPONSE: Locked in.

2. **Q**: What's the minimum required fields for each entity type?

Use existing edit panels and their validations. Adding new shouldn't be different.
RESPONSE: I will rely on existing validation on Flow save and surface errors if required fields are missing.

3. **Q**: How to handle machine name collisions?

Use the standard Drupal machine name handling when persisting on save.
RESPONSE: I will mirror the current save path behavior; if you prefer an explicit UI prompt on collision, say so.

4. **Q**: Should newly created entities be immediately saved, or only on flow save?

Flow Save
RESPONSE: Will implement Flow-save-only persistence.

## Dependencies

- Depends on #3565644 (Bring Chatbots into FlowDrop UI) for chatbot node type
This has now been merged into main.

## Reference Files

User Comment: This might need to be updated, we have the agent flow and assistant flow now that arn't entirely the same.
RESPONSE: Updated reference files to include separate `AgentEditorController` and `AssistantEditorController` plus the mapper.

| File | Purpose |
|------|---------|
| `modules/flowdrop_ui_agents/src/Controller/Api/NodesController.php` | Sidebar categories + node listings |
| `modules/flowdrop_ui_agents/js/flowdrop-agents-editor.js` | Add create UI |
| `modules/flowdrop_ui_agents/src/Controller/AssistantEditorController.php` | Assistant flow load + scope |
| `modules/flowdrop_ui_agents/src/Controller/AgentEditorController.php` | Agent flow load + scope |
| `modules/flowdrop_ui_agents/src/Service/AgentWorkflowMapper.php` | Node mapping + scope filtering |
