#!/bin/bash

if [ -z "$1" ]; then
    echo "Usage: $0 <version>"
    exit 1
fi

VERSION=$1

# Load environment variables
source ./envs.sh

mkdir -p $DIST_DIR
zip -r $DIST_DIR/betterportal-theme-embedded-$VERSION.zip
