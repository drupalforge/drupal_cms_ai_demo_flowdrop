# Drupal.org Issue: Fix Agent ModelOwner to handle description_override and add safety check

## Issue Details

**Project**: AI Agents (ai_agents)
**Component**: Code
**Category**: Bug report
**Priority**: Normal
**Version**: 1.2.x-dev

---

## Title

Agent ModelOwner missing description_override handling and str_contains safety check for tool settings

---

## Issue Body (HTML for Drupal.org)

```html
<h3>Problem/Motivation</h3>

The Agent ModelOwner in <code>Agent.php</code> was updated to handle most behavioral tool settings (<code>return_directly</code>, <code>require_usage</code>, <code>use_artifacts</code>, <code>progress_message</code>), but two issues remain:

<ol>
<li><strong>Missing description_override</strong>: The <code>description_override</code> field is not extracted to <code>tool_settings</code> and not removed before property restriction processing. This causes it to be passed to the foreach loop where <code>explode('___', 'description_override')</code> fails.</li>
<li><strong>No safety check for malformed keys</strong>: If any key without <code>___</code> separator reaches the foreach loop, the <code>explode()</code> call produces a single-element array, causing PHP errors or malformed config entries.</li>
</ol>

<h3>Steps to reproduce</h3>

<ol>
<li>Use the Modeler API (e.g., FlowDrop UI or BPMN.io) to save an agent with a tool that has <code>description_override</code> set</li>
<li>The save will either fail with a PHP error or create malformed config in <code>tool_usage_limits</code></li>
</ol>

<h3>Expected behavior</h3>

<code>description_override</code> should be saved to <code>tool_settings</code> alongside other behavioral settings:

<pre>
tool_settings:
  'tool:entity_bundle_list':
    return_directly: 0
    require_usage: 0
    use_artifacts: 0
    description_override: 'Custom description here'
    progress_message: ''
</pre>

<h3>Actual behavior</h3>

<code>description_override</code> is passed through to the property restriction loop, causing either:
<ul>
<li>PHP error from <code>explode('___', 'description_override')</code> not having enough elements</li>
<li>Malformed entry in <code>tool_usage_limits</code> like <code>{'': 'value'}</code></li>
</ul>

<h3>Proposed resolution</h3>

<ol>
<li>Add <code>description_override</code> to the <code>elementSettings</code> array extraction (line 486)</li>
<li>Add <code>unset($config['description_override'])</code> before the foreach loop</li>
<li>Add a <code>str_contains($key, '___')</code> check at the start of the foreach loop to skip any keys that don't match the expected property restriction format</li>
</ol>

<h3>Remaining tasks</h3>

<ul>
<li>Review patch</li>
<li>Test with Modeler API implementations (FlowDrop, BPMN.io)</li>
<li>Commit</li>
</ul>
```

---

## Patch

**File**: `patches/ai_agents-fix-tool-settings-save.patch`

```diff
diff --git a/src/Plugin/ModelerApiModelOwner/Agent.php b/src/Plugin/ModelerApiModelOwner/Agent.php
index 2c9aaa3..f1d3b8c 100644
--- a/src/Plugin/ModelerApiModelOwner/Agent.php
+++ b/src/Plugin/ModelerApiModelOwner/Agent.php
@@ -481,14 +481,20 @@ class Agent extends ModelOwnerBase {
         $elementSettings[$id] = [
           'progress_message' => $config['progress_message'] ?? "",
           'use_artifacts' => $config['use_artifacts'] ?? FALSE,
           'require_usage' => $config['require_usage'] ?? FALSE,
           'return_directly' => $config['return_directly'] ?? FALSE,
+          'description_override' => $config['description_override'] ?? '',
         ];
         $config += $this->ownerComponentDefaultConfig(Api::COMPONENT_TYPE_ELEMENT, $id);
         unset($config['use_artifacts']);
         unset($config['return_directly']);
         unset($config['progress_message']);
         unset($config['require_usage']);
+        unset($config['description_override']);
         foreach ($config as $key => $value) {
+          // Only process keys in property restriction format (propName___field).
+          if (!str_contains($key, '___')) {
+            continue;
+          }
           [$plugin, $field] = explode('___', $key);
           $plugin = str_replace('__colon__', ':', $plugin);
           $value = match ($field) {
```

---

## Testing Instructions

1. Apply the patch to `ai_agents` module
2. Clear caches: `drush cr`
3. Create/edit an agent via Modeler API (FlowDrop UI or BPMN.io)
4. Configure a tool with `description_override` set to a custom value
5. Save the agent
6. Verify config structure:
   ```bash
   drush config:get ai_agents.ai_agent.YOUR_AGENT_ID
   ```
7. Confirm `description_override` appears in `tool_settings`, not `tool_usage_limits`

---

## Related Issues

- This completes the work started when `require_usage`, `use_artifacts`, and `progress_message` were added to the ModelOwner
- Related to FlowDrop UI Agents issue [#3567208](https://www.drupal.org/project/flowdrop_ui_agents/issues/3567208)
