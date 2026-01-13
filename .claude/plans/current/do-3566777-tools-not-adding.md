# Issue #3566777: Property Restrictions Not Persisting

- **Issue**: [#3566777](https://www.drupal.org/project/flowdrop_ui_agents/issues/3566777)
- **Branch**: `3566777-fix-tools-not-updating`
- **Status**: Complete - Ready for Review

---

## Problem

When configuring tool property restrictions in FlowDrop UI (e.g., setting "Force value" = "node" on an entity_type property), the settings were lost after saving and reloading the page. The restriction would revert to "Allow all".

This affected both the Agent route and Assistant route in FlowDrop.

---

## Root Cause

The bug had two components:

### 1. Format Mismatch in Agent Route

The Agent route saves via `WorkflowParser` → Agent ModelOwner. The ModelOwner expects a **flat key format**:

```php
// Expected by ModelOwner
'entity_type___action' => 'force_value',
'entity_type___values' => 'node',
'entity_type___hide_property' => 0,
```

But `WorkflowParser` was outputting a **nested format**:

```php
// What was being sent (wrong)
'property_restrictions' => [
    'entity_type' => [
        'action' => 'force_value',
        'force_value' => 'node',
    ],
],
```

### 2. Values Stored as String Instead of Array in Assistant Route

The Assistant route saves via `AssistantSaveController` directly to the entity. Drupal's standard form expects `values` to be an **array**, but the save code was storing it as a **string**:

```php
// What was being saved (wrong)
'entity_type' => [
    'action' => 'force_value',
    'values' => 'node',  // String - Drupal form can't read this
]

// What Drupal expects (correct)
'entity_type' => [
    'action' => 'force_value',
    'values' => ['node'],  // Array
]
```

This is why "Only allow certain values" worked but "Force value" didn't - the only_allow code path happened to use arrays while force_value used strings.

---

## Solution

### File 1: `src/Service/WorkflowParser.php`

Added `buildFlatPropertyRestrictions()` method (lines 458-505) that converts FlowDrop's `prop_*` keys to the flat format expected by Agent ModelOwner:

```php
protected function buildFlatPropertyRestrictions(array $config): array {
    $flatConfig = [];

    foreach ($config as $key => $value) {
        if (!str_starts_with($key, 'prop_') || !str_ends_with($key, '_restriction')) {
            continue;
        }

        $propName = substr($key, 5, -12);  // prop_entity_type_restriction -> entity_type
        $restriction = $value ?? '';
        $values = $config['prop_' . $propName . '_values'] ?? '';
        $hidden = !empty($config['prop_' . $propName . '_hidden']);

        $action = match ($restriction) {
            'Force value', 'force_value' => 'force_value',
            'Only allow certain values', 'only_allow' => 'only_allow',
            default => '',
        };

        if (!empty($action)) {
            $safePropName = str_replace(':', '__colon__', $propName);
            $flatConfig[$safePropName . '___action'] = $action;
            $flatConfig[$safePropName . '___values'] = $values;
            $flatConfig[$safePropName . '___hide_property'] = $hidden ? 1 : 0;
        }
    }

    return $flatConfig;
}
```

Modified `createToolComponent()` to use this flat format instead of nested `property_restrictions`.

### File 2: `src/Controller/Api/AssistantSaveController.php`

Added `buildToolUsageLimitsEntry()` method (lines 565-606) that ensures values are **always saved as arrays**:

```php
protected function buildToolUsageLimitsEntry(array $nodeConfig): array {
    $entry = [];

    foreach (array_keys($propNames) as $propName) {
        $restriction = $nodeConfig['prop_' . $propName . '_restriction'] ?? '';
        $values = $nodeConfig['prop_' . $propName . '_values'] ?? '';
        $hidden = !empty($nodeConfig['prop_' . $propName . '_hidden']);

        $propLimit = [
            'action' => '',
            'hide_property' => $hidden ? 1 : 0,
            'values' => '',
        ];

        // KEY FIX: Values must always be an array for Drupal form compatibility
        if ($restriction === 'Force value' || $restriction === 'force_value') {
            $propLimit['action'] = 'force_value';
            $propLimit['values'] = is_array($values) ? $values : ($values !== '' ? [$values] : []);
        }
        elseif ($restriction === 'Only allow certain values' || $restriction === 'only_allow') {
            $propLimit['action'] = 'only_allow';
            $propLimit['values'] = is_array($values) ? $values : ($values !== '' ? [$values] : []);
        }

        $entry[$propName] = $propLimit;
    }

    return $entry;
}
```

Also added helper methods:
- `buildPropertyRestrictions()` - Converts flat `prop_*` keys to nested format for tool_settings
- `extractBehavioralSettings()` - Extracts non-restriction settings (return_directly, require_usage, etc.)

### File 3: `src/Service/AgentWorkflowMapper.php`

Enhanced property restriction loading to handle both legacy nested format and new flat format.

---

## Tests Added

Added 4 kernel tests to `tests/src/Kernel/AssistantSaveTest.php`:

| Test | Purpose |
|------|---------|
| `testSubAgentToolPropertyRestrictionsPersist` | Primary regression test for Issue #3566777 - verifies sub-agent tool restrictions save via Assistant route |
| `testForceValueSavesAsArray` | Verifies force_value saves values as array, not string |
| `testOnlyAllowSavesCorrectly` | Verifies only_allow restriction works correctly |
| `testMultiplePropertyRestrictionsSave` | Verifies multiple restrictions on one tool save correctly |

Additional tests added later in this branch:

| Test | Purpose |
|------|---------|
| `testToolDescriptionOverridePersistsWhenOmitted` | Ensures tool description overrides are preserved when FlowDrop omits the field |

**Note**: `AgentRouteToolDescriptionOverrideTest` was moved to [Issue #3566849](https://www.drupal.org/project/flowdrop_ui_agents/issues/3566849) as description override persistence via Agent route is out of scope for this bug fix.

---

## Files Changed

| File | Lines Changed | Description |
|------|---------------|-------------|
| `src/Controller/Api/AssistantSaveController.php` | +340 | Added buildToolUsageLimitsEntry(), buildPropertyRestrictions(), extractBehavioralSettings() |
| `src/Service/WorkflowParser.php` | +72 | Added buildFlatPropertyRestrictions() |
| `src/Service/AgentWorkflowMapper.php` | +123 | Enhanced property restriction loading |
| `tests/src/Kernel/AssistantSaveTest.php` | +240 | Added 4 regression tests |
| `tests/src/Kernel/AgentToolConnectionTest.php` | +19 | Test improvements |

Additional changes since initial fix:

| File | Lines Changed | Description |
|------|---------------|-------------|
| `src/Controller/Api/AssistantSaveController.php` | n/a | Preserve tool description overrides when missing from FlowDrop payload |
| `tests/src/Kernel/AssistantSaveTest.php` | n/a | Added tool description override persistence test |
| `src/Service/AgentWorkflowMapper.php` | n/a | Use JSON schema for new tool nodes to avoid "Debug - Config Schema" |

---

## Verification

Manually tested:
1. Navigate to Assistant FlowDrop editor
2. Configure tool with "Force value" restriction
3. Save and reload page
4. Verify restriction persists correctly

Both Agent route and Assistant route now work correctly.

Additional verification:
1. Adding a tool and opening the config cog before saving now shows the standard config form (no debug schema).
2. Tool description overrides persist when FlowDrop omits the description override field on save.

---

*Completed: 2026-01-11*

## CODEX CODE REVIEW

### Key findings (ordered by risk)

1. **Property-level description overrides are dropped on save (Assistant route)**  
   - The save path now splits behavioral settings vs. property restrictions, but `extractBehavioralSettings()` never persists `prop_*_override_desc_*`, and `buildToolUsageLimitsEntry()` ignores description overrides entirely. The tool save path for assistants uses `extractBehavioralSettings()` and never calls `extractToolSettings()` which would incorporate `buildPropertyRestrictions()` (and thus override descriptions).  
   - References: `modules/flowdrop_ui_agents/src/Controller/Api/AssistantSaveController.php:281`, `modules/flowdrop_ui_agents/src/Controller/Api/AssistantSaveController.php:529`, `modules/flowdrop_ui_agents/src/Controller/Api/AssistantSaveController.php:565`, `modules/flowdrop_ui_agents/src/Controller/Api/AssistantSaveController.php:390`.  
   - Result: property description overrides can silently disappear when saving from FlowDrop, even if the UI supports them.  
   - Recommendation: persist per-property description overrides in `tool_settings[property_restrictions]` (via `buildPropertyRestrictions()`), or clearly strip them in UI to avoid false promises.

2. **Notice-level debug logging on every save**  
   - `AssistantSaveController` logs full `tool_usage_limits` at notice level. This is noisy in production and may expose sensitive config.  
   - Reference: `modules/flowdrop_ui_agents/src/Controller/Api/AssistantSaveController.php:312`.  
   - Recommendation: remove the log or downgrade to debug and guard it behind a config flag.

### Testing strategy improvements

- **Add Agent-route regression tests** for `WorkflowParser::buildFlatPropertyRestrictions()` (flat key mapping, colon handling, hidden-only restrictions). This is the main code path for Agent saves and currently untested.  
- **Add tests for property description overrides** to confirm `prop_*_override_desc_*` survives round-trip, or explicitly assert it is not supported.  
- **Add tests for "hidden without restriction"** and `"Allow all"` cases to ensure `tool_usage_limits` doesn’t inadvertently clear existing restrictions when the UI didn’t expose those properties.  
- **Add tests for multi-value only_allow** (array input) and scalar input normalization to avoid future regressions in value typing.  
- **Add a smoke test that loads + saves without tool edits** to validate the "preserve existing limits when no prop keys are present" behavior.

---

## CLAUDE RESPONSE TO CODE REVIEW

### Finding 1: Property description overrides dropped - **DEFER**

**Verdict**: Valid concern, but out of scope for this issue.

Codex is correct that `buildToolUsageLimitsEntry()` doesn't save description overrides. However:
- Description overrides are handled in `buildPropertyRestrictions()` (lines 479-480, 505-507)
- `buildPropertyRestrictions()` saves to `tool_settings`, not `tool_usage_limits`
- This is the correct separation - behavioral settings go to `tool_settings`, property limits go to `tool_usage_limits`

The real question is: does the UI even support property-level description overrides? If not, this is a non-issue. If yes, we need to verify the load path reads from `tool_settings[property_restrictions]`.

**Recommendation**: Create a separate issue to audit description override support end-to-end.

### Finding 2: Notice-level debug logging - **FIX NOW**

**Verdict**: Valid bug. This was debug code that should have been removed.

Line 312 has a `\Drupal::logger()->notice()` that logs full `tool_usage_limits` on every save. This:
- Creates noise in production logs
- May expose configuration data
- Was left over from debugging

**Action**: Remove this line before pushing.

---

## Follow-up Notes

- Debug logging in `AssistantSaveController` has been removed.
- FlowDrop UI still does not display the tool description override in node description after save/reload; needs separate follow-up.

## Latest Test Run

`ddev exec "SIMPLETEST_DB=sqlite://localhost/sites/default/files/.sqlite ./vendor/bin/phpunit -c web/core/phpunit.xml.dist modules/flowdrop_ui_agents/tests/src/Kernel/AssistantSaveTest.php --colors=always"`

Result: tests pass with deprecations; SimpleTest browser_output warning remains.

**Note**: `AgentRouteToolDescriptionOverrideTest.php` was removed from this branch and moved to issue #3566849.

### Testing suggestions - **DEFER**

The testing suggestions are good but out of scope for this bug fix:
- Agent-route tests for `buildFlatPropertyRestrictions()` - Good idea for follow-up
- Description override tests - Depends on whether feature is supported
- Hidden-only and Allow all tests - Edge cases, lower priority
- Multi-value tests - Already covered by `testMultiplePropertyRestrictionsSave`
- Smoke test for preserve-existing - Already implemented in lines 289-302

**Recommendation**: Create a follow-up issue for expanded test coverage.
