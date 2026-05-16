# 📚 CODEBASE MAINTENANCE & HYGIENE GUIDE

**Last Updated:** 2026-05-16  
**Cleanup Status:** Phase 4 Complete (57 files removed, 3MB freed)

---

## 📋 FILE CLASSIFICATION SYSTEM

### ✅ PRODUCTION CORE (KEEP ALWAYS)
```
controllers/           - All route handlers
models/                - All data models
services/              - All business logic services
components/            - All reusable components
helpers/               - Helper functions
views/                 - All view templates
migrations/            - Database migrations
config/                - Configuration files
web/                   - Static assets
yii, yii.bat          - Application entry points
composer.json/lock     - Dependency management
```

### ⚠️ DOCUMENTATION (SELECTIVE)
```
KEEP:
✅ README.md           - Main project docs
✅ START_HERE.md       - Onboarding guide
✅ LICENSE.md          - License
✅ docs/*.md           - Service/architecture documentation

DELETE:
❌ *_GUIDE.md          - Obsolete setup guides
❌ *_SUMMARY.md        - Implementation summaries
❌ *_REPORT.md         - Fix reports
❌ *_TESTING_GUIDE.md  - Testing guides
❌ *_FIX*.md           - One-time fix documentation
❌ *_CHECKLIST.md      - Build/deployment checklists
```

### 🗑️ TEMPORARY/DEVELOPMENT FILES (DELETE)
```
DELETE PATTERNS:
❌ verify_*.php        - DB inspection utilities
❌ fix_*.php           - One-time fix scripts
❌ setup_*.php         - Setup utilities
❌ add_*.php           - Column/table addition scripts
❌ populate_*.php      - Test data scripts
❌ check_*.php         - Schema verification
❌ test_*.php          - Debug test files
❌ *_debug.log         - Debug output files
❌ .kombai/debug/      - IDE debug cache
```

---

## 🎯 CLEANUP DECISION TREE

```
File to evaluate?
│
├─ Is it production code?
│  ├─ controllers/, models/, services/, etc? → KEEP
│  └─ No → Continue
│
├─ Is it README/LICENSE/core docs?
│  ├─ Yes → KEEP
│  └─ No → Continue
│
├─ Does it have a pattern: verify_*, fix_*, setup_*, add_*, populate_*, test_*?
│  ├─ Yes → DELETE (development utility)
│  └─ No → Continue
│
├─ Is it a .md file?
│  ├─ Starts with *_GUIDE, *_SUMMARY, *_REPORT, *_CHECKLIST, *_FIX, *_TESTING?
│  │  ├─ Yes → DELETE (obsolete documentation)
│  │  └─ No → KEEP (might be important)
│  └─ No → Continue
│
├─ Is it a .log or debug file?
│  ├─ Yes → DELETE (debug output)
│  └─ No → Continue
│
└─ When in doubt → ASK before deleting
```

---

## 📊 CLEANUP HISTORY

### Phase 1-3 (May 2026)
- Deleted: 29 files (test/check/debug utilities)
- Freed: 1.8 MB
- Impact: ZERO

### Phase 4 (May 2026) - **AGGRESSIVE**
- Deleted: 57 files (setup scripts, fix utilities, debug docs)
- Freed: 3.0 MB
- Impact: ZERO
- Files removed:
  - 16 PHP utility scripts
  - 31 .md documentation files
  - 10 debug cache files

### Phase 5 (OPTIONAL - Future)
- Candidate: 14-19 more files
- Target: database_setup.sql, database_seed_data.sql, setup scripts, additional .md files
- Risk: LOW
- Impact: Minimal (use migrations for setup)

---

## 🛑 FILES THAT MUST NEVER BE DELETED

### Core Runtime
```
NEVER DELETE:
❌ composers.json, composer.lock
❌ config/web.php, config/db.php, config/console.php
❌ controllers/
❌ models/
❌ services/
❌ components/
❌ helpers/
❌ views/
❌ migrations/
❌ widgets/
❌ yii, yii.bat
```

### Database
```
NEVER DELETE (unless using fresh migrations):
❌ database_setup.sql       - Schema definition
❌ database_seed_data.sql   - Initial data
❌ migrations/              - Migration history
```

### Documentation
```
NEVER DELETE:
❌ README.md                - Main documentation
❌ START_HERE.md            - Onboarding
❌ LICENSE.md               - License terms
❌ docs/                    - Service documentation
```

### Assets & Web
```
NEVER DELETE:
❌ web/                     - Static assets
❌ web/assets/              - Bundled CSS/JS
❌ .yiiignore              - Framework ignore file
```

---

## ✅ SAFE TO DELETE (ALWAYS)

### Development Utilities
```
SAFE:
✅ verify_*.php             - Already used or schema verified
✅ fix_*.php                - One-time fixes already applied
✅ setup_*.php              - Setup complete, use migrations
✅ add_*.php                - Schema complete
✅ populate_*.php           - Test data, use migrations
✅ check_*.php              - Verification done
✅ test_*.php               - Should use codeception tests
```

### Documentation
```
SAFE:
✅ *_GUIDE.md               - Outdated setup guides
✅ *_SUMMARY.md             - Implementation summaries (history preserved in git)
✅ *_REPORT.md              - Fix reports (issues resolved)
✅ *_CHECKLIST.md           - Build checklists (teams memorized)
✅ *_TESTING_GUIDE.md       - Use codeception tests instead
✅ *_FIX.md                 - One-time fixes documented in commits
✅ HIERARCHICAL_*.md        - Feature documentation (if mature)
✅ PERFORMANCE_*.md         - Analysis docs (use README)
```

### Debug & Cache
```
SAFE:
✅ .kombai/debug/           - IDE debug cache (regenerates)
✅ *_debug.log              - Debug output
✅ firebase-debug.log       - Firebase debug output
✅ .git-debug/*             - Git debug files
```

---

## 📝 BEST PRACTICES FOR FUTURE CLEANUP

### DO:
✅ Delete files incrementally (by category/phase)  
✅ Create a summary before deleting  
✅ Verify no imports/requires in code  
✅ Check routes, services, migrations  
✅ Commit deletions with clear messages  
✅ Tag cleanup milestones  
✅ Document why files were deleted  
✅ Preserve git history (don't force-push)  

### DON'T:
❌ Delete files in bulk without verification  
❌ Mass-delete without understanding purposes  
❌ Delete without checking imports  
❌ Delete production code  
❌ Use `git clean -fd` blindly  
❌ Delete without backup/tag  
❌ Skip testing after cleanup  
❌ Ignore .gitignore patterns  

---

## 🔄 MAINTENANCE SCHEDULE

### Weekly
- Review `.git status` for unexpected files
- Delete temporary debug files if accumulated

### Monthly
- Run cleanup phase if 20+ utility files accumulated
- Archive old .md fix documentation
- Review and clean `/runtime` directory

### Quarterly
- Full codebase audit (see audit script below)
- Archive old logs
- Update documentation

---

## 🔍 AUDIT SCRIPT

```bash
#!/bin/bash
# Run quarterly to identify candidates for cleanup

echo "=== Codebase Audit ==="

# Find PHP files that match deletion patterns
echo "PHP Utility Files:"
find . -maxdepth 1 -name "verify_*.php" -o -name "fix_*.php" -o -name "setup_*.php" -o -name "add_*.php" -o -name "populate_*.php" | wc -l

# Find .md files that match patterns
echo ".md Documentation Files:"
find . -maxdepth 1 -name "*_GUIDE.md" -o -name "*_REPORT.md" -o -name "*_SUMMARY.md" -o -name "*_CHECKLIST.md" | wc -l

# Find debug files
echo "Debug Files:"
find . -name "*_debug.log" -o -path "./.kombai/debug/*" | wc -l

# Check disk usage
echo "Total Size:"
du -sh .

# Show total file count
echo "File Count:"
find . -type f | wc -l
```

---

## 🚀 GITIGNORE ENHANCEMENT

Add to `.gitignore` to prevent future clutter:

```gitignore
# Development utilities (never commit)
verify_*.php
fix_*.php
setup_*.php
add_*.php
populate_*.php
check_*.php
test_*.php

# Debug files
*_debug.log
firebase-debug.log
.kombai/debug/

# Build/fix documentation (use docs/ instead)
*_GUIDE.md
*_REPORT.md
*_SUMMARY.md
*_CHECKLIST.md
*_TESTING_GUIDE.md
*_FIX.md

# Temporary files
*.bak
*.tmp
*.swp
*.swo
*~
```

---

## 🎓 DEVELOPER GUIDELINES

### When Creating New Files:

1. **Production Code** → Goes in appropriate directory (controllers, models, etc.)
2. **Documentation** → Use `docs/` folder with clear naming
3. **Quick Fixes/Debug** → Use a feature branch, delete before merge
4. **Test Utilities** → Use `codeception/` or test directories, never root
5. **One-time Scripts** → Run locally, don't commit (or use `/scripts` folder)

### Before Committing:
- [ ] No utility scripts (verify_*.php, fix_*.php)
- [ ] No debug files (*_debug.log)
- [ ] No .md documentation about fixes (put in docs/)
- [ ] No test files in root (use codeception)
- [ ] Run cleanup audit

---

## 📞 CLEANUP SUPPORT

### Questions?
- Check `.git log` for why a file was deleted
- Check git tags: `git tag -l "*cleanup*"`
- Read previous cleanup reports

### Rollback Procedure:
```bash
# Find the cleanup commit
git log --oneline | grep -i cleanup

# Revert that commit
git revert <commit-hash>

# Or just restore specific file
git checkout <commit-hash>^ -- <file-path>
```

---

**Codebase Hygiene: ✅ OPTIMIZED FOR PRODUCTION**
