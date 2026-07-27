<?php
if (php_sapi_name() === 'cli') return;
if (defined('VERCEL_SESSION_LOADED')) return;
define('VERCEL_SESSION_LOADED', true);

class VercelSessionHandler implements SessionHandlerInterface
{
    private $mysqli;
    private $connected = false;
    private $tableCreated = false;

    private function connect()
    {
        if ($this->connected) return;
        $host = getenv('DB_HOST') ?: 'mysql-32225e83-mandadinheiroproloky-4897.i.aivencloud.com';
        $user = getenv('DB_USER') ?: 'avnadmin';
        $pass = getenv('DB_PASS') ?: 'AVNS_Hx-pbf22fhzULYbOUkR';
        $db   = getenv('DB_NAME') ?: 'defaultdb';
        $port = (int)(getenv('DB_PORT') ?: 18533);
        try {
            $this->mysqli = new mysqli();
            $this->mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
            $this->mysqli->options(MYSQLI_OPT_READ_TIMEOUT, 5);
            $this->mysqli->ssl_set(null, null, null, null, null);
            @$this->mysqli->real_connect($host, $user, $pass, $db, $port, null, MYSQLI_CLIENT_SSL);
            if ($this->mysqli->connect_errno) {
                error_log("VercelSession: connection failed: " . $this->mysqli->connect_error);
                return;
            }
            $this->connected = true;
            $this->ensureTable();
        } catch (Exception $e) {
            error_log("VercelSession: exception: " . $e->getMessage());
        }
    }

    private function ensureTable()
    {
        if ($this->tableCreated || !$this->connected) return;
        $sql = "CREATE TABLE IF NOT EXISTS sessions (
            id VARCHAR(128) NOT NULL PRIMARY KEY,
            data MEDIUMTEXT NOT NULL,
            expires INT(11) UNSIGNED NOT NULL,
            INDEX idx_expires (expires)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        if ($this->mysqli->query($sql)) {
            $this->tableCreated = true;
        } else {
            error_log("VercelSession: could not create sessions table: " . $this->mysqli->error);
        }
    }

    public function open($savePath, $sessionName)
    {
        $this->connect();
        return true;
    }

    public function close()
    {
        if ($this->mysqli) $this->mysqli->close();
        $this->connected = false;
        return true;
    }

    public function read($sessionId)
    {
        if (!$this->connected) return '';
        $stmt = $this->mysqli->prepare("SELECT data FROM sessions WHERE id = ? AND expires > ?");
        if (!$stmt) return '';
        $now = time();
        $stmt->bind_param("si", $sessionId, $now);
        $stmt->execute();
        $stmt->bind_result($data);
        $stmt->fetch();
        $stmt->close();
        return $data ?: '';
    }

    public function write($sessionId, $data)
    {
        if (!$this->connected) return false;
        $expires = time() + 86400;
        $stmt = $this->mysqli->prepare("REPLACE INTO sessions (id, data, expires) VALUES (?, ?, ?)");
        if (!$stmt) return false;
        $stmt->bind_param("ssi", $sessionId, $data, $expires);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function destroy($sessionId)
    {
        if (!$this->connected) return false;
        $stmt = $this->mysqli->prepare("DELETE FROM sessions WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("s", $sessionId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function gc($maxLifetime)
    {
        if (!$this->connected) return false;
        $expires = time();
        $stmt = $this->mysqli->prepare("DELETE FROM sessions WHERE expires < ?");
        if (!$stmt) return false;
        $stmt->bind_param("i", $expires);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}

$handler = new VercelSessionHandler();
session_set_save_handler($handler, true);
