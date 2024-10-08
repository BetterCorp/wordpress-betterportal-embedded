#!/bin/bash

# Get the absolute path of the current directory
CURRENT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Directory paths
export SOURCE_DIR="$CURRENT_DIR/src"
export BUILD_DIR="$CURRENT_DIR/build"
export DIST_DIR="$CURRENT_DIR/dist"

# File names
export MAIN_PHP_FILE="betterportal-theme-embedded.php"
export WIDGET_PHP_FILE="widgets/betterportal-embed-widget.php"
export CSS_FILE="css/betterportal-loader.css"
export JS_FILE="scripts/betterportal-loader.js"

# Other configuration
export PLUGIN_README="PLUGIN_README.txt"
export LICENSE_FILE="LICENSE"
export DEFINITION_FILE="DEFINITION.txt"
