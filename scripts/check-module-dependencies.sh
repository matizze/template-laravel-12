#!/usr/bin/env bash
set -e
REPO_ROOT="$(git rev-parse --show-toplevel)"
MODULES_DIR="$REPO_ROOT/app-modules"
VIOLATIONS=0

check_module() {
    local module="$1"; shift; local allowed=("$@")
    local module_dir="$MODULES_DIR/$module/src"
    [ -d "$module_dir" ] || return 0
    while IFS= read -r line; do
        file=$(echo "$line" | cut -d: -f1)
        statement=$(echo "$line" | cut -d: -f2- | xargs)
        imported_module=$(echo "$statement" | sed -n 's/^use Modules\\\([^\\]*\)\\.*/\1/p')
        [ -z "$imported_module" ] && continue
        local allowed_import=false
        for a in "${allowed[@]}"; do [ "$a" = "$imported_module" ] && allowed_import=true && break; done
        if [ "$allowed_import" = false ]; then
            echo "VIOLATION: $module cannot import from $imported_module"
            echo "  File: $file"
            echo "  Statement: $statement"
            VIOLATIONS=$((VIOLATIONS + 1))
        fi
    done < <(grep -rn "^use Modules\\\\" "$module_dir" 2>/dev/null || true)
}

echo "=== Module Dependency Check ==="
check_module "core"
check_module "user" "Core" "Workspace"  # Workspace allowed via HasWorkspaces trait
check_module "auth" "Core" "User"
check_module "workspace" "Core" "User"
echo "=== Results ==="
if [ "$VIOLATIONS" -eq 0 ]; then echo "OK: No dependency violations found."; exit 0
else echo "FAILED: $VIOLATIONS violation(s) found."; exit 1; fi
