#!/bin/bash
# generate-secrets.sh

# Create secrets directory if it doesn't exist
mkdir -p secrets

# Generate random passwords and save them to files
openssl rand -base64 32 > secrets/db_password.txt
openssl rand -base64 32 > secrets/redis_password.txt
openssl rand -base64 64 > secrets/jwt_secret.txt

# Set proper permissions
chmod 600 secrets/*

echo "Secrets generated successfully!"