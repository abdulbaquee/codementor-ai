# 🚀 Quick Setup Guide for New Laravel Projects

## Copy Review System to Any Laravel Project

### 1. Copy the Review System Folder

```bash
# From your current project, copy the review-system folder
cp -r review-system/ /path/to/new-laravel-project/
```

### 2. Run the Installer

```bash
cd /path/to/new-laravel-project/
./review-system/install.sh
```

This installer will:
- ✅ Install required packages (phpstan/phpstan, squizlabs/php_codesniffer, nunomaduro/larastan)
- ✅ Update composer.json with scripts and autoload-dev
- ✅ Run composer dump-autoload

### 3. Test the Setup

```bash
# Test individual tools
composer run phpstan
composer run phpcs

# Test full pipeline
composer run review
```

### 4. Uninstall (Optional)

If you need to remove the review system:

```bash
./review-system/uninstall.sh
```

This will:
- ✅ Remove all dev packages
- ✅ Clean up composer.json
- ✅ Optionally remove the review-system folder
- ✅ Safe backup naming (won't overwrite user's `.bak` files)

## ✅ That's It!

Your new Laravel project now has the complete code review system with:
- ✅ Required packages installed (phpstan/phpstan, squizlabs/php_codesniffer, nunomaduro/larastan)
- ✅ PHPStan static analysis
- ✅ PHPCS code style checking
- ✅ Custom MongoDB usage detection
- ✅ HTML violation reports with browser access
- ✅ Auto-fix capabilities

## 🎯 Benefits of This Approach

- **⚡ 30-second setup** - Just copy and configure
- **🔒 No conflicts** - Self-contained configuration
- **📦 Portable** - Works on any Laravel project
- **🔄 Consistent** - Same rules across all projects 