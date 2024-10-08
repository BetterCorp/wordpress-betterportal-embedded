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

# Create a temporary directory for the plugin
TEMP_DIR=$(mktemp -d)
PLUGIN_DIR="$TEMP_DIR/betterportal-theme-embedded"
mkdir -p "$PLUGIN_DIR"

# Copy build files to the plugin directory
cp -R $BUILD_DIR/* "$PLUGIN_DIR"

# Change to the temporary directory and create the zip file
pushd $TEMP_DIR
zip -r $DIST_DIR/betterportal-theme-embedded-v$VERSION.zip betterportal-theme-embedded -x ".*" -x "__MACOSX"
popd

# Clean up the temporary directory
rm -rf $TEMP_DIR

echo "Plugin zip file created: $DIST_DIR/betterportal-theme-embedded-v$VERSION.zip"
