<?php
// database.php

// Error reporting for debugging
error_reporting(-1);
ini_set('display_errors', 1);

/**
 * Database class for connecting to Wikimedia Commons replica
 * Reads credentials from replica.my.cnf and connects to commonswiki.analytics.db.svc.wikimedia.cloud
 */
class Database {
    private static $instance = null;
    private $conn = null;
    private $credentials = null;

    private function __construct() {
        try {
            $this->loadCredentials();
            $this->connectToCommons();
        } catch (Exception $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed. Please try again later.');
        }
    }

    /**
     * Load credentials from replica.my.cnf
     */
    private function loadCredentials() {
        $paths = [
            // Typical Toolforge location
            '/data/project/' . (getenv('TOOL_NAME') ?: basename(getcwd())) . '/replica.my.cnf',
            // Alternative locations
            __DIR__ . '/replica.my.cnf',
            './replica.my.cnf',
        ];

        foreach ($paths as $path) {
            if (is_readable($path)) {
                $content = file_get_contents($path);
                if ($content !== false) {
                    $this->credentials = $this->parseReplicaCnf($content);
                    return;
                }
            }
        }

        // Fallback to environment variables
        $user = getenv('TOOL_REPLICA_USER');
        $password = getenv('TOOL_REPLICA_PASSWORD');
        
        if ($user && $password) {
            $this->credentials = [
                'user' => $user,
                'password' => $password,
                'disable-ssl' => false
            ];
            return;
        }

        throw new Exception('Could not find replica.my.cnf or environment variables for database credentials');
    }

    /**
     * Parse replica.my.cnf file
     */
    private function parseReplicaCnf($content) {
        $lines = explode("\n", $content);
        $inClientSection = false;
        $credentials = [];

        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines and comments
            if (empty($line) || $line[0] === '#' || $line[0] === ';') {
                continue;
            }

            // Check for [client] section
            if ($line === '[client]') {
                $inClientSection = true;
                continue;
            }

            // Check for other sections
            if (preg_match('/^\[.+\]$/', $line)) {
                $inClientSection = false;
                continue;
            }

            // Parse key=value pairs in [client] section
            if ($inClientSection && strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Handle boolean values
                if ($key === 'disable-ssl') {
                    $credentials[$key] = ($value === 'true' || $value === '1');
                } else {
                    $credentials[$key] = $value;
                }
            }
        }

        if (!isset($credentials['user']) || !isset($credentials['password'])) {
            throw new Exception('Invalid replica.my.cnf: missing user or password');
        }

        return $credentials;
    }

    /**
     * Connect to Wikimedia Commons replica database
     */
    private function connectToCommons() {
        $host = 'commonswiki.analytics.db.svc.wikimedia.cloud';
        $dbname = 'commonswiki_p';
        $port = 3306;

        $this->conn = new mysqli(
            $host,
            $this->credentials['user'],
            $this->credentials['password'],
            $dbname,
            $port
        );

        if ($this->conn->connect_error) {
            throw new RuntimeException("Connection failed: " . $this->conn->connect_error);
        }

        // Set charset for proper handling of Unicode
        if (!$this->conn->set_charset('utf8mb4')) {
            error_log('Failed to set charset utf8mb4');
        }

        // Optional: Set session to read-only (replicas are read-only anyway)
        $this->conn->query("SET SESSION TRANSACTION READ ONLY");

        error_log("Connected to Wikimedia Commons replica: {$host}/{$dbname}");
    }

    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Get mysqli connection
     */
    public function getConnection() {
        if ($this->conn === null) {
            throw new Exception('Database not connected');
        }
        return $this->conn;
    }

    /**
     * Execute a parameterized SELECT query
     * Returns array of associative arrays
     */
    public function executeQuery($sql, $params = []) {
        try {
            $startTime = microtime(true);
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->conn->error);
            }

            // Bind parameters if provided
            if (!empty($params)) {
                $types = $this->getParameterTypes($params);
                $stmt->bind_param($types, ...$params);
            }

            if (!$stmt->execute()) {
                $error = $stmt->error;
                $stmt->close();
                throw new Exception('Execute failed: ' . $error);
            }

            $result = $stmt->get_result();
            $rows = [];
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
            }

            $stmt->close();
            
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            error_log("Query executed: " . count($rows) . " rows, {$executionTime}ms");
            
            return $rows;

        } catch (Exception $e) {
            error_log('Query execution failed: ' . $e->getMessage() . ' SQL: ' . $sql);
            throw new Exception('Database query failed: ' . $e->getMessage());
        }
    }

    /**
     * Execute a non-SELECT query (INSERT, UPDATE, DELETE)
     * Returns number of affected rows
     */
    public function executeUpdate($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->conn->error);
            }

            // Bind parameters if provided
            if (!empty($params)) {
                $types = $this->getParameterTypes($params);
                $stmt->bind_param($types, ...$params);
            }

            if (!$stmt->execute()) {
                $error = $stmt->error;
                $stmt->close();
                throw new Exception('Execute failed: ' . $error);
            }

            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            
            return $affectedRows;

        } catch (Exception $e) {
            error_log('Update execution failed: ' . $e->getMessage() . ' SQL: ' . $sql);
            throw new Exception('Database update failed: ' . $e->getMessage());
        }
    }

    /**
     * Get parameter types string for mysqli bind_param
     */
    private function getParameterTypes($params) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_null($param)) {
                $types .= 's';
            } else {
                $types .= 's';
            }
        }
        return $types;
    }

    /**
     * Get the last insert ID
     */
    public function lastInsertId() {
        return $this->conn->insert_id;
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->conn->begin_transaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->conn->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->conn->rollback();
    }

    /**
     * Close database connection
     */
    public function close() {
        if ($this->conn) {
            $this->conn->close();
            $this->conn = null;
        }
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

    /**
     * Cleanup on destruction
     */
    public function __destruct() {
        $this->close();
    }
}

/**
 * Legacy function for backward compatibility
 * Get SQL connection to Wikimedia Commons replica
 * @return mysqli
 */
function getSqlConnection() {
    return Database::getInstance()->getConnection();
}

/**
 * Get database names from meta database (if needed)
 * This function connects to meta.web.db.svc.wikimedia.cloud to get database list
 */
function getDbNames() {
    // Create a separate connection to meta database for this specific query
    $db = Database::getInstance();
    $credentials = $db->credentials ?? [];
    
    $connection = new mysqli(
        'meta.web.db.svc.wikimedia.cloud',
        $credentials['user'] ?? getenv('TOOL_REPLICA_USER'),
        $credentials['password'] ?? getenv('TOOL_REPLICA_PASSWORD'),
        'meta_p'
    );

    if ($connection->connect_error) {
        throw new RuntimeException("Meta connection failed: " . $connection->connect_error);
    }

    $statement = $connection->prepare('SELECT dbname FROM wiki');
    if (!$statement) {
        $connection->close();
        throw new RuntimeException("Prepare failed: " . $connection->error);
    }

    $statement->execute();

    if ($statement->error) {
        $statement->close();
        $connection->close();
        throw new RuntimeException("Failed to retrieve data: " . $statement->error);
    }

    $databases = [];
    $result = $statement->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $databases[] = $row;
    }

    $result->close();
    $statement->close();
    $connection->close();

    return $databases;
}
?>