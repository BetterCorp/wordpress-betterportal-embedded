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

# Change to the build directory and create the zip file
pushd $BUILD_DIR
zip -r $DIST_DIR/betterportal-theme-embedded-$VERSION.zip .
popd
