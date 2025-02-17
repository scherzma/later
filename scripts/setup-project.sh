#!/bin/bash
# setup-project.sh

# Generate secrets if they don't exist
if [ ! -f secrets/db_password.txt ]; then
    echo "Generating secrets..."
    ./generate-secrets.sh
fi

# Start Docker containers
docker-compose up -d

echo "Setup complete! Your secrets are stored in ./secrets/"
echo "Make sure to back up the secrets directory securely!"