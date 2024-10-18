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
mkdir -p $DIST_RELEASE_SRC_DIR
mkdir -p $DIST_RELEASE_ASSETS_DIR

# Change to the temporary directory and create the zip file
pushd $BUILD_DIR
zip -r $DIST_DIR/betterportal-theme-embedded-v$VERSION.zip . -x ".*" -x "__MACOSX"
popd

cp -rv "$BUILD_DIR/betterportal-theme-embedded/." $DIST_RELEASE_SRC_DIR
rm -rfv "$DIST_RELEASE_SRC_DIR/assets/"
cp -rv "$BUILD_DIR/betterportal-theme-embedded/assets/." $DIST_RELEASE_ASSETS_DIR

echo "Plugin zip file created: $DIST_DIR/betterportal-theme-embedded-v$VERSION.zip"
echo "WP Release ready: $DIST_RELEASE_SRC_DIR / $DIST_RELEASE_ASSETS_DIR"
