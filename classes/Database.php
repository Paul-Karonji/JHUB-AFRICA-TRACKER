<?php
/**
 * classes/Database.php
 * Database Connection Manager - COMPLETELY FIXED
 * 
 * FIXES APPLIED:
 * 1. update() method uses ONLY positional parameters (?)
 * 2. Added getAll() method
 * 3. Proper error logging
 * 4. Transaction safety
 */

class Database {
    private static $instance = null;
    private $connection = null;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            global $db_options;
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $db_options);
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Database connection failed: " . $e->getMessage());
            } else {
                die("Database connection failed. Please try again later.");
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Execute a query and return results
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, $params);
            return false;
        }
    }
    
    /**
     * Insert record and return last insert ID
     * Uses NAMED parameters (:param)
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $stmt = $this->query($sql, $data);
        if ($stmt) {
            return $this->connection->lastInsertId();
        }
        return false;
    }
    
    /**
     * Update records - FIXED VERSION
     * Uses ONLY positional parameters (?) throughout
     * This prevents PDO error about mixing named and positional parameters
     *
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @param string $condition WHERE clause (e.g. "id = ?")
     * @param array $conditionParams Parameters for WHERE clause
     * @return bool Success/failure
     */
    public function update($table, $data, $condition, $conditionParams = []) {
        // Build SET clause with positional placeholders
        $setClause = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $setClause[] = "{$key} = ?";
            $values[] = $value;
        }
        
        $setClause = implode(', ', $setClause);
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$condition}";
        
        // Merge SET values with WHERE condition parameters
        $params = array_merge($values, $conditionParams);
        
        return $this->query($sql, $params);
    }
    
    /**
     * Delete records
     */
    public function delete($table, $condition, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$condition}";
        return $this->query($sql, $params);
    }
    
    /**
     * Get single record
     */
    public function getRow($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->fetch();
        }
        return null;
    }
    
    /**
     * Get multiple records
     */
    public function getRows($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }
    
    /**
     * Get all records - Alias for getRows()
     * Added because some code was calling Database::getAll()
     */
    public function getAll($sql, $params = []) {
        return $this->getRows($sql, $params);
    }
    
    /**
     * Count records
     */
    public function count($table, $condition = '', $params = []) {
        $sql = "SELECT COUNT(*) FROM {$table}";
        if (!empty($condition)) {
            $sql .= " WHERE {$condition}";
        }
        
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->fetchColumn();
        }
        return 0;
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollback();
    }
    
    /**
     * Log database errors to file
     */
    private function logError($error, $sql = '', $params = []) {
        $logMessage = date('Y-m-d H:i:s') . " - Database Error: {$error}\n";
        if (!empty($sql)) {
            $logMessage .= "SQL: {$sql}\n";
        }
        if (!empty($params)) {
            $logMessage .= "Params: " . json_encode($params) . "\n";
        }
        $logMessage .= "---\n";
        
        error_log($logMessage, 3, dirname(__DIR__) . '/logs/error.log');
    }
    
    /**
     * Prevent cloning of singleton
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization of singleton
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>