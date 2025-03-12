# Todo API Testing

This directory contains tests for the Todo API application. The tests are organized into unit tests and integration tests.

## Test Structure

- `tests/Unit/`: Contains unit tests for individual classes
- `tests/Integration/`: Contains integration tests for API endpoints
- `tests/Mocks/`: Contains mock classes used for testing
- `tests/bootstrap.php`: Sets up the test environment
- `phpunit.xml`: PHPUnit configuration

## Running Tests

Use the following commands to run the tests:

```bash
# Run all tests
./vendor/bin/phpunit

# Run unit tests only
./vendor/bin/phpunit tests/Unit

# Run integration tests only
./vendor/bin/phpunit tests/Integration

# Run a specific test file
./vendor/bin/phpunit tests/Unit/UserTest.php

# Run a specific test method
./vendor/bin/phpunit --filter testUserRoles tests/Unit/UserTest.php
```

Alternatively, you can use the provided shell script:

```bash
./run-tests.sh
```

## Mock Classes

To avoid dependencies on the database and external services, the tests use mock classes:

- `MockDatabase`: Simulates database operations
- `MockUser`: Simulates the User class
- `MockTask`: Simulates the Task class
- `MockTag`: Simulates the Tag class
- `MockJwt`: Simulates JWT operations

## Adding New Tests

1. Create a new test class in the appropriate directory (Unit or Integration)
2. Extend the `Tests\TestCase` class
3. Use mock classes as needed
4. Run the tests to ensure they pass

## Test Coverage

The tests cover the following areas:

- Database operations
- User management
- Task management
- Tag management
- JWT authentication