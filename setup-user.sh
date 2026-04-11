#!/bin/bash
# Setup script to configure Docker user mapping

echo "Docker User Mapping Setup"
echo "========================="
echo ""

# Get current user ID and group ID
USER_ID=$(id -u)
GROUP_ID=$(id -g)

echo "Detected host user:"
echo "  USER_ID:  $USER_ID"
echo "  GROUP_ID: $GROUP_ID"
echo ""

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cat > .env << EOL
# Docker User Mapping
# These values match your host user to avoid permission issues
USER_ID=$USER_ID
GROUP_ID=$GROUP_ID
EOL
    echo "✓ .env file created"
else
    echo "⚠ .env file already exists"
    echo "  Current values:"
    grep -E "USER_ID|GROUP_ID" .env || echo "  (no user mapping configured)"
    echo ""
    read -p "Do you want to update it? (y/N): " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        # Update or add USER_ID and GROUP_ID
        if grep -q "USER_ID=" .env; then
            sed -i "s/USER_ID=.*/USER_ID=$USER_ID/" .env
        else
            echo "USER_ID=$USER_ID" >> .env
        fi
        
        if grep -q "GROUP_ID=" .env; then
            sed -i "s/GROUP_ID=.*/GROUP_ID=$GROUP_ID/" .env
        else
            echo "GROUP_ID=$GROUP_ID" >> .env
        fi
        echo "✓ .env file updated"
    else
        echo "Skipped updating .env"
    fi
fi

echo ""
echo "Next steps:"
echo "1. Rebuild containers: make up"
echo "2. Files created in the container will now match your user permissions"
