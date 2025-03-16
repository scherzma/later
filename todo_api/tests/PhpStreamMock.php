<?php

namespace Tests;

/**
 * Mock class for the php://input stream
 * This allows us to simulate the request body in tests
 */
class PhpStreamMock
{
    /**
     * Static data to be returned when reading from the stream
     */
    public static $mockData = '';
    
    /**
     * Current position in the stream
     */
    private $position = 0;
    
    /**
     * Stream context
     */
    public $context;
    
    /**
     * Open the stream
     */
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        return true;
    }
    
    /**
     * Read from the stream
     */
    public function stream_read($count)
    {
        $ret = substr(self::$mockData, $this->position, $count);
        $this->position += strlen($ret);
        return $ret;
    }
    
    /**
     * Write to the stream
     */
    public function stream_write($data)
    {
        return strlen($data);
    }
    
    /**
     * Tell the current position in the stream
     */
    public function stream_tell()
    {
        return $this->position;
    }
    
    /**
     * Check if we're at the end of the stream
     */
    public function stream_eof()
    {
        return $this->position >= strlen(self::$mockData);
    }
    
    /**
     * Seek to a position in the stream
     */
    public function stream_seek($offset, $whence)
    {
        switch ($whence) {
            case SEEK_SET:
                if ($offset < strlen(self::$mockData) && $offset >= 0) {
                    $this->position = $offset;
                    return true;
                }
                return false;
            case SEEK_CUR:
                if ($offset >= 0) {
                    $this->position += $offset;
                    return true;
                }
                return false;
            case SEEK_END:
                if (strlen(self::$mockData) + $offset >= 0) {
                    $this->position = strlen(self::$mockData) + $offset;
                    return true;
                }
                return false;
            default:
                return false;
        }
    }
    
    /**
     * Get stream stat
     */
    public function stream_stat()
    {
        return [];
    }
    
    /**
     * Get URL stat
     */
    public function url_stat($path, $flags)
    {
        return [];
    }
}