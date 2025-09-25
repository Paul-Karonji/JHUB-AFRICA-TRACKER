<?php
// classes/Database.php
// Database Connection Manager

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
    
    // Execute a query and return results
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
    
    // Insert record and return last insert ID
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
    
    // Update records
    public function update($table, $data, $condition, $conditionParams = []) {
        $setClause = [];
        foreach ($data as $key => $value) {
            $setClause[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setClause);
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$condition}";
        
        $params = array_merge($data, $conditionParams);
        return $this->query($sql, $params);
    }
    
    // Delete records
    public function delete($table, $condition, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$condition}";
        return $this->query($sql, $params);
    }
    
    // Get single record
    public function getRow($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->fetch();
        }
        return null;
    }
    
    // Get multiple records
    public function getRows($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }
    
    // Count records
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
    
    // Begin transaction
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    // Commit transaction
    public function commit() {
        return $this->connection->commit();
    }
    
    // Rollback transaction
    public function rollback() {
        return $this->connection->rollback();
    }
    
    // Log database errors
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
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

?>