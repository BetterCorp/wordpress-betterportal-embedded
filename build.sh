#!/bin/bash

if [ -z "$1" ]; then
  echo "Usage: $0 <version>"
  exit 1
fi

VERSION=$1

# Load environment variables
source ./envs.sh
chmod +x ./cleanup.sh
./cleanup.sh

mkdir -p $BUILD_DIR

cp -r $SOURCE_DIR/* $BUILD_DIR
cp PLUGIN_README.txt $BUILD_DIR/README.txt
cp LICENSE $BUILD_DIR/LICENSE.txt

# Copy DEFINITION.txt to build directory and replace VERSION
cp DEFINITION.txt $BUILD_DIR/DEFINITION.txt

# Read the contents of the updated DEFINITION.txt
DEFINITION=$(cat $BUILD_DIR/DEFINITION.txt)

# Remove the temporary DEFINITION.txt from build directory
rm $BUILD_DIR/DEFINITION.txt

# Function to add definition to file
add_definition() {
  local file=$1
  local is_php=$2

  if [ "$is_php" = true ]; then
    # For PHP files, replace the existing <?php tag
    sed -i '1s/^<?php//' "$file" # Remove existing <?php if present
    echo "<?php" >"$file.tmp"
    echo "$DEFINITION" >>"$file.tmp"
    cat "$file" >>"$file.tmp"
    mv "$file.tmp" "$file"
  else
    # For other files, insert at the beginning
    echo "$DEFINITION" >"$file.tmp"
    cat "$file" >>"$file.tmp"
    mv "$file.tmp" "$file"
  fi
}

# Function to perform basic minification
basic_minify() {
  local file=$1
  local ext=${file##*.}
  local output_file="${file%.${ext}}.min.${ext}"

  if [ "$ext" = "js" ]; then
    # Basic JS minification
    sed -e 's/\/\/.*$//' -e '/^\s*\/\*/,/\*\//d' -e 's/^\s*//' -e 's/\s*$//' -e 's/\s\+/ /g' "$file" | tr -d '\n' >"$output_file"
  elif [ "$ext" = "css" ]; then
    # Basic CSS minification
    sed -e 's/\/\*.*\*\///g' -e 's/^\s*//' -e 's/\s*$//' -e 's/\s\+/ /g' -e 's/;\s*/;/g' -e 's/:\s*/:/g' -e 's/,\s*/,/g' "$file" | tr -d '\n' >"$output_file"
  fi
}

# Add definition to specified files
add_definition "$BUILD_DIR/betterportal-theme-embedded.php" true
add_definition "$BUILD_DIR/widgets/betterportal-embed-widget.php" true

# Minify CSS and JS files
basic_minify "$BUILD_DIR/css/betterportal-loader.css"
basic_minify "$BUILD_DIR/scripts/betterportal-loader.js"

# Remove non-minified files
rm $BUILD_DIR/css/betterportal-loader.css
rm $BUILD_DIR/scripts/betterportal-loader.js

add_definition "$BUILD_DIR/css/betterportal-loader.min.css" false
add_definition "$BUILD_DIR/scripts/betterportal-loader.min.js" false

# Replace version placeholders in other files
sed -i "s/{{VERSION}}/$VERSION/g" $BUILD_DIR/README.txt
sed -i "s/{{VERSION}}/$VERSION/g" $BUILD_DIR/betterportal-theme-embedded.php
sed -i "s/{{VERSION}}/$VERSION/g" $BUILD_DIR/widgets/betterportal-embed-widget.php
sed -i "s/{{VERSION}}/$VERSION/g" $BUILD_DIR/css/betterportal-loader.min.css
sed -i "s/{{VERSION}}/$VERSION/g" $BUILD_DIR/scripts/betterportal-loader.min.js

# Update references to use minified files
sed -i 's/betterportal-loader\.css/betterportal-loader.min.css/g' $BUILD_DIR/betterportal-theme-embedded.php
sed -i 's/betterportal-loader\.js/betterportal-loader.min.js/g' $BUILD_DIR/betterportal-theme-embedded.php
