#!/bin/bash

# WP News Source - Release Build Script
# Creates a clean production build of the plugin

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Get version from main plugin file
VERSION=$(grep "Version:" wp-news-source.php | sed 's/.*Version: *//' | tr -d ' ')

echo -e "${GREEN}Building WP News Source v${VERSION}${NC}"
echo "======================================"

# Create build directory
BUILD_DIR="temp-release"
PLUGIN_NAME="wp-news-source"
RELEASE_DIR="${BUILD_DIR}/${PLUGIN_NAME}"

# Clean previous builds
echo -e "${YELLOW}Cleaning previous builds...${NC}"
rm -rf ${BUILD_DIR}
rm -f ${PLUGIN_NAME}.zip ${PLUGIN_NAME}-*.zip

# Create directory structure
echo -e "${YELLOW}Creating build structure...${NC}"
mkdir -p ${RELEASE_DIR}

# Copy essential files and directories
echo -e "${YELLOW}Copying essential files...${NC}"

# Core files
cp wp-news-source.php ${RELEASE_DIR}/
cp uninstall.php ${RELEASE_DIR}/
cp readme.txt ${RELEASE_DIR}/

# Copy n8n integration file (IMPORTANT!)
cp n8n-integration.js ${RELEASE_DIR}/

# Admin directory
mkdir -p ${RELEASE_DIR}/admin
cp -r admin/class-wp-news-source-admin.php ${RELEASE_DIR}/admin/
cp -r admin/css ${RELEASE_DIR}/admin/
cp -r admin/js ${RELEASE_DIR}/admin/
cp -r admin/partials ${RELEASE_DIR}/admin/

# Database directory
mkdir -p ${RELEASE_DIR}/database
cp -r database/* ${RELEASE_DIR}/database/

# Includes directory
mkdir -p ${RELEASE_DIR}/includes
cp -r includes/* ${RELEASE_DIR}/includes/

# Languages directory
mkdir -p ${RELEASE_DIR}/languages
cp -r languages/* ${RELEASE_DIR}/languages/

# Optional: Include minimal documentation
echo -e "${YELLOW}Adding essential documentation...${NC}"
cp README.md ${RELEASE_DIR}/ 2>/dev/null || echo "README.md not included"
cp CHANGELOG.md ${RELEASE_DIR}/ 2>/dev/null || echo "CHANGELOG.md not included"

# Create ZIP file
echo -e "${YELLOW}Creating release package...${NC}"
cd ${BUILD_DIR}
zip -r ../${PLUGIN_NAME}.zip ${PLUGIN_NAME}
cd ..

# Clean up
echo -e "${YELLOW}Cleaning up...${NC}"
rm -rf ${BUILD_DIR}

# Summary
echo ""
echo -e "${GREEN}✅ Build completed successfully!${NC}"
echo "======================================"
echo -e "Package: ${GREEN}${PLUGIN_NAME}.zip${NC}"

# Show package size
SIZE=$(ls -lh ${PLUGIN_NAME}.zip | awk '{print $5}')
echo -e "Size: ${YELLOW}${SIZE}${NC}"

# List contents
echo ""
echo -e "${YELLOW}Package contents:${NC}"
unzip -l ${PLUGIN_NAME}.zip | grep -E "(php|js|css|mo|po|pot|txt|md)$" | wc -l | xargs echo "Total files:"

echo ""
echo -e "${GREEN}Ready for deployment!${NC}"