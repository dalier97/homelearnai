# Claude Code Hooks Configuration

This directory contains the Claude Code hooks configuration for automatic code quality checks and testing.

## 🚀 What's Configured

### PostToolUse Hooks (Run after file changes)

1. **PHP Quality Check** (`php-quality-check.sh`)
   - **Triggers**: When `.php` files are created/edited
   - **Actions**:
     - PHP syntax validation (`php -l`)
     - Laravel Pint code formatting (auto-fix)
     - Basic security checks (XSS, debug statements)
     - **PHPStan static analysis** (type checking on modified files)
   - **Timeout**: 30 seconds

2. **JavaScript/TypeScript Quality Check** (`js-quality-check.sh`)
   - **Triggers**: When `.js`, `.ts`, `.tsx`, `.jsx` files are created/edited
   - **Actions**:
     - TypeScript type checking (non-blocking)
     - ESLint linting (auto-fix where possible)
     - Prettier code formatting (auto-fix)
     - Basic security checks (eval, innerHTML)
   - **Timeout**: 45 seconds

3. **Test Runner** (`test-runner.sh`)
   - **Triggers**: When source code files are modified
   - **Actions**:
     - Runs relevant PHP unit tests
     - Runs JavaScript tests (if available)
     - Triggers E2E tests for critical changes (background)
   - **Timeout**: 60 seconds
   - **Background**: Yes (for E2E tests)

### PreTaskStart Hook

- **Environment Check**: Shows current environment and basic project info

## 📁 File Structure

```
.claude/
├── hooks.json              # Hook configuration
├── README.md              # This file
└── hooks/
    ├── php-quality-check.sh       # PHP quality checks
    ├── js-quality-check.sh        # JS/TS quality checks
    └── test-runner.sh             # Test runner
```

## 🛠️ Requirements

### For PHP Hooks:

- PHP 8.0+ installed
- Laravel Pint (`composer require laravel/pint`)
- PHPUnit (for tests)

### For JavaScript Hooks:

- Node.js 18+ installed
- ESLint (`npm install eslint`)
- Prettier (`npm install prettier`)
- TypeScript (`npm install typescript`)

## 🔧 Customization

### Disabling Hooks Temporarily

You can disable hooks by:

1. Renaming `hooks.json` to `hooks.json.disabled`
2. Or commenting out specific hook configurations

### Adjusting Timeouts

Edit the `timeout` values in `hooks.json` (in milliseconds):

- PHP checks: 30000ms (30 seconds)
- JS checks: 45000ms (45 seconds)
- Tests: 60000ms (60 seconds)

### Adding New Checks

1. Create a new script in the `hooks/` directory
2. Make it executable: `chmod +x .claude/hooks/your-script.sh`
3. Add a new hook configuration in `hooks.json`

## 🧪 Testing the Hooks

You can test the individual scripts manually:

```bash
# Test PHP quality check
.claude/hooks/php-quality-check.sh

# Test JS quality check
.claude/hooks/js-quality-check.sh

# Test runner
.claude/hooks/test-runner.sh
```

## 📝 Environment Variables Available

- `$CLAUDE_FILE_PATHS`: Space-separated list of file paths that were modified
- `$CLAUDE_PROJECT_DIR`: Project root directory
- `$CLAUDE_TOOL_OUTPUT`: Output from the tool that triggered the hook

## 🎯 Hook Matching Patterns

- `Write:*.php` - Matches when PHP files are created
- `Edit:*.php` - Matches when PHP files are edited
- `MultiEdit:*.php` - Matches when multiple PHP files are edited
- Use `|` to combine patterns: `Write:*.php|Edit:*.php`

## ⚠️ Troubleshooting

### Hook Not Running

- Check that the hook scripts are executable
- Verify the file patterns match your changes
- Check Claude Code logs for errors

### Performance Issues

- Increase timeouts if needed
- Consider running heavy operations in background
- Use more specific file patterns to reduce unnecessary runs

### False Positives

- Adjust security checks in the individual scripts
- Add file exclusions for generated files
- Modify linter configurations

### PHPStan Issues

- Run `composer run phpstan` to see all type errors in the codebase
- Fix return type mismatches by using union types (e.g., `View|RedirectResponse`)  
- Add proper type hints to method parameters and return values
- Use `instanceof` checks before calling methods on exception interfaces
- Replace `env()` calls with `config()` in classes outside config directory

## 💡 Best Practices

1. **Keep hooks fast** - Aim for under 30 seconds execution time
2. **Use auto-fix where possible** - Let tools fix issues automatically
3. **Provide clear feedback** - Use colors and clear messages
4. **Handle errors gracefully** - Don't block development on minor issues
5. **Test hooks manually** - Verify they work before relying on them
