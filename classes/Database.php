<?php
/**
 * JHUB AFRICA PROJECT TRACKER
 * Database Connection and Management Class
 * 
 * This class provides a singleton pattern for database connections,
 * query execution, transaction management, and database utilities.
 * 
 * @author JHUB AFRICA Development Team
 * @version 1.0
 * @since 2024
 */

// Prevent direct access
if (!defined('JHUB_ACCESS')) {
    die('Direct access not permitted');
}

/**
 * Database Class
 * 
 * Singleton class for managing database connections and operations
 */
class Database {
    
    /** @var Database|null Singleton instance */
    private static $instance = null;
    
    /** @var PDO Database connection */
    private $connection;
    
    /** @var array Query statistics */
    private $queryStats = [
        'total_queries' => 0,
        'select_queries' => 0,
        'insert_queries' => 0,
        'update_queries' => 0,
        'delete_queries' => 0,
        'total_time' => 0
    ];
    
    /** @var array Transaction stack */
    private $transactionLevel = 0;
    
    /** @var bool Debug mode flag */
    private $debugMode;
    
    /**
     * Private constructor - Singleton pattern
     * 
     * @throws Exception If database connection fails
     */
    private function __construct() {
        $this->debugMode = defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE;
        $this->connect();
    }
    
    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}
    
    /**
     * Prevent unserializing of the instance
     */
    public function __wakeup() {}
    
    /**
     * Get singleton instance
     * 
     * @return Database Database instance
     * @throws Exception If initialization fails
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Establish database connection
     * 
     * @throws Exception If connection fails
     */
    private function connect() {
        try {
            $dsn = DatabaseConfig::getDSN();
            $options = DatabaseConfig::getOptions();
            
            $this->connection = new PDO(
                $dsn,
                DatabaseConfig::DB_USER,
                DatabaseConfig::DB_PASS,
                $options
            );
            
            // Set additional options
            $this->connection->exec("SET time_zone = '+03:00'"); // East Africa Time
            $this->connection->exec("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
            
            if ($this->debugMode) {
                logActivity('INFO', 'Database connection established successfully');
            }
            
        } catch (PDOException $e) {
            $errorMessage = "Database connection failed: " . $e->getMessage();
            logActivity('CRITICAL', $errorMessage);
            throw new Exception("Database connection failed. Please check configuration.");
        }
    }
    
    /**
     * Get PDO connection instance
     * 
     * @return PDO Database connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Prepare a SQL statement
     * 
     * @param string $sql SQL statement
     * @return PDOStatement|false Prepared statement or false on failure
     * @throws Exception If preparation fails
     */
    public function prepare($sql) {
        try {
            $startTime = microtime(true);
            $statement = $this->connection->prepare($sql);
            
            if (!$statement) {
                throw new Exception("Failed to prepare statement: " . implode(' ', $this->connection->errorInfo()));
            }
            
            // Track query statistics
            $this->updateQueryStats($sql, microtime(true) - $startTime);
            
            if ($this->debugMode) {
                logActivity('DEBUG', "SQL prepared: $sql");
            }
            
            return $statement;
            
        } catch (PDOException $e) {
            logActivity('ERROR', "SQL prepare failed: {$e->getMessage()} | SQL: $sql");
            throw new Exception("Database query preparation failed: " . $e->getMessage());
        }
    }
    
    /**
     * Execute a prepared statement
     * 
     * @param PDOStatement $statement Prepared statement
     * @param array $params Parameters to bind
     * @return bool True on success
     * @throws Exception If execution fails
     */
    public function execute($statement, $params = []) {
        try {
            $startTime = microtime(true);
            $result = $statement->execute($params);
            
            if (!$result) {
                throw new Exception("Statement execution failed: " . implode(' ', $statement->errorInfo()));
            }
            
            $executionTime = microtime(true) - $startTime;
            $this->queryStats['total_time'] += $executionTime;
            
            if ($this->debugMode) {
                $paramString = empty($params) ? 'no params' : 'params: ' . json_encode($params);
                logActivity('DEBUG', "SQL executed in {$executionTime}s ($paramString)");
            }
            
            return $result;
            
        } catch (PDOException $e) {
            logActivity('ERROR', "SQL execution failed: {$e->getMessage()} | Params: " . json_encode($params));
            throw new Exception("Database query execution failed: " . $e->getMessage());
        }
    }
    
    /**
     * Prepare and execute a statement in one call
     * 
     * @param string $sql SQL statement
     * @param array $params Parameters to bind
     * @return PDOStatement Executed statement
     * @throws Exception If preparation or execution fails
     */
    public function query($sql, $params = []) {
        $statement = $this->prepare($sql);
        $this->execute($statement, $params);
        return $statement;
    }
    
    /**
     * Fetch a single row
     * 
     * @param string $sql SQL statement
     * @param array $params Parameters to bind
     * @param int $fetchMode PDO fetch mode
     * @return mixed Single row or false if no results
     * @throws Exception If query fails
     */
    public function fetchOne($sql, $params = [], $fetchMode = PDO::FETCH_ASSOC) {
        $statement = $this->query($sql, $params);
        return $statement->fetch($fetchMode);
    }
    
    /**
     * Fetch all rows
     * 
     * @param string $sql SQL statement
     * @param array $params Parameters to bind
     * @param int $fetchMode PDO fetch mode
     * @return array Array of rows
     * @throws Exception If query fails
     */
    public function fetchAll($sql, $params = [], $fetchMode = PDO::FETCH_ASSOC) {
        $statement = $this->query($sql, $params);
        return $statement->fetchAll($fetchMode);
    }
    
    /**
     * Fetch a single column value
     * 
     * @param string $sql SQL statement
     * @param array $params Parameters to bind
     * @param int $column Column number (0-indexed)
     * @return mixed Column value or false if no results
     * @throws Exception If query fails
     */
    public function fetchColumn($sql, $params = [], $column = 0) {
        $statement = $this->query($sql, $params);
        return $statement->fetchColumn($column);
    }
    
    /**
     * Get the last inserted ID
     * 
     * @param string|null $name Sequence name (for PostgreSQL)
     * @return string Last insert ID
     */
    public function lastInsertId($name = null) {
        return $this->connection->lastInsertId($name);
    }
    
    /**
     * Get the number of affected rows
     * 
     * @param PDOStatement $statement Executed statement
     * @return int Number of affected rows
     */
    public function rowCount($statement) {
        return $statement->rowCount();
    }
    
    /**
     * Begin a database transaction
     * 
     * @return bool True on success
     * @throws Exception If transaction fails to begin
     */
    public function beginTransaction() {
        try {
            if ($this->transactionLevel === 0) {
                $result = $this->connection->beginTransaction();
                if ($this->debugMode) {
                    logActivity('DEBUG', 'Database transaction started');
                }
            } else {
                // Nested transaction - use savepoint
                $savepoint = "sp_level_{$this->transactionLevel}";
                $this->connection->exec("SAVEPOINT $savepoint");
                $result = true;
                if ($this->debugMode) {
                    logActivity('DEBUG', "Database savepoint created: $savepoint");
                }
            }
            
            $this->transactionLevel++;
            return $result;
            
        } catch (PDOException $e) {
            logActivity('ERROR', "Failed to begin transaction: " . $e->getMessage());
            throw new Exception("Failed to begin database transaction");
        }
    }
    
    /**
     * Commit a database transaction
     * 
     * @return bool True on success
     * @throws Exception If commit fails
     */
    public function commit() {
        try {
            if ($this->transactionLevel === 0) {
                throw new Exception("No active transaction to commit");
            }
            
            $this->transactionLevel--;
            
            if ($this->transactionLevel === 0) {
                $result = $this->connection->commit();
                if ($this->debugMode) {
                    logActivity('DEBUG', 'Database transaction committed');
                }
            } else {
                // Release savepoint
                $savepoint = "sp_level_{$this->transactionLevel}";
                $this->connection->exec("RELEASE SAVEPOINT $savepoint");
                $result = true;
                if ($this->debugMode) {
                    logActivity('DEBUG', "Database savepoint released: $savepoint");
                }
            }
            
            return $result;
            
        } catch (PDOException $e) {
            logActivity('ERROR', "Failed to commit transaction: " . $e->getMessage());
            throw new Exception("Failed to commit database transaction");
        }
    }
    
    /**
     * Rollback a database transaction
     * 
     * @return bool True on success
     * @throws Exception If rollback fails
     */
    public function rollback() {
        try {
            if ($this->transactionLevel === 0) {
                throw new Exception("No active transaction to rollback");
            }
            
            $this->transactionLevel--;
            
            if ($this->transactionLevel === 0) {
                $result = $this->connection->rollback();
                if ($this->debugMode) {
                    logActivity('DEBUG', 'Database transaction rolled back');
                }
            } else {
                // Rollback to savepoint
                $savepoint = "sp_level_{$this->transactionLevel}";
                $this->connection->exec("ROLLBACK TO SAVEPOINT $savepoint");
                $result = true;
                if ($this->debugMode) {
                    logActivity('DEBUG', "Database rolled back to savepoint: $savepoint");
                }
            }
            
            return $result;
            
        } catch (PDOException $e) {
            logActivity('ERROR', "Failed to rollback transaction: " . $e->getMessage());
            throw new Exception("Failed to rollback database transaction");
        }
    }
    
    /**
     * Check if currently in a transaction
     * 
     * @return bool True if in transaction
     */
    public function inTransaction() {
        return $this->transactionLevel > 0;
    }
    
    /**
     * Execute a callback within a transaction
     * 
     * @param callable $callback Callback function to execute
     * @return mixed Callback return value
     * @throws Exception If transaction or callback fails
     */
    public function transaction($callback) {
        if (!is_callable($callback)) {
            throw new Exception("Transaction callback must be callable");
        }
        
        $this->beginTransaction();
        
        try {
            $result = call_user_func($callback, $this);
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Update query statistics
     * 
     * @param string $sql SQL statement
     * @param float $executionTime Execution time in seconds
     */
    private function updateQueryStats($sql, $executionTime) {
        $this->queryStats['total_queries']++;
        $this->queryStats['total_time'] += $executionTime;
        
        // Categorize query type
        $sql = strtoupper(trim($sql));
        if (strpos($sql, 'SELECT') === 0) {
            $this->queryStats['select_queries']++;
        } elseif (strpos($sql, 'INSERT') === 0) {
            $this->queryStats['insert_queries']++;
        } elseif (strpos($sql, 'UPDATE') === 0) {
            $this->queryStats['update_queries']++;
        } elseif (strpos($sql, 'DELETE') === 0) {
            $this->queryStats['delete_queries']++;
        }
    }
    
    /**
     * Get query statistics
     * 
     * @return array Query statistics
     */
    public function getQueryStats() {
        return $this->queryStats;
    }
    
    /**
     * Reset query statistics
     */
    public function resetQueryStats() {
        $this->queryStats = [
            'total_queries' => 0,
            'select_queries' => 0,
            'insert_queries' => 0,
            'update_queries' => 0,
            'delete_queries' => 0,
            'total_time' => 0
        ];
    }
    
    /**
     * Test database connection
     * 
     * @return bool True if connection is valid
     */
    public function testConnection() {
        try {
            $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get database server information
     * 
     * @return array Server information
     */
    public function getServerInfo() {
        try {
            return [
                'version' => $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION),
                'connection_status' => $this->connection->getAttribute(PDO::ATTR_CONNECTION_STATUS),
                'client_version' => $this->connection->getAttribute(PDO::ATTR_CLIENT_VERSION),
                'server_info' => $this->connection->getAttribute(PDO::ATTR_SERVER_INFO)
            ];
        } catch (PDOException $e) {
            return ['error' => 'Unable to retrieve server information'];
        }
    }
    
    /**
     * Execute a raw SQL statement (use with caution)
     * 
     * @param string $sql SQL statement
     * @return int|false Number of affected rows or false on failure
     * @throws Exception If execution fails
     */
    public function exec($sql) {
        try {
            $startTime = microtime(true);
            $result = $this->connection->exec($sql);
            
            $this->updateQueryStats($sql, microtime(true) - $startTime);
            
            if ($this->debugMode) {
                logActivity('DEBUG', "Raw SQL executed: $sql");
            }
            
            return $result;
            
        } catch (PDOException $e) {
            logActivity('ERROR', "Raw SQL execution failed: {$e->getMessage()} | SQL: $sql");
            throw new Exception("Database execution failed: " . $e->getMessage());
        }
    }
    
    /**
     * Ping database connection and reconnect if necessary
     * 
     * @return bool True if connection is active
     */
    public function ping() {
        try {
            $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            // Attempt to reconnect
            try {
                $this->connect();
                return true;
            } catch (Exception $reconnectException) {
                logActivity('ERROR', "Database reconnection failed: " . $reconnectException->getMessage());
                return false;
            }
        }
    }
    
    /**
     * Close database connection
     */
    public function close() {
        if ($this->connection) {
            $this->connection = null;
            if ($this->debugMode) {
                logActivity('INFO', 'Database connection closed');
            }
        }
    }
    
    /**
     * Destructor - Close connection when object is destroyed
     */
    public function __destruct() {
        $this->close();
    }
    
    /**
     * Quote a string for use in SQL
     * 
     * @param string $string String to quote
     * @param int $parameterType Parameter type
     * @return string Quoted string
     */
    public function quote($string, $parameterType = PDO::PARAM_STR) {
        return $this->connection->quote($string, $parameterType);
    }
    
    /**
     * Get table information
     * 
     * @param string $tableName Table name
     * @return array Table information
     */
    public function getTableInfo($tableName) {
        try {
            $sql = "SHOW CREATE TABLE " . $this->quote($tableName);
            $result = $this->fetchOne($sql);
            return $result;
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Check if table exists
     * 
     * @param string $tableName Table name
     * @return bool True if table exists
     */
    public function tableExists($tableName) {
        try {
            $sql = "SHOW TABLES LIKE ?";
            $result = $this->fetchOne($sql, [$tableName]);
            return !empty($result);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get database performance statistics
     * 
     * @return array Performance statistics
     */
    public function getPerformanceStats() {
        $stats = $this->getQueryStats();
        $stats['average_query_time'] = $stats['total_queries'] > 0 
            ? round($stats['total_time'] / $stats['total_queries'], 4) 
            : 0;
        $stats['queries_per_second'] = $stats['total_time'] > 0 
            ? round($stats['total_queries'] / $stats['total_time'], 2) 
            : 0;
        
        return $stats;
    }
}
