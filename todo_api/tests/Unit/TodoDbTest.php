<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Mocks\MockDatabase;

class TodoDbTest extends TestCase
{
    /**
     * Test the singleton pattern of the database
     */
    public function testDatabaseSingleton()
    {
        // Get the DB instance twice
        $db1 = MockDatabase::gibInstanz();
        $db2 = MockDatabase::gibInstanz();
        
        // Ensure they are the same instance (singleton pattern)
        $this->assertSame($db1, $db2);
        
        // Check correct class
        $this->assertInstanceOf(MockDatabase::class, $db1);
    }
    
    /**
     * Test basic DB functionality with a simple query
     */
    public function testDatabaseConnection()
    {
        $db = MockDatabase::gibInstanz();
        
        // Run a simple query to check connection
        $result = $db->myQuery("SELECT 1 as test");
        
        // Should return true for successful query execution
        $this->assertTrue($result);
        
        // Get the result
        $rows = $db->gibZeilen();
        
        // Should get one row with value 1
        $this->assertCount(1, $rows);
        $this->assertEquals(1, $rows[0]['test']);
    }
    
    /**
     * Test parameterized queries for security
     */
    public function testParameterizedQueries()
    {
        $db = MockDatabase::gibInstanz();
        
        // Run a query with parameters
        $param = 'test_value';
        $result = $db->myQuery("SELECT ? as param", [$param]);
        
        // Should return true for successful query execution
        $this->assertTrue($result);
        
        // Get the result
        $rows = $db->gibZeilen();
        
        // Should get one row with the parameter
        $this->assertCount(1, $rows);
        $this->assertEquals($param, $rows[0]['param']);
    }
}