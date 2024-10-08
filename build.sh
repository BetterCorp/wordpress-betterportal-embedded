#!/bin/bash

if [ -z "$1" ]; then
    echo "Usage: $0 <version>"
    exit 1
fi

VERSION=$1
SOURCE_DIR="src/*"
BUILD_DIR="build"
DIST_DIR="dist"

rm -rf $DIST_DIR $BUILD_DIR
mkdir -p $BUILD_DIR $DIST_DIR 
cp -r $SOURCE_DIR $BUILD_DIR
cp README.md $BUILD_DIR/README.txt
cp LICENSE $BUILD_DIR/LICENSE.txt

sed -i "s/{{VERSION}}/$VERSION/g" $BUILD_DIR/*
sed -i "s/{{VERSION}}/$VERSION/g" $BUILD_DIR/**/*

zip -r $DIST_DIR/betterportal-theme-embedded-$VERSION.zip $BUILD_DIR

rm -rf $BUILD_DIR