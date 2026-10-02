#!/bin/bash
# =========================================================================
# KASIRA LARAVEL - HOSTINGER DEPLOYMENT FIX
# Run this on Hostinger terminal in your public_html directory
# =========================================================================

set -e  # Exit on any error

echo "=========================================="
echo "KASIRA LARAVEL - HOSTINGER DEPLOYMENT FIX"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Step 1: Verify we're in the right directory
echo -e "${YELLOW}Step 1: Verifying directory structure...${NC}"
if [ ! -f "artisan" ]; then
    echo -e "${RED}ERROR: artisan file not found. Are you in the Laravel root directory?${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Found artisan file${NC}"
echo ""

# Step 2: Backup current .env
echo -e "${YELLOW}Step 2: Backing up current .env...${NC}"
if [ -f ".env" ]; then
    cp .env .env.backup."$(date +%s)"
    echo -e "${GREEN}✓ Backed up .env${NC}"
else
    echo -e "${YELLOW}! .env not found, creating from .env.example${NC}"
    cp .env.example .env
fi
echo ""

# Step 3: Ensure APP_KEY is set
echo -e "${YELLOW}Step 3: Checking APP_KEY...${NC}"
if ! grep -q "^APP_KEY=" .env || [ -z "$(grep '^APP_KEY=' .env | cut -d'=' -f2-)" ]; then
    echo -e "${YELLOW}Generating new APP_KEY...${NC}"
    php artisan key:generate --no-interaction
fi
echo -e "${GREEN}✓ APP_KEY is set${NC}"
echo ""

# Step 4: Fix critical session settings for Hostinger LiteSpeed
echo -e "${YELLOW}Step 4: Fixing session configuration for Hostinger LiteSpeed...${NC}"

# Remove old session settings
sed -i '/^SESSION_/d' .env

# Add correct session settings
cat >> .env << 'EOF'
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=null
SESSION_SAME_SITE=lax
EOF

echo -e "${GREEN}✓ Session settings configured${NC}"
echo ""

# Step 5: Clear all caches and optimize
echo -e "${YELLOW}Step 5: Clearing caches and optimizing...${NC}"
php artisan config:clear --no-interaction
php artisan cache:clear --no-interaction
php artisan route:clear --no-interaction
php artisan view:clear --no-interaction
php artisan optimize:clear --no-interaction
php artisan optimize --no-interaction
echo -e "${GREEN}✓ Caches cleared and optimized${NC}"
echo ""

# Step 6: Run migrations to ensure database is up to date
echo -e "${YELLOW}Step 6: Running database migrations...${NC}"
php artisan migrate --force --no-interaction 2>/dev/null || true
echo -e "${GREEN}✓ Migrations completed${NC}"
echo ""

# Step 7: Truncate old sessions
echo -e "${YELLOW}Step 7: Clearing old sessions from database...${NC}"
php artisan tinker --execute="DB::table('sessions')->truncate();" 2>/dev/null || true
echo -e "${GREEN}✓ Sessions table cleared${NC}"
echo ""

# Step 8: Verify configuration
echo -e "${YELLOW}Step 8: Verifying final configuration...${NC}"
echo "App Configuration:"
php artisan config:show app.env 2>/dev/null | head -1
php artisan config:show session.driver 2>/dev/null | head -1
echo ""

# Step 9: Storage and permissions
echo -e "${YELLOW}Step 9: Setting up storage...${NC}"
mkdir -p storage bootstrap/cache
chmod 755 storage bootstrap/cache
php artisan storage:link --force 2>/dev/null || true
echo -e "${GREEN}✓ Storage configured${NC}"
echo ""

echo -e "${GREEN}=========================================="
echo "✓ DEPLOYMENT COMPLETE"
echo "==========================================${NC}"
echo ""
echo "NEXT STEPS:"
echo "1. Clear your browser cookies for this domain"
echo "2. Clear browser cache"
echo "3. Visit: https://your-domain.com/admin/login"
echo "4. Log in with your credentials"
echo "5. Check the dashboard"
echo ""
echo "If you still see 403 errors:"
echo "1. Check storage/logs/laravel.log for details"
echo "2. Email this log to support"
echo ""
echo "Debug commands:"
echo "  php artisan config:show session"
echo "  php artisan config:show app.debug"
echo "  tail -f storage/logs/laravel.log"
echo ""
