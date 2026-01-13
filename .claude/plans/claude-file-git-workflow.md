# Plan: Git Workflow for .claude Files

## Problem Statement

The `.claude/` directory contains valuable context for AI-assisted development:
- Plans for implementing features
- Branch context and working notes
- Documentation that helps AI understand the codebase

**The issue:** Frequent commits to plan files clutter git history, drowning out actual code changes. This isn't what git is designed for, yet we want to share these files with the team.

## Options Analysed

### Option A: Separate `plans` Branch

**Concept:**
```
main branch:           .claude/ gitignored
feature branches:      .claude/ gitignored
plans branch:          .claude/ committed, all WIP shared
```

**Workflow:**
1. Create orphan `plans` branch (no shared history with main)
2. Add `.claude/` to `.gitignore` on main and all feature branches
3. AI commits .claude changes to `plans` branch only
4. Developers fetch plans branch separately to get context

**Pros:**
- Clean separation of concerns
- Main/feature branch history stays focused on code
- Plans still versioned and shareable

**Cons:**
- Complex workflow requiring branch switching
- AI needs to switch branches mid-work (error-prone)
- When .claude is gitignored, AI loses context unless files are manually copied
- New developers need to know to fetch the plans branch
- Risk of accidentally committing .claude to wrong branch
- Merge conflicts between branches could be messy

**Verdict:** Architecturally clean but operationally painful. Branch switching mid-task is where AI assistants make mistakes.

---

### Option B: Staged Plan Lifecycle (Drafts vs Finals)

**Concept:**
```
.claude/
├── plans/
│   ├── drafts/          ← .gitignored (active work)
│   ├── completed/       ← Committed (finalized plans)
```

**Workflow:**
1. AI creates/updates plans in `drafts/` - never committed
2. When plan is finalized, move to `completed/`
3. Commit once with meaningful message

**Pros:**
- Simple workflow
- Clean history (one commit per completed plan)
- No branch switching

**Cons:**
- Work-in-progress plans not shared with team
- Requires manual "promotion" step

**Verdict:** Doesn't meet the requirement of sharing WIP plans with the team.

---

### Option C: Commit Convention + Batching (Recommended)

**Concept:** Keep .claude tracked normally, but use commit conventions and batching to manage the noise.

**Workflow:**
1. All `.claude/` files tracked on all branches (current setup)
2. Batch plan commits - don't commit every small change
3. Use `[plans]` prefix for all plan-related commits
4. Filter history when you need a clean view

**Commit examples:**
```bash
git commit -m "[plans] Update DO-3565646 agent implementation plan"
git commit -m "[plans] Add branch context for chatbot icons fix"
```

**Filtering history:**
```bash
# View clean history (excludes plan commits)
git log --oneline | grep -v "\[plans\]"
git log --invert-grep --grep="\[plans\]"

# View only plan commits
git log --oneline --grep="\[plans\]"
```

**Pros:**
- Simple - no branch switching, no gitignore complexity
- AI always has context available
- Plans still shared via normal git workflow
- History is filterable when clean view needed
- Works with existing git muscle memory

**Cons:**
- Commits still technically in history (just filterable)
- Requires discipline to batch commits

**Verdict:** Best balance of simplicity and functionality. The real problem is commit frequency, not commit location.

---

## Recommendation

**Use Option C: Commit Convention + Batching**

The separate branch idea sounds clean in theory but adds significant workflow complexity for marginal benefit. AI assistants work best with simple, linear workflows. The filtering approach gives a clean history *view* without the complex *workflow*.

## Implementation Guidelines

### For CLAUDE.md

Add these rules:

1. **Batch `.claude/` commits** - Update plan files as needed during work, but only commit at:
   - End of a working session
   - When switching to a different task
   - When explicitly asked to commit

2. **Use commit prefix** - All commits touching only `.claude/` files must use:
   ```
   [plans] Description of what changed
   ```

3. **Never mix commits** - Don't combine code changes and plan changes in the same commit. This allows clean filtering.

4. **Commit message examples:**
   ```
   [plans] Update DO-3565646 with implementation notes
   [plans] Add branch context for current feature work
   [plans] Mark DO-3512345 plan as completed
   ```

### For .claude/plans/README.md

Update to explain:
- Plans are committed to git for team sharing
- Use `[plans]` prefix convention
- How to filter history if needed

---

## Future Considerations

- GitHub/GitLab could potentially filter `[plans]` commits in PR views
- Could create git alias for clean log: `git config alias.codelog "log --invert-grep --grep='\\[plans\\]'"`
- If commit noise becomes severe, revisit the branch approach with git worktrees

---

## Status

**Decision:** Pending user approval

**Next steps if approved:**
1. Update CLAUDE.md with commit guidelines
2. Update .claude/plans/README.md
3. Create git alias for filtered log view
