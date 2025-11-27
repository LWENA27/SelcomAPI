#!/bin/bash

echo "🚀 Ready to push to GitHub!"
echo ""
echo "Make sure you've created the repository on GitHub first:"
echo "1. Go to: https://github.com/new"
echo "2. Repository name: selcom-checkout-api"
echo "3. Make it PUBLIC"
echo "4. Don't initialize with README"
echo "5. Click 'Create repository'"
echo ""
read -p "Have you created the GitHub repository? (y/n): " created

if [ "$created" != "y" ]; then
    echo "Please create the repository first, then run this script again."
    exit 1
fi

echo ""
read -p "Enter your GitHub username: " username

if [ -z "$username" ]; then
    echo "Username cannot be empty!"
    exit 1
fi

echo ""
echo "Setting up remote..."
git remote remove origin 2>/dev/null
git remote add origin https://github.com/$username/selcom-checkout-api.git

echo ""
echo "Pushing to GitHub..."
git push -u origin main

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Successfully pushed to GitHub!"
    echo ""
    echo "🎉 Your repository is now live at:"
    echo "   https://github.com/$username/selcom-checkout-api"
    echo ""
    echo "📝 Next steps:"
    echo "1. Add topics/tags to your repo"
    echo "2. Share the link with your interviewer"
    echo "3. Review the code one more time"
    echo ""
else
    echo ""
    echo "❌ Push failed. Common issues:"
    echo "1. Check your GitHub username is correct"
    echo "2. You may need to use a Personal Access Token instead of password"
    echo "   Create one at: https://github.com/settings/tokens"
    echo "3. Make sure the repository exists on GitHub"
    echo ""
fi
