# Git Hooks Guide for Developers

This guide explains how Git hooks work with our code review system and how to set them up with minimal configuration.

## 🚀 Quick Setup (One Command)

```bash
# Run this from your project root
./review-system/setup-git-hooks.sh
```

That's it! Your Git hooks are now configured and ready to use.

## 🔍 What are Git Hooks?

Git hooks are automatic scripts that run at specific points in your Git workflow. Think of them as quality gates that check your code before it gets committed or pushed.

### **Pre-Commit Hook** (`pre-commit`)
- **When**: Before you commit code
- **What it does**: Quick code review (fast, basic checks)
- **Why**: Catches issues early, prevents bad commits

### **Pre-Push Hook** (`pre-push`)
- **When**: Before you push code to remote repository
- **What it does**: Full code review (comprehensive analysis)
- **Why**: Ensures only high-quality code reaches your team

## 📋 How It Works

### **Normal Development Flow**
```bash
# 1. Make changes to your code
git add .

# 2. Commit (triggers pre-commit hook)
git commit -m "Add new feature"
# 🔍 Pre-commit hook runs automatically
# ✅ If passes: commit is created
# ❌ If fails: commit is blocked

# 3. Push (triggers pre-push hook)
git push origin feature-branch
# 🔍 Pre-push hook runs automatically
# ✅ If passes: code is pushed
# ❌ If fails: push is blocked
```

### **What Happens When Hooks Run**

#### **Pre-Commit Hook**
```bash
🔍 Running pre-commit code review...
✅ Pre-commit review passed!
```

#### **Pre-Push Hook**
```bash
🔍 Running pre-push code review...
✅ Pre-push review passed!
```

## 🛠️ Manual Commands

You can also run reviews manually:

```bash
# Quick review (same as pre-commit)
composer run review:quick

# Full review (same as pre-push)
composer run review:full

# Standard review
composer run review
```

## ⚡ Quick Mode vs Full Mode

### **Quick Mode** (Pre-Commit)
- **Speed**: Very fast (< 5 seconds)
- **Checks**: Basic code style and security
- **Files**: Limited to changed files
- **Rules**: PSR-12, Security checks only

### **Full Mode** (Pre-Push)
- **Speed**: Comprehensive (10-30 seconds)
- **Checks**: All rules and best practices
- **Files**: Entire codebase
- **Rules**: PSR-12, Laravel, Security, MongoDB, Custom rules

## 🔧 Configuration

The setup script automatically creates a default configuration:

```php
// review-system/config.php
return [
    'rules' => [
        'psr12' => true,        // PHP coding standards
        'laravel' => true,      // Laravel best practices
        'security' => true,     // Security checks
        'mongodb' => true,      // MongoDB usage validation
    ],
    'quick_mode' => [
        'enabled' => true,
        'rules' => ['psr12', 'security'],  // Only these rules in quick mode
        'max_files' => 50,                 // Limit files for speed
    ],
];
```

## 🚨 What Happens When Review Fails?

### **Pre-Commit Failure**
```bash
❌ Quick code review failed. Please fix issues before committing.
💡 Run 'composer run review:quick' to see details.
```

### **Pre-Push Failure**
```bash
❌ Full code review failed. Please fix issues before pushing.
💡 Run 'composer run review:full' to see details.
```

## 📊 Understanding Review Results

### **Success Case**
```bash
✅ No violations found.
```

### **Issues Found**
```bash
== Violations found. ==

📄 Report saved to: /path/to/codementor-ai/reports/report-20250127_143022.html
🌐 View in browser: http://localhost/project/codementor-ai/reports/report-20250127_143022.html
```

## 🔍 Reading the HTML Report

When violations are found, an HTML report is generated with:

- **Interactive Filtering**: Filter by severity, category, or file
- **Code Highlighting**: Syntax-highlighted code snippets
- **Issue Details**: Description, bad code, suggested fix
- **Navigation**: Quick links between issues

## 🛠️ Troubleshooting

### **Hook Not Running**
```bash
# Check if hooks are executable
ls -la .git/hooks/

# Make hooks executable
chmod +x .git/hooks/pre-commit
chmod +x .git/hooks/pre-push
```

### **Composer Scripts Not Found**
```bash
# Regenerate composer autoload
composer dump-autoload

# Check if scripts are in composer.json
cat composer.json | grep -A 10 '"scripts"'
```

### **Permission Issues**
```bash
# Make setup script executable
chmod +x review-system/setup-git-hooks.sh

# Run setup again
./review-system/setup-git-hooks.sh
```

## 🎯 Best Practices

### **For Developers**
1. **Commit Frequently**: Small commits are easier to review
2. **Fix Issues Early**: Address pre-commit failures immediately
3. **Check Before Push**: Run `composer run review:full` before pushing
4. **Read Reports**: Use HTML reports to understand issues

### **For Teams**
1. **Standardize Setup**: Use the setup script for all developers
2. **Document Issues**: Share common fixes with the team
3. **Regular Reviews**: Use full reviews for important changes
4. **Continuous Improvement**: Update rules based on team feedback

## 🔄 Disabling Hooks (Temporary)

If you need to bypass hooks temporarily:

```bash
# Skip pre-commit hook
git commit -m "message" --no-verify

# Skip pre-push hook
git push origin branch --no-verify
```

⚠️ **Warning**: Only use `--no-verify` for emergency fixes. Always run reviews manually afterward.

## 📞 Getting Help

- **Setup Issues**: Run `./review-system/setup-git-hooks.sh` again
- **Configuration**: Check `review-system/config.php`
- **Documentation**: See `review-system/docs/README.md`
- **Reports**: View HTML reports in `codementor-ai/reports/`

---

**Your code review system is now protecting your codebase automatically! 🎉** 