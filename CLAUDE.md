# Development Guidelines

## Commands
- **React Frontend:**
  - Start dev server: `cd react_todo_frontend && npm start`
  - Build for production: `cd react_todo_frontend && npm run build`
  - Run tests: `cd react_todo_frontend && npm test`
  - Run single test: `cd react_todo_frontend && npm test -- -t "test name"`

- **PHP Backend:**
  - Start containers: `docker-compose up -d`
  - Rebuild containers: `docker-compose build`
  - View logs: `docker-compose logs`
  - Run tests: `cd todo_api && ./run-tests.sh`
  - Run specific test: `cd todo_api && ./vendor/bin/phpunit tests/Unit/UserTest.php`
  - Run specific test method: `cd todo_api && ./vendor/bin/phpunit --filter testUserExists tests/Unit/UserTest.php`

## Coding Style
- **React (JavaScript):**
  - Use functional components with hooks
  - Import order: React, libraries, components, utils, styles
  - Props validation with PropTypes
  - Error handling with try/catch and toast notifications
  - Use camelCase for variables and functions
  - Form validation before submission

- **PHP Backend:**
  - PSR-4 autoloading standard
  - Class methods use camelCase
  - Use type hints where possible
  - Error handling through exceptions
  - Parameterized queries for database access
  - Security: Password hashing, JWT for authentication