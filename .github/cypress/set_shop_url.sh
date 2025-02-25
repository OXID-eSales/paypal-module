#!/bin/bash

replace_shop_url_custom() {
    local config_file="source/source/config.inc.php"
    local new_url="$1"

    # Check if URL argument is provided
    if [ -z "$new_url" ]; then
        echo "Error: Please provide a URL as an argument"
        echo "Usage: $0 <new_url>"
        echo "Example: $0 http://example.com/"
        exit 1
    fi;

    # Check if file exists
    if [ ! -f "$config_file" ]; then
        echo "Error: Config file not found at $config_file"
        exit 1
    fi;

    # Add debug output
    echo "Replacing shop URL with '$new_url' in: $config_file"

    # Create a backup of the original file
    cp "$config_file" "${config_file}.bak"

    # Replace the shop URL using sed
    sed -i "s|\$this->sShopURL\s*=\s*'[^']*';|\$this->sShopURL     = '$new_url';|" "$config_file"

    # Verify the replacement
    if grep -q "\$this->sShopURL     = '$new_url';" "$config_file"; then
        echo "Shop URL successfully replaced with: $new_url"
    else
        echo "Error: Shop URL replacement failed"
        # Restore from backup
        mv "${config_file}.bak" "$config_file"
        exit 1
    fi
}

# Run the function with the provided argument
replace_shop_url_custom "$1"
