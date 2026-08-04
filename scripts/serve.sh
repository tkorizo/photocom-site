#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
exec php \
  -d upload_max_filesize=64M \
  -d post_max_size=64M \
  -d memory_limit=256M \
  -S "${HOST:-localhost}:${PORT:-8000}" router.php
