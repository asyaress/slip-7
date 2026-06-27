#!/usr/bin/env bash

set -euo pipefail

cd "$(dirname "$0")/.."

php artisan security:reset-two-factor --force "$@"
