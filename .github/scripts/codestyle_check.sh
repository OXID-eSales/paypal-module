#!/bin/bash

# Function to show usage
show_usage() {
    echo "Usage: $0 [phpmd|phpstan|phpcs|all] [commit]"
    echo "  First argument:"
    echo "    phpmd   - Run PHP Mess Detector only"
    echo "    phpstan - Run PHPStan only"
    echo "    phpcs   - Run PHP CodeSniffer only"
    echo "    all     - Run all checks (default)"
    echo "  Second argument (optional):"
    echo "    commit  - Check files from the last commit"
    echo "    If omitted, checks locally changed files (default)"
    exit 1
}

# Function to get changed files
get_changed_files() {
    local mode=${1:-local}  # Default to local if no mode specified
    local files=""

    if [ "$mode" = "commit" ]; then
        files=$(git diff-tree --no-commit-id --name-only -r HEAD)
    else
        files=$(git diff --name-only HEAD)
    fi

    # Filter for PHP files only and convert to space-separated list
    echo "$files" | grep -E '\.php$' | tr '\n' ' '
}

# Function to run PHPCS
run_phpcs() {
    echo "Running PHP CodeSniffer..."
    composer phpcs
}

# Function to run PHPStan
run_phpstan() {
    local changed_files="$1"
    if [ -n "$changed_files" ]; then
        echo "Running PHPStan on changed files: $changed_files"
        vendor/bin/phpstan analyse -c tests/PhpStan/phpstan.neon --level=max $changed_files --memory-limit=1G || true
    else
        echo "No PHP files to check with PHPStan"
    fi
}

# Function to run PHPMD
run_phpmd() {
    local files="$1"
    if [ -n "$files" ]; then
        echo "Running PHPMD on changed files: $files"
        vendor/bin/phpmd $files text tests/PhpMd/phpmd.baseline.xml --exclude tests/ --suffixes php --strict || true
    else
        echo "No PHP files to check with PHPMD"
    fi
}

# Get check type and mode from arguments
CHECK_TYPE="${1:-all}"
CHECK_MODE="${2:-local}"

# Get changed files
CHANGED_FILES=$(get_changed_files "$CHECK_MODE")

# Run the appropriate checks
case "$CHECK_TYPE" in
    phpmd)
        run_phpmd "$CHANGED_FILES"
        ;;
    phpstan)
        run_phpstan "$CHANGED_FILES"
        ;;
    phpcs)
        run_phpcs
        ;;
    all)
        run_phpcs
        run_phpstan "$CHANGED_FILES"
        run_phpmd "$CHANGED_FILES"
        ;;
    *)
        show_usage
        ;;
esac
