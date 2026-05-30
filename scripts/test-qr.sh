#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

echo "==> Install Python dependency (segno)..."
if command -v pip3 >/dev/null 2>&1; then
    sudo pip3 install -r scripts/requirements.txt --break-system-packages 2>/dev/null \
        || sudo pip3 install -r scripts/requirements.txt
else
    echo "pip3 not found. Run: sudo apt install -y python3-pip"
    exit 1
fi

OUTPUT="${1:-/tmp/test-qr.svg}"

echo "==> Generate test QR..."
python3 scripts/generate_qr_signature.py "test" public/images/logo_m.png "$OUTPUT"

echo "==> Result:"
ls -la "$OUTPUT"
