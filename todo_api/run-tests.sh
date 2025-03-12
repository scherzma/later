#!/bin/bash

# Run all tests
./vendor/bin/phpunit

# Run specific test file
# ./vendor/bin/phpunit tests/Unit/UserTest.php

# Run specific test method
# ./vendor/bin/phpunit --filter testUserExists tests/Unit/UserTest.php

# Run only unit tests
# ./vendor/bin/phpunit tests/Unit

# Run only integration tests
# ./vendor/bin/phpunit tests/Integration