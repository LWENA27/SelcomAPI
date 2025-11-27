#!/bin/bash

# ========================================
# Git Commit Preparation for Interview
# ========================================

echo "🎯 Preparing your Selcom Checkout API for Git..."
echo ""

# Check if git is initialized
if [ ! -d .git ]; then
    echo "📁 Initializing Git repository..."
    git init
    echo "✅ Git initialized"
else
    echo "✅ Git repository already exists"
fi
echo ""

# Check .gitignore
echo "📋 Checking .gitignore..."
if grep -q "vendor/" .gitignore 2>/dev/null; then
    echo "✅ .gitignore looks good"
else
    echo "⚠️  Warning: Make sure .gitignore includes sensitive files"
fi
echo ""

# Check for uncommitted .env
if [ -f .env ]; then
    if git check-ignore .env > /dev/null 2>&1; then
        echo "✅ .env is properly ignored"
    else
        echo "⚠️  WARNING: .env should be in .gitignore!"
    fi
fi
echo ""

# Show what will be committed
echo "📦 Files to be committed:"
echo "----------------------------------------"
git status --short 2>/dev/null || echo "Run 'git add .' first"
echo ""

# Suggested commit message
echo "💬 Suggested commit message:"
echo "----------------------------------------"
cat <<'EOF'
feat: Complete Selcom Checkout API implementation

Implemented production-grade payment gateway API following Selcom's
Checkout API specification for technical interview demonstration.

Features:
- RESTful API with 5 endpoints (create, status, callback, cancel, list)
- Two-layer authentication (API Key + HMAC-SHA256 signature)
- Selcom-compliant order lifecycle management
- Financial data handling with integer-based amounts
- Idempotent order creation with composite unique keys
- Comprehensive request validation using Form Requests
- Production-ready error handling and logging

Security:
- HMAC signature verification with constant-time comparison
- API key authentication via Bearer token
- Mass assignment protection and SQL injection prevention
- Rate limiting middleware

Documentation:
- Complete API reference with curl examples
- Interview preparation guide with technical Q&A
- Postman collection with auto-signature generation
- Professional README with project overview

Testing:
- Automated bash test script covering all endpoints
- Postman tests with response validation
- Security tests for invalid credentials

Tech Stack: Laravel 12.40.2, PHP 8.1+, MySQL 8.0

Interview-ready with comprehensive documentation and working demo.
EOF
echo "----------------------------------------"
echo ""

# Instructions
echo "📝 Next steps:"
echo "----------------------------------------"
echo "1. Review changes: git status"
echo "2. Stage files:    git add ."
echo "3. Commit:         git commit -F commit_message.txt"
echo "4. (Optional) Create GitHub repo and push:"
echo "   git remote add origin https://github.com/yourusername/selcom-checkout-api.git"
echo "   git branch -M main"
echo "   git push -u origin main"
echo ""

# Create commit message file
cat <<'EOF' > commit_message.txt
feat: Complete Selcom Checkout API implementation

Implemented production-grade payment gateway API following Selcom's
Checkout API specification for technical interview demonstration.

Features:
- RESTful API with 5 endpoints (create, status, callback, cancel, list)
- Two-layer authentication (API Key + HMAC-SHA256 signature)
- Selcom-compliant order lifecycle management
- Financial data handling with integer-based amounts
- Idempotent order creation with composite unique keys
- Comprehensive request validation using Form Requests
- Production-ready error handling and logging

Security:
- HMAC signature verification with constant-time comparison
- API key authentication via Bearer token
- Mass assignment protection and SQL injection prevention
- Rate limiting middleware

Documentation:
- Complete API reference with curl examples
- Interview preparation guide with technical Q&A
- Postman collection with auto-signature generation
- Professional README with project overview

Testing:
- Automated bash test script covering all endpoints
- Postman tests with response validation
- Security tests for invalid credentials

Tech Stack: Laravel 12.40.2, PHP 8.1+, MySQL 8.0

Interview-ready with comprehensive documentation and working demo.
EOF

echo "✅ Commit message saved to commit_message.txt"
echo ""
echo "🎉 Ready to commit! Your project is interview-ready."
