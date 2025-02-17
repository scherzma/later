# Modern PHP Project Setup

## Project Structure
```
project/
├── docker/
│   ├── php/
│   │   └── Dockerfile
│   ├── nginx/
│   │   └── default.conf
│   └── mysql/
│       └── init.sql
├── docker-compose.yml
├── backend/
│   ├── src/
│   └── composer.json
├── frontend/
│   ├── src/
│   └── package.json
└── README.md
```

## Docker Configuration

### docker-compose.yml
```yaml
version: '3.8'

services:
  frontend:
    build:
      context: ./frontend
      dockerfile: ../docker/node/Dockerfile
    ports:
      - "5173:5173"
    volumes:
      - ./frontend:/app
      - /app/node_modules
    depends_on:
      - backend

  backend:
    build:
      context: ./backend
      dockerfile: ../docker/php/Dockerfile
    volumes:
      - ./backend:/var/www/html
    depends_on:
      - db
      - redis

  nginx:
    image: nginx:alpine
    ports:
      - "8000:80"
    volumes:
      - ./backend:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - backend

  db:
    image: mariadb:10.6
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: project_db
      MYSQL_USER: project_user
      MYSQL_PASSWORD: project_pass
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
      - ./docker/mysql/init.sql:/docker-entrypoint-initdb.d/init.sql

  redis:
    image: redis:alpine
    ports:
      - "6379:6379"

volumes:
  mysql_data:
```

### docker/php/Dockerfile
```dockerfile
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy existing application directory
COPY . .

# Install dependencies
RUN composer install

# Change ownership of our applications
RUN chown -R www-data:www-data /var/www/html
```

### docker/nginx/default.conf
```nginx
server {
    listen 80;
    index index.php index.html;
    server_name localhost;
    root /var/www/html/public;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass backend:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }
}
```

## Setup Commands

### First Time Setup
```bash
# Clone the repository
git clone <your-repo-url>
cd <project-directory>

# Start all services
docker-compose up -d

# Install backend dependencies
docker-compose exec backend composer install

# Install frontend dependencies
docker-compose exec frontend npm install

# Run database migrations
docker-compose exec backend php migrate.php

# Generate app key (if needed)
docker-compose exec backend php generate-key.php
```

### Daily Development Commands
```bash
# Start the development environment
docker-compose up -d

# Stop the development environment
docker-compose down

# View logs
docker-compose logs -f

# Access PHP container
docker-compose exec backend bash

# Access Database
docker-compose exec db mariadb -u project_user -p project_db

# Run backend tests
docker-compose exec backend vendor/bin/phpunit

# Run frontend tests
docker-compose exec frontend npm test

# Build frontend for production
docker-compose exec frontend npm run build
```

### Database Commands
```bash
# Create a database backup
docker-compose exec db mysqldump -u root -p project_db > backup.sql

# Restore from backup
docker-compose exec -T db mysql -u root -p project_db < backup.sql
```

## Development URLs
- Frontend: http://localhost:5173
- Backend API: http://localhost:8000
- Database: localhost:3306
- Redis: localhost:6379

## Common Issues & Solutions

### Permission Issues
If you encounter permission issues, run:
```bash
docker-compose exec backend chown -R www-data:www-data /var/www/html
```

### Container Won't Start
Check logs with:
```bash
docker-compose logs [service_name]
```

### Database Connection Issues
Ensure the database has initialized fully (can take a few seconds after container starts). Check connection settings in your .env file match the docker-compose.yml settings.