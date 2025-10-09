<?php
/**
 * classes/Database.php
 * Database Connection Manager - FIXED FOR ERROR #3
 * 
 * FIXES APPLIED:
 * 1. update() method now returns TRUE on success (even if 0 rows affected)
 * 2. delete() method also returns TRUE on success
 * 3. Proper boolean returns for all CRUD operations
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
     * Update records - FIXED FOR ERROR #3
     * Now returns TRUE on success, even if 0 rows were affected
     * This fixes the "Failed to update stage" error when updating to same value
     *
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @param string $condition WHERE clause (e.g. "id = ?")
     * @param array $conditionParams Parameters for WHERE clause
     * @return bool TRUE on success, FALSE on error
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
        
        // ✅ FIX: Return TRUE if query executed successfully, FALSE if error
        $stmt = $this->query($sql, $params);
        return ($stmt !== false);  // Returns TRUE even if 0 rows affected
    }
    
    /**
     * Delete records - FIXED (same as update)
     * @return bool TRUE on success, FALSE on error
     */
    public function delete($table, $condition, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$condition}";
        $stmt = $this->query($sql, $params);
        return ($stmt !== false);  // Returns TRUE even if 0 rows affected
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
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>