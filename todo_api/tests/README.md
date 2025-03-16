# Todo API Testing

This directory contains tests for the Todo API application. The tests are organized into unit tests, functional tests, and integration tests.

## Test Structure

- `tests/Unit/`: Contains unit tests for individual classes
  - `ApiRequestTest.php`: Tests for API request sanitization functions
  - `RouterTest.php`: Tests for URL routing functionality
  - `EagerLoadingTest.php`: Tests for eager loading implementation in models

- `tests/Functional/`: Contains functional tests for API endpoints (currently skipped)
  - `ApiTest.php`: Tests for API endpoints (requires database)

- `tests/Integration/`: Contains integration tests for model interactions (requires database)
  - `ModelIntegrationTest.php`: Tests model interactions (requires database)

- `tests/Utils/`: Utility classes for testing
  - `ApiUtils.php`: Utility functions for API testing

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
- API request sanitization
- URL routing
- Eager loading implementation

### Eager Loading Tests

The `EagerLoadingTest.php` file contains tests that verify our eager loading implementation for models. These tests:

1. Verify that the User class has eager loading parameters in methods like `getTasks()`
2. Check that the Task class has cached properties for related objects
3. Verify that the getter methods check for preloaded objects before hitting the database
4. Test that direct setter methods exist for setting preloaded objects

These tests don't need a database connection as they use PHP's reflection API to examine the class structure.

### Functional Tests Note

The functional tests in `tests/Functional/ApiTest.php` are currently set up to be skipped when run in a standard environment due to header output issues in PHPUnit. To run these tests in a proper environment, you would need to:

1. Set up a test database
2. Remove the `markTestSkipped()` call in the test setup
3. Ensure the header output issue is addressed in your environment