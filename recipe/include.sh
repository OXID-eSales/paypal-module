#!/bin/bash

#### functions
# Function to find the module root by searching for the composer.json file
find_module_root() {
  # shellcheck disable=SC2155
  local current_dir=$(dirname "$(realpath "${BASH_SOURCE[0]}")")
  while [ "$current_dir" != "/" ]; do
    if [ -f "$current_dir/composer.json" ]; then
      echo "$current_dir"
      return
    fi
    current_dir=$(dirname "$current_dir")
  done

  echo "Error: Module root (composer.json) not found in any parent directories." >&2
  exit 1
}

# Function to find docker-compose.yml or docker-compose.yml.dist
find_project_root() {
  # Resolve to the absolute directory of the script
  local current_dir=$(dirname "$(realpath "${BASH_SOURCE[0]}")")

  # Traverse up the directory tree
  while [ "$current_dir" != "/" ]; do
    if [ -f "$current_dir/docker-compose.yml" ]; then
      echo "$current_dir"
      return
    elif [ -f "$current_dir/docker-compose.yml.dist" ]; then
      echo "$current_dir"
      return
    fi
    current_dir=$(dirname "$current_dir")
  done

  # If neither file is found, print an error and exit
  echo "Error: Neither docker-compose.yml nor docker-compose.yml.dist found in any parent directories." >&2
  exit 1
}


copy_tmp_module() {
  # Ensure MODULE_ROOT and PROJECT_ROOT are set
  if [ -z "$MODULE_ROOT" ] || [ -z "$PROJECT_ROOT" ]; then
    echo "Error: MODULE_ROOT or PROJECT_ROOT is not set."
    exit 1
  fi

  # Define the target directory
  local target_dir="$PROJECT_ROOT/tmp/paypal"

  # Create the target directory if it doesn't exist
  mkdir -p "$target_dir"

  # Copy MODULE_ROOT to PROJECT_ROOT/tmp/module
  echo "Copying $MODULE_ROOT to $target_dir..."
  cp -r "$MODULE_ROOT"/. "$target_dir"

  # Check if the copy was successful
  if [ $? -ne 0 ]; then
    echo "Error: Failed to copy $MODULE_ROOT to $target_dir."
    exit 1
  fi

  echo "Copy completed successfully!"

  # Remove the ./source directory
  local source_dir="$PROJECT_ROOT/source"
  if [ -d "$source_dir" ]; then
    echo "Removing $source_dir..."
    rm -rf "$source_dir"  # Be cautious with rm -rf
    if [ $? -ne 0 ]; then
      echo "Error: Failed to remove $source_dir."
      exit 1
    fi
    echo "Source directory removed successfully!"
  else
    echo "No source directory found to remove."
  fi
}

restore_and_cleanup() {
  # Ensure MODULE_ROOT and PROJECT_ROOT are set
  if [ -z "$MODULE_ROOT" ] || [ -z "$PROJECT_ROOT" ]; then
    echo "Error: MODULE_ROOT or PROJECT_ROOT is not set."
    exit 1
  fi

  # Define the source directory
  local tmp_module_dir="$PROJECT_ROOT/tmp/paypal"

  # Check if the temporary module directory exists
  if [ ! -d "$tmp_module_dir" ]; then
    echo "Error: $tmp_module_dir does not exist. Nothing to restore."
    exit 1
  fi

  # Copy the tmp module back to MODULE_ROOT
  echo "Copying $tmp_module_dir back to $MODULE_ROOT..."
  mkdir -p "$MODULE_ROOT"  # Ensure the module root exists
  cp -r "$tmp_module_dir"/. "$MODULE_ROOT"

  # Check if the copy was successful
  if [ $? -ne 0 ]; then
    echo "Error: Failed to copy $tmp_module_dir to $MODULE_ROOT."
    exit 1
  fi

  echo "Copy completed successfully!"

  # Remove the tmp module directory
  echo "Removing $tmp_module_dir..."
  rm -rf "$tmp_module_dir"  # Be cautious with rm -rf

  # Check if the removal was successful
  if [ $? -ne 0 ]; then
    echo "Error: Failed to remove $tmp_module_dir."
    exit 1
  fi

  echo "$tmp_module_dir removed successfully!"
}
