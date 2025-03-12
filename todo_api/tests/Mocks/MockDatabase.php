<?php

namespace Tests\Mocks;

/**
 * Mock Database for testing
 * This class mimics the behavior of Todo_DB without requiring an actual database connection
 */
class MockDatabase
{
    private static $instance = null;
    private $lastQuery = '';
    private $params = [];
    private $mockResults = [];
    
    private function __construct()
    {
        // Set up default mock results
        $this->setupDefaultMockResults();
    }
    
    public static function gibInstanz()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Setup default mock results for common queries
     */
    private function setupDefaultMockResults()
    {
        // Mock result for "SELECT 1 as test"
        $this->mockResults['SELECT 1 as test'] = [
            ['test' => 1]
        ];
        
        // Mock result for parameters
        $this->mockResults['SELECT ? as param'] = [
            ['param' => null] // Will be replaced with actual param
        ];
    }
    
    /**
     * Execute a query and store params
     */
    public function myQuery($query, $params = [])
    {
        $this->lastQuery = $query;
        $this->params = $params;
        
        // If we have a parameter and a mock result for this query that uses the parameter
        if (count($params) > 0 && isset($this->mockResults[$query])) {
            // For queries with a single parameter placeholder, replace the value
            if (strpos($query, '?') !== false && count($this->mockResults[$query]) > 0) {
                foreach ($this->mockResults[$query] as $key => $row) {
                    foreach ($row as $colName => $colValue) {
                        if ($colValue === null) {
                            $this->mockResults[$query][$key][$colName] = $params[0];
                        }
                    }
                }
            }
        }
        
        return true;
    }
    
    /**
     * Get mock query results
     */
    public function gibZeilen()
    {
        if (isset($this->mockResults[$this->lastQuery])) {
            return $this->mockResults[$this->lastQuery];
        }
        
        // Default empty result
        return [];
    }
    
    /**
     * Mock lastInsertID method
     */
    public function lastInsertID()
    {
        return 1; // Always return 1 for tests
    }
    
    /**
     * Set custom mock results for a specific query
     */
    public function setMockResults($query, $results)
    {
        $this->mockResults[$query] = $results;
    }
}