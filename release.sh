#!/bin/bash

if [ -z "$1" ]; then
    echo "Usage: $0 <version>"
    exit 1
fi

VERSION=$1
SOURCE_DIR="src/*"
BUILD_DIR="build"
DIST_DIR="dist"

rm -rf $DIST_DIR
mkdir -p $DIST_DIR
zip -r $DIST_DIR/betterportal-theme-embedded-$VERSION.zip
