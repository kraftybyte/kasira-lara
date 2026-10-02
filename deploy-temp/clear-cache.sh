#!/bin/bash
# Script untuk clear semua cache Laravel
# Upload ke server dan jalankan: php clear-cache.php

echo "Clearing Laravel caches..."
echo "========================="

# Clear config cache
echo "[1/6] Clearing config cache..."
php artisan config:clear
if [ $? -eq 0 ]; then echo "✓ Config cleared"; else echo "✗ Config failed"; fi

# Clear route cache
echo "[2/6] Clearing route cache..."
php artisan route:clear
if [ $? -eq 0 ]; then echo "✓ Routes cleared"; else echo "✗ Routes failed"; fi

# Clear view cache
echo "[3/6] Clearing view cache..."
php artisan view:clear
if [ $? -eq 0 ]; then echo "✓ Views cleared"; else echo "✗ Views failed"; fi

# Clear application cache
echo "[4/6] Clearing application cache..."
php artisan cache:clear
if [ $? -eq 0 ]; then echo "✓ Cache cleared"; else echo "✗ Cache failed"; fi

# Clear compiled classes
echo "[5/6] Clearing compiled classes..."
php artisan clear-compiled
if [ $? -eq 0 ]; then echo "✓ Compiled cleared"; else echo "✗ Compiled failed"; fi

# Recreate bootstrap cache
echo "[6/6] Recreating bootstrap cache..."
php artisan optimize:clear
if [ $? -eq 0 ]; then echo "✓ Optimize cleared"; else echo "✗ Optimize failed"; fi

echo ""
echo "========================="
echo "Cache clearing complete!"
echo ""
echo "NOTE: Jika masih ada masalah, cek log:"
echo "  cat storage/logs/laravel.log | tail -50"
