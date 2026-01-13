# Current Branch Context

Per-branch tracking for the current work session.

## Current Branches

| Repo | Branch | Issue |
|------|--------|-------|
| Demo site (this repo) | `main` | - |
| flowdrop_ui_agents | `3565646-add-chatbot-agents` | [#3565646](https://www.drupal.org/project/flowdrop_ui_agents/issues/3565646) |

## Active Work

**Issue #3565646 - Add Chatbot/Agent Creation UI**: Add creation controls for chatbots, agents, sub-agents, and assistants in the FlowDrop sidebar with proper edit scope rules.

See: `.claude/plans/current/do-3565646-ability-to-add-agents.md`

## Test URLs

- **Assistant Editor**: `/admin/config/ai/ai-assistant/drupal_cms_assistant/edit-flowdrop`
- **API Endpoint**: `/api/flowdrop-agents/nodes`

## Quick Commands

```bash
# Check module branch
cd modules/flowdrop_ui_agents && git branch --show-current

# See changes on branch (compare to 1.0.x)
cd modules/flowdrop_ui_agents && git log --oneline 1.0.x..HEAD

# Push module to drupal.org
cd modules/flowdrop_ui_agents && git push flowdrop_ui_agents-3565646 3565646-add-chatbot-agents

# Clear Drupal cache after changes
ddev drush cr
```

---

*Last updated: 2026-01-07*
