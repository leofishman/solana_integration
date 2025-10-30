#!/bin/bash

# Script to uninstall and reinstall the solana_integration module using DDEV
# This is useful for testing fresh installations and configuration changes

set -e  # Exit on any error

echo "================================================"
echo "Solana Integration Module Reinstall Script"
echo "================================================"
echo ""

# Check if module is currently installed
echo "Checking module status..."
MODULE_STATUS=$(ddev drush pm:list --filter=solana_integration --format=json 2>/dev/null || echo "{}")

if echo "$MODULE_STATUS" | grep -q "solana_integration"; then
    echo "✓ Module found"
    
    # Uninstall the module
    echo ""
    echo "Step 1: Uninstalling solana_integration module..."
    ddev drush pm:uninstall solana_integration -y
    echo "✓ Module uninstalled"
else
    echo "ℹ Module not currently installed"
fi

# Clear all caches
echo ""
echo "Step 2: Clearing all caches..."
ddev drush cr
echo "✓ Caches cleared"

# Reinstall the module
echo ""
echo "Step 3: Installing solana_integration module..."
ddev drush pm:enable solana_integration -y
echo "✓ Module installed"

# Clear caches again after installation
echo ""
echo "Step 4: Clearing caches after installation..."
ddev drush cr
echo "✓ Caches cleared"

# Show module status
echo ""
echo "Step 5: Verifying installation..."
ddev drush pm:list --filter=solana_integration
echo ""

# Display configuration
echo "Step 6: Displaying current configuration..."
ddev drush config:get solana_integration.settings
echo ""

echo "================================================"
echo "✓ Reinstallation complete!"
echo "================================================"
echo ""
echo "Module is now freshly installed with default configuration."
echo "Admin URL: /admin/config/services/solana-integration"
echo ""
echo "To view configuration in your browser:"
echo "  ddev launch /admin/config/services/solana-integration"
echo ""