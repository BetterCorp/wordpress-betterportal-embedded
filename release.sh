#!/bin/bash

if [ -z "$1" ]; then
    echo "Usage: $0 <version>"
    exit 1
fi

VERSION=$1

# Load environment variables
source ./envs.sh

# Create the distribution directory if it doesn't exist
mkdir -p $DIST_DIR

# Change to the temporary directory and create the zip file
pushd $BUILD_DIR
zip -r $DIST_DIR/betterportal-theme-embedded-v$VERSION.zip . -x ".*" -x "__MACOSX"
popd

echo "Plugin zip file created: $DIST_DIR/betterportal-theme-embedded-v$VERSION.zip"
