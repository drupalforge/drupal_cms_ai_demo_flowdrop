# Fix FlowDrop UI Saving Tool Settings Incorrectly

- **Issue**: [#3567208](https://www.drupal.org/project/flowdrop_ui_agents/issues/3567208)
- **Branch**: `3567208-fix-flowdrop-ui-saving-incorrectly`
- **Status**: Minimal patch applied - upstream fixed most issues, patch adds `description_override` handling
- **Related**: [#3566777](https://www.drupal.org/project/flowdrop_ui_agents/issues/3566777) (Property Restrictions Not Persisting)

---

## Problem

FlowDrop UI is saving tool settings (behavioral settings) to `tool_usage_limits` instead of `tool_settings`. This causes fields to appear to save in the FlowDrop UI but not persist correctly in Drupal config.

### Expected Config Structure

```yaml
tool_settings:
  'tool:entity_bundle_list':
    return_directly: 0
    require_usage: 0
    description_override: ''
    progress_message: ''
    use_artifacts: 0
tool_usage_limits:
  'tool:entity_bundle_list':
    entity_type_id:
      action: ''
      hide_property: 0
      not_break: 0
      values: ''
```

### Actual (Broken) Config Structure

```yaml
tool_usage_limits:
  'tool:entity_bundle_list':
    require_usage:
      '': 0
    use_artifacts:
      '': 0
    description_override:
      '': ''
    progress_message:
      '': ''
    entity_type_id:
      action: ''
      hide_property: false
      values: ''
```

### Issues Identified

1. **Wrong destination**: Behavioral settings (`require_usage`, `use_artifacts`, `description_override`, `progress_message`) are being saved under `tool_usage_limits` instead of `tool_settings`

2. **Wrong format**: These settings are being nested with an empty string key (`'': value`) instead of being flat values

3. **Missing `return_directly`**: The `return_directly` setting appears to be missing entirely

4. **Missing `not_break`**: The `not_break` property restriction field is missing from the tool_usage_limits entries

---

## Root Cause Investigation

### Files to Investigate

| File | Purpose |
|------|---------|
| `src/Controller/Api/AssistantSaveController.php` | Handles save from Assistant route |
| `src/Service/WorkflowParser.php` | Parses workflow for Agent route save |
| `src/Service/AgentWorkflowMapper.php` | Maps agent config to FlowDrop format (loading) |
| `js/flowdrop-agents-editor.js` | JavaScript that sends save payload |

### Hypotheses

1. **Save controller routing issue**: The save controller is not properly distinguishing between behavioral settings (→ `tool_settings`) and property restrictions (→ `tool_usage_limits`)

2. **Key naming mismatch**: The FlowDrop UI may be sending keys that don't match what the save handler expects, causing misrouting

3. **Missing extraction logic**: The `extractBehavioralSettings()` function from #3566777 may not be extracting all required fields

4. **Client-side payload issue**: The JavaScript may be formatting the save payload incorrectly

---

## Tasks

### Phase 1: Investigation ✅

- [x] Reproduce the bug locally - configure a tool in FlowDrop UI, save, inspect resulting config
- [x] Trace the save path from JS → Controller → Config to identify where settings get misrouted
- [x] Compare expected vs actual save payload at each step
- [x] Review `extractBehavioralSettings()` to verify it handles all required fields

**Finding**: Bug is in `WorkflowParser` - it sends keys the Agent ModelOwner doesn't understand.

### Phase 2: Fix (Option D - Patch ai_agents Module) ✅

Created patch for `ai_agents` module to properly handle all behavioral settings:

- [x] Patch `Agent.php` ModelOwner to extract all tool_settings fields:
  - `return_directly`
  - `require_usage`
  - `use_artifacts`
  - `description_override`
  - `progress_message`
- [x] Remove behavioral settings before processing property restrictions
- [x] Skip keys that don't contain `___` to prevent malformed entries
- [x] Save patch to `patches/ai_agents-fix-tool-settings-save.patch`
- [x] Add patch to `composer.json` for auto-application

**Patch file**: `patches/ai_agents-fix-tool-settings-save.patch`

### Phase 3: Testing

- [ ] Add kernel test for Agent route tool behavioral settings round-trip
- [ ] Add kernel test for property restrictions including `not_break` field
- [ ] Verify fix works for Agent route
- [ ] Verify Assistant route still works correctly

### Phase 4: Verification

- [ ] Manual test: Configure tool in Agent FlowDrop UI, save, export config, verify structure
- [ ] Manual test: Reload FlowDrop UI and verify settings display correctly
- [ ] Compare saved config with standard Drupal form save

---

## Technical Notes

### ROOT CAUSE IDENTIFIED

The bug is in **WorkflowParser** - it sends keys that the Agent ModelOwner doesn't understand.

#### How the Agent ModelOwner Works

The Agent ModelOwner (`ai_agents` module) expects tool config keys in specific format:
- `return_directly` → extracted to `tool_settings`
- `propName___action`, `propName___hide_property`, `propName___values` → processed as property restrictions

```php
// Agent.php addComponent() lines 456-464
foreach ($config as $key => $value) {
  [$plugin, $field] = explode('___', $key);  // Expects propName___field format
  $elementUsageLimits[$id][$plugin][$field] = $value;
}
```

#### Bug Location

**File**: `modules/flowdrop_ui_agents/src/Service/WorkflowParser.php`
**Method**: `createToolComponent()` (lines 284-290)

```php
$toolConfig = [
  'return_directly' => ...,      // ✅ Agent ModelOwner handles this
  'require_usage' => ...,        // ❌ NOT handled - causes malformed entry
  'use_artifacts' => ...,        // ❌ NOT handled - causes malformed entry
  'description_override' => ..., // ❌ NOT handled - causes malformed entry
  'progress_message' => ...,     // ❌ NOT handled - causes malformed entry
];
```

**What happens**:
1. WorkflowParser sends `require_usage`, `use_artifacts`, etc. as flat keys
2. Agent ModelOwner tries `explode('___', 'require_usage')` → returns `['require_usage']` (single element)
3. PHP list assignment `[$plugin, $field] = ...` sets `$field` to empty string
4. Result: `$elementUsageLimits[$id]['require_usage'][''] = 0` → creates `{'': 0}` structure

#### Why Assistant Route Works

`AssistantSaveController` has custom logic that properly separates behavioral settings from property restrictions - it doesn't use the Modeler API save path.

#### Affected Settings

Settings that WorkflowParser sends but Agent ModelOwner doesn't handle:
- `require_usage`
- `use_artifacts`
- `description_override`
- `progress_message`
- `property_restrictions` (nested array)
- `property_description_override` (nested array)

### Key Code Paths

**Assistant Route Save** (WORKS):
```
POST /api/flowdrop-agents/assistant/{id}/save
→ AssistantSaveController::save()
→ updateAgent()
  → extractBehavioralSettings() → tool_settings (correct!)
  → buildToolUsageLimitsEntry() → tool_usage_limits (correct!)
→ Agent entity save
```

**Agent Route Save** (BROKEN):
```
POST /admin/modeler_api/ai_agent/flowdrop_agents/save
→ Modeler API form handler
→ Agent ModelOwner::addComponent()
  → Only return_directly → tool_settings
  → ALL other keys → tool_usage_limits (WRONG!)
→ Agent entity save
```

### Config Schema Reference

From `ai_agents` module, the expected structure:

**tool_settings** (per tool):
- `return_directly`: boolean - Return tool result directly to user
- `require_usage`: boolean - Require tool usage before responding
- `description_override`: string - Custom description for the tool
- `progress_message`: string - Message to show while tool executes
- `use_artifacts`: boolean - Whether to use artifacts
- `property_restrictions`: array - Nested property restriction config
- `property_description_override`: array - Per-property description overrides

**tool_usage_limits** (per property per tool):
- `action`: string - 'force_value', 'only_allow', or ''
- `hide_property`: boolean - Hide property from LLM
- `not_break`: boolean - Don't break on this property
- `values`: array|string - Allowed/forced values

---

## Solution Options

### Option A: Fix WorkflowParser (Simplest)

Remove the behavioral settings from `toolConfig` in WorkflowParser - don't send keys the Agent ModelOwner can't handle.

```php
// Only send what Agent ModelOwner understands
$toolConfig = [
  'return_directly' => $config['return_directly'] ?? FALSE,
  // Don't include require_usage, use_artifacts, description_override, progress_message
];
```

**Pros**: Simple fix, stops the broken config from being created
**Cons**: These settings won't be saved via Agent route (only return_directly will work)

### Option B: Create AgentSaveController (Full Fix)

Create a custom `AgentSaveController` that mirrors `AssistantSaveController`. Override the save URL for Agent route to bypass Modeler API.

**Pros**: All settings saved correctly for Agent route
**Cons**: Bypasses Modeler API, duplicates some logic

### Option C: Hybrid Approach (Recommended)

1. Fix WorkflowParser to not send unsupported keys (stops broken config)
2. Create `AgentSaveController` for full behavioral settings support
3. Optionally submit patch to `ai_agents` to extend Agent ModelOwner

### Option D: Patch ai_agents Module ← CHOSEN

Fix the Agent ModelOwner to handle all behavioral settings before processing property restrictions.

**Pros**: Fixes at the source, benefits all users
**Cons**: Requires contrib patch, may take time to get merged

**Implemented**: `patches/ai_agents-fix-tool-settings-save.patch`

---

## Blockers / Questions

1. **Q**: Is this bug present on both Agent and Assistant routes?
   - **A**: Only Agent route. Assistant route uses our custom controller which works correctly.

2. **Q**: Was this introduced as a regression from #3566777 fixes or a pre-existing bug?
   - **A**: Pre-existing bug in `ai_agents` module. The Agent ModelOwner has always only saved `return_directly` to `tool_settings`.

---

## Test Commands

```bash
# Run all FlowDrop tests
ddev exec "SIMPLETEST_DB=sqlite://localhost/sites/default/files/.sqlite ./vendor/bin/phpunit -c web/core/phpunit.xml.dist modules/flowdrop_ui_agents/tests/src/Kernel/ --colors=always"

# Run specific test file
ddev exec "SIMPLETEST_DB=sqlite://localhost/sites/default/files/.sqlite ./vendor/bin/phpunit -c web/core/phpunit.xml.dist modules/flowdrop_ui_agents/tests/src/Kernel/AssistantSaveTest.php --colors=always"
```

## Test URLs

- **Assistant Editor**: `/admin/config/ai/ai-assistant/bundle_lister_assistant/edit-flowdrop`
- **Agent Editor**: `/admin/config/ai/agents/bundle_lister_agent/edit_with/flowdrop_agents`
- **Standard Agent Form** (for comparison): `/admin/config/ai/agents/bundle_lister_agent/edit`

---

## Handoff Notes for Next Agent

### Context
We investigated issue #3567208 where FlowDrop UI saves tool settings incorrectly. The root cause is that `WorkflowParser` sends behavioral settings (`require_usage`, `use_artifacts`, `description_override`, `progress_message`) that the Agent ModelOwner in `ai_agents` module doesn't understand. This causes malformed config entries like `{'': 0}` in `tool_usage_limits`.

### What Was Done
1. Full investigation completed - root cause identified in `WorkflowParser.php` lines 284-290
2. Created a local patch at `patches/ai_agents-fix-tool-settings-save.patch`
3. Added patch to `composer.json` for auto-application
4. **However**: User mentioned fixes were done upstream in `ai_agents` module

### What Needs to Be Done After Composer Update

1. **Remove local patch if upstream fix works**:
   - Check if `ai_agents` module now handles all tool_settings fields
   - If yes, remove patch from `composer.json` and delete `patches/ai_agents-fix-tool-settings-save.patch`
   - Revert changes to `web/modules/contrib/ai_agents/src/Plugin/ModelerApiModelOwner/Agent.php`

2. **Test the fix**:
   ```bash
   # Go to Agent FlowDrop editor
   # URL: /admin/config/ai/agents/bundle_lister_agent/edit_with/flowdrop_agents

   # Configure a tool with behavioral settings (require_usage, use_artifacts, etc.)
   # Save the workflow

   # Export config and verify structure:
   ddev drush cex -y
   # Check config/sync/ai_agents.ai_agent.bundle_lister_agent.yml
   ```

3. **Verify expected config structure**:
   ```yaml
   tool_settings:
     'tool:entity_bundle_list':
       return_directly: 0
       require_usage: 0        # Should be here, NOT in tool_usage_limits
       use_artifacts: 0        # Should be here, NOT in tool_usage_limits
       description_override: ''
       progress_message: ''
   tool_usage_limits:
     'tool:entity_bundle_list':
       entity_type_id:         # Only property restrictions here
         action: ''
         hide_property: 0
         values: ''
   ```

4. **If upstream fix doesn't work**: Keep the local patch or implement Option B (AgentSaveController)

### Key Files
- Plan: `.claude/plans/current/do-3567208-fix-flowdrop-ui-saving-incorrectly.md`
- Common issues doc: `.claude/flowdrop-ui-agents.md` (updated with this gotcha)
- Local patch: `patches/ai_agents-fix-tool-settings-save.patch`
- WorkflowParser: `modules/flowdrop_ui_agents/src/Service/WorkflowParser.php`
- Agent ModelOwner: `web/modules/contrib/ai_agents/src/Plugin/ModelerApiModelOwner/Agent.php`

---

## Verification Results (2025-01-13)

### Upstream Fix Status

After `composer update`, the upstream `ai_agents` module now handles most behavioral settings:

**Handled by upstream** (commit `2c9aaa307b`):
- ✅ `return_directly`
- ✅ `require_usage`
- ✅ `use_artifacts`
- ✅ `progress_message`

**Still missing from upstream**:
- ❌ `description_override` - not extracted to tool_settings, not unset
- ❌ `str_contains` safety check - keys without `___` will cause PHP errors

### Minimal Patch Applied

Updated patch to only add what's missing:
1. Added `description_override` to `elementSettings`
2. Added `unset($config['description_override'])`
3. Added `str_contains($key, '___')` check to skip non-property-restriction keys

**Patch location**: `patches/ai_agents-fix-tool-settings-save.patch`

### Config Verification

Current `bundle_lister_agent` config shows correct structure:
```yaml
tool_settings:
  'tool:entity_bundle_list':
    return_directly: 0
    require_usage: 0
    description_override: ''
    progress_message: ''
    use_artifacts: 0
tool_usage_limits:
  'tool:entity_bundle_list':
    entity_type_id:
      action: ''
      hide_property: 0
      values: ''
```

### Remaining Tasks

- [ ] Manual test: Save via FlowDrop Agent editor with description_override set
- [ ] Submit updated patch to upstream ai_agents module
- [ ] Test that patch applies automatically via composer

---

*Created: 2025-01-13*
*Updated: 2025-01-13 - Verification after composer update*
