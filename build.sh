#!/bin/bash

PLUGIN_NAME="turnstile-protection"
VERSION="1.1.0"

zip -r "${PLUGIN_NAME}-${VERSION}.zip" \
    turnstile-protection.php \
    README.md \
    languages/ \
    -x "*.DS_Store"

echo "Archiv erstellt: ${PLUGIN_NAME}-${VERSION}.zip"
