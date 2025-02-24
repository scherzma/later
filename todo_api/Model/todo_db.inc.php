<?php
require_once "./Model/db_query.inc.php"; // Assumes DB_Query is defined here

class Todo_DB extends DB_Query {
    private static $db_objekt;

    public function __construct() {
        $this->db_server = "db";
        $this->db_name = "todo_db";
        $this->db_user = "todo_user";
        $this->db_passwort = "todo_pass";
        parent::__construct();
    }

    public function __destruct() {
        parent::__destruct();
    }

    public static function gibInstanz() {
        if (!isset(self::$db_objekt)) {
            self::$db_objekt = new Todo_DB();
        }
        return self::$db_objekt;
    }
}
?>