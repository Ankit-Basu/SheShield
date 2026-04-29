<?php
class Database {
    // Parse database URL if available (for Render.com)
    private function getDbConfig() {
        if (getenv('DATABASE_URL')) {
            $db = parse_url(getenv('DATABASE_URL'));
            return [
                'host' => $db['host'],
                'db'   => ltrim($db['path'], '/'),
                'user' => $db['user'],
                'pass' => $db['pass'],
                'port' => $db['port'],
                'driver' => 'pgsql'
            ];
        }
        return [
            'host' => 'localhost',
            'db'   => 'sheshield',
            'user' => 'root',
            'pass' => '',
            'port' => 3306,
            'driver' => 'mysql'
        ];
    }
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $dbConfig = $this->getDbConfig();
            
            if ($dbConfig['driver'] === 'pgsql') {
                $dsn = "pgsql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['db']};";
            } else {
                $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['db']};charset=utf8mb4";
            }
            
            $this->conn = new PDO(
                $dsn,
                $dbConfig['user'],
                $dbConfig['pass'],
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false
                )
            );

            return $this->conn;
        } catch(PDOException $e) {
            error_log("Connection Error: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
}
?>
