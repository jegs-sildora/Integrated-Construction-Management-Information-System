<?php
/**
 * Graceful Degradation Database Driver (Microservices Migration)
 * 
 * This file replaces the legacy MySQL connection logic. To prevent fatal errors
 * in monolithic files that haven't been fully refactored, it provides a 
 * mock mysqli-compatible object.
 */

class MockMysqli {
    public $connect_error = null;
    public $insert_id = 0;
    public $affected_rows = 0;
    public $num_rows = 0;

    public function prepare($sql) { return new MockStmt(); }
    public function query($sql) { return new MockResult(); }
    public function set_charset($charset) { return true; }
    public function close() { return true; }
    public function ping() { return true; }
}

class MockStmt {
    public $error = null;
    public function bind_param(...$args) { return true; }
    public function execute() { return true; }
    public function get_result() { return new MockResult(); }
    public function fetch() { return null; }
    public function close() { return true; }
    public function bind_result(...$args) { return true; }
}

class MockResult {
    public $num_rows = 0;
    public function fetch_assoc() { return null; }
    public function fetch_all($mode) { return []; }
    public function free() { return true; }
}

// Global connection object for legacy code
$conn = new MockMysqli();

/**
 * Migration Note:
 * The system now uses microservices. New code should use core/ApiHelper.php
 * to fetch data from the API Gateway instead of using $conn.
 */
