# 🧰 Developer Guide: Efficiently Fixing Code Review Issues in Laravel Projects

This guide helps developers handle large volumes of code issues flagged by:
- ✅ PHPStan (static analysis)
- ✅ PHPCS (code style)
- ✅ Custom rules (e.g., MongoDB usage in controllers)

---

## 🔧 1. Use PHPCBF for Auto-Fixable Issues (Low Risk)

You’ll likely see dozens (or hundreds) of issues like:
- Trailing whitespace
- Missing newlines
- Bad `use` formatting
- Empty lines in control blocks

These are safe to **auto-fix** using:

```bash
composer run fix-style
```

This will:
- Instantly fix 90–95% of style issues
- Leave only the opinionated or structural ones

**✅ Best Practice:**  
Commit all autofixes separately:

```bash
git commit -am "chore: autofixed code style violations via phpcbf"
```

---

## 🧠 2. Triage PHPStan Errors (Manual Fixes)

PHPStan reports logic and type-level bugs like:
- Incorrect constructor args
- Undefined variables or properties
- Calling methods that don’t exist
- Return type mismatches

These **must be fixed manually** with care.

**✅ Best Practice:**  
Fix issues by folder or by type:

```bash
composer run phpstan -- app/Http/Controllers
composer run phpstan -- app/Services
```

---

## 📂 3. Fix PHPCS in Layers (Optional)

If you don’t want to auto-fix everything:

- See a full summary:
```bash
composer run phpcs -- --report=summary
```

- Or lint a specific folder:
```bash
composer run phpcs -- app/Models
composer run phpcs -- database/migrations
```

---

## 📈 4. Track Review Progress Over Time (Optional)

- Save reports as flat HTML or JSON
- Compare reports before/after cleanup
- Use this for CI pipelines or PR tracking

---

## ✅ Summary Table

| Task                          | Tool     | Command                                                 |
|-------------------------------|----------|----------------------------------------------------------|
| Auto-fix safe style issues    | PHPCBF   | `composer run fix-style`                                |
| See summary only              | PHPCS    | `composer run phpcs -- --report=summary`                |
| Target folders only           | PHPCS    | `composer run phpcs -- app/Services`                    |
| Fix static analysis errors    | PHPStan  | `composer run phpstan -- app/Models`                    |
| Clean commit after fixing     | Git      | `git commit -am "chore: code style cleanup"`            |

---

## 🔁 Review Before You Commit

Always run:

```bash
# Full review pipeline
composer run review

# Auto-fix code style issues  
composer run fix-style

# Individual tools
composer run phpstan
composer run phpcs
composer run phpcbf
```

Then verify:
- ✅ PHPStan shows 0–minimal errors
- ✅ PHPCS issues are fixed or justified
- ✅ HTML report contains no critical violations

---

Let’s keep our codebase clean, predictable, and easy to maintain! 💡
