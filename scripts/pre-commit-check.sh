#!/usr/bin/env bash
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_ROOT"

echo "==> PHP format check (Pint)"
vendor/bin/pint --test

echo
echo "==> Frontend format (Prettier)"
npm run format

echo
echo "==> Frontend format check (Prettier)"
npm run format:check

echo
echo "==> Frontend lint check (ESLint)"
npm run lint:check

echo
echo "==> Test suite (testing env)"
php artisan test --env=testing --compact

echo
echo "All pre-commit checks passed."
