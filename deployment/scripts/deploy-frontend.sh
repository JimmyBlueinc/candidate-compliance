#!/bin/bash

# Goodwill Staffing - Frontend Deployment Script (Linux/Mac)
# This script builds the React frontend for production deployment

set -e  # Exit on error

echo "========================================"
echo "Frontend Production Deployment"
echo "========================================"
echo ""

# Check if we're in the frontend directory
if [ ! -f "package.json" ]; then
    echo "Error: This script must be run from the frontend directory"
    echo "Please run: cd frontend"
    exit 1
fi

# Check for required tools
echo "Checking prerequisites..."

# Check Node.js
if ! command -v node &> /dev/null; then
    echo "Error: Node.js not found. Please install Node.js 18+"
    exit 1
fi

NODE_VERSION=$(node -v | cut -d'v' -f2)
NODE_MAJOR=$(echo $NODE_VERSION | cut -d. -f1)

if [ "$NODE_MAJOR" -lt 18 ]; then
    echo "Error: Node.js 18+ required. Found: Node.js $NODE_VERSION"
    exit 1
fi

echo "✓ Node.js $NODE_VERSION found"

# Check npm
if ! command -v npm &> /dev/null; then
    echo "Error: npm not found"
    exit 1
fi

NPM_VERSION=$(npm -v)
echo "✓ npm $NPM_VERSION found"
echo ""

# Step 1: Check environment configuration
echo "Step 1: Checking environment configuration..."
if [ ! -f ".env.production" ]; then
    if [ -f ".env" ]; then
        echo "⚠ .env.production not found, using .env"
    else
        echo "Creating .env.production..."
        read -p "Enter your production API URL (e.g., https://api.yourdomain.com/api): " API_URL
        echo "VITE_API_BASE_URL=$API_URL" > .env.production
        echo "✓ .env.production created"
    fi
else
    echo "✓ .env.production found"
    if grep -q "VITE_API_BASE_URL" .env.production; then
        echo "  API URL: $(grep VITE_API_BASE_URL .env.production)"
    fi
fi
echo ""

# Step 2: Install dependencies
echo "Step 2: Installing dependencies..."
npm install
echo "✓ Dependencies installed"
echo ""

# Step 3: Build for production
echo "Step 3: Building for production..."
echo "This may take a few minutes..."
npm run build
echo "✓ Build completed successfully"
echo ""

# Step 4: Check build output
echo "Step 4: Verifying build output..."
if [ -d "dist" ]; then
    DIST_SIZE=$(du -sh dist | cut -f1)
    echo "✓ Build output found in dist/ folder ($DIST_SIZE)"
    
    if [ -f "dist/index.html" ]; then
        echo "✓ index.html found"
    else
        echo "⚠ Warning: index.html not found in dist/"
    fi
else
    echo "Error: dist/ folder not found. Build may have failed."
    exit 1
fi
echo ""

# Summary
echo "========================================"
echo "Build Complete!"
echo "========================================"
echo ""
echo "Build output location: dist/"
echo ""
echo "Deployment options:"
echo "1. Static Hosting (Vercel, Netlify, etc.)"
echo "   - Upload dist/ folder or connect repository"
echo "   - Set build command: npm run build"
echo "   - Set output directory: dist"
echo ""
echo "2. Traditional Web Server (Nginx/Apache)"
echo "   - Copy contents of dist/ to web server root"
echo "   - Configure server to serve index.html for all routes (SPA)"
echo ""
echo "3. CDN/Cloud Storage (AWS S3, Cloudflare Pages, etc.)"
echo "   - Upload dist/ folder contents"
echo "   - Configure for SPA routing"
echo ""
echo "For detailed instructions, see DEPLOYMENT_GUIDE.md"
echo ""

