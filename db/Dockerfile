# Use the official MariaDB image from Docker Hub
FROM mariadb:latest

# Set environment variables for MariaDB
ENV MARIADB_ROOT_PASSWORD=rootpass
ENV MARIADB_DATABASE=todo_db
ENV MARIADB_USER=todo_user
ENV MARIADB_PASSWORD=todo_pass

# Copy the initial SQL script into the container
COPY init.sql /docker-entrypoint-initdb.d/

# Expose the default MariaDB port (optional, for documentation)
EXPOSE 3306