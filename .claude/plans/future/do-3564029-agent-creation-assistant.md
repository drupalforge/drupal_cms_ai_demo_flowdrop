# Create an Agent that Creates Agents for FlowDrop UI

- **Issue**: [#3564029](https://www.drupal.org/project/flowdrop_ui_agents/issues/3564029)
- **Branch**: `3564029-agent-creation-assistant`
- **Status**: Planning

## Goal

Create an AI-assisted experience for building and configuring AI Agents within FlowDrop UI. Users should be able to describe what they want in natural language and see the agent being built dynamically in the visual editor.

## Vision

Many visual editors rely heavily on AI for setup. This feature would allow users to:
- Describe an agent in natural language ("I need an agent that can create and update articles")
- Watch the agent and its tools appear in FlowDrop UI in real-time
- Iterate on the design conversationally before saving

## Research Tasks

### Phase 1: Exploration

- [ ] **Explore DeepChat JS Library**
  - Understand the component API and event system
  - Identify how to embed it within FlowDrop UI (sidebar panel vs. overlay)
  - Explore streaming message support for real-time feedback
  - Review customization options for styling within Drupal admin theme

- [ ] **Explore ai_agent_agent Module**
  - Install and test the module in the demo environment
  - Understand how it creates agents from natural language
  - Review its tool discovery mechanism
  - Identify what tools it provides and how they work
  - Determine if patches are needed for FlowDrop integration

- [ ] **Determine Assistant Requirements**
  - Does ai_agent_agent require an assistant wrapper?
  - Can we use it directly or do we need ai_assistant_api integration?
  - What configuration is needed for the agent-building agent?

### Phase 2: Architecture Design

- [ ] **Define Integration Approach**
  - Option A: Agent edits Drupal config directly, FlowDrop UI refreshes to show updates
  - Option B: Agent manipulates FlowDrop UI state directly (more complex, better UX)
  - Decide on v1 approach (likely Option A for simplicity)

- [ ] **Design UI Placement**
  - Option: Replace component drawer with chat interface (toggle between them)
  - Option: Slide-out panel on the right side
  - Option: Floating chat bubble that expands
  - Consider how to show both chat and canvas simultaneously

- [ ] **Define Tool Requirements**
  - Tools for Assistant configuration
  - Tools for Agent configuration
  - Tools for browsing and attaching available tools
  - Tools for tool-specific configuration (property restrictions, etc.)

### Phase 3: Implementation Planning

- [ ] **Sub-module Structure**
  - Should this be a sub-module of flowdrop_ui_agents?
  - Dependencies: ai_agent_agent, ai_assistant_api, deepchat
  - Define module boundaries and responsibilities

- [ ] **Streaming Updates Design**
  - How to detect when agent config changes
  - How to trigger FlowDrop UI refresh without losing unsaved changes
  - Consider using Drupal's AJAX system or WebSocket for real-time updates

## Technical Notes

### DeepChat in Current Codebase

DeepChat is already used in flowdrop_ui_agents for chatbot blocks:
- `ai_deepchat_block` plugin creates chatbot blocks
- Blocks are linked to AI Assistants
- Styles can be customized via `deepchat_styles/` directories in modules/themes

Key files:
- `src/Controller/Api/AssistantSaveController.php` - Creates DeepChat blocks
- `src/Service/AgentWorkflowMapper.php` - Maps chatbots to workflow nodes
- `src/Controller/AssistantEditorController.php` - Loads chatbot nodes into editor

### ai_agent_agent Module

From drupal.org:
- **Purpose**: Create/update agents through natural language commands
- **Example**: "Can you create an agent that can create and update node type page for me?"
- **Dependencies**: AI Agents module, Tool API module
- **Status**: Experimental, minimal maintenance until AI 2.0
- **Maintainers**: marcus_johansson, yautja_cetanu

### Potential Integration Patterns

**Pattern 1: Config-based refresh**
```
User → DeepChat → ai_agent_agent → Creates/updates config → Webhook/polling → FlowDrop refreshes
```

**Pattern 2: Direct state manipulation**
```
User → DeepChat → Custom tools → FlowDrop JS API → Live canvas updates
```

### Related Modules to Consider

- **Modeler API**: Could enable BPMN compatibility in the future
- **ai_assistant_api**: Wrapper for agents with LLM configuration

## Questions to Answer

1. **Can ai_agent_agent work standalone or does it need an assistant?**
   - Check if it's an agent that can be called directly
   - Determine LLM provider configuration requirements

2. **How does ai_agent_agent discover available tools?**
   - Does it scan all installed tools?
   - Can we limit which tools it can assign?

3. **What's the best UX for showing streaming updates?**
   - Can FlowDrop handle incremental node additions?
   - Should we batch updates or stream them?

4. **How to handle unsaved changes?**
   - If user has unsaved edits, how do we merge AI-generated changes?
   - Should AI changes be staged separately?

5. **DeepChat placement in FlowDrop UI?**
   - Sidebar tab (like components drawer)?
   - Floating panel?
   - Full sidebar replacement with toggle?

## Dependencies

- ai_agent_agent module from drupal.org
- DeepChat JS library (already in use)
- Possibly ai_assistant_api for assistant wrapper

## Reference Links

- [DeepChat Library](https://deepchat.dev/) - JS chat component
- [ai_agent_agent on drupal.org](https://www.drupal.org/project/ai_agent_agent) - Agent creation module
- [Issue #3565646](https://www.drupal.org/project/flowdrop_ui_agents/issues/3565646) - Related: Add Chatbots/Agents via FlowDrop UI
