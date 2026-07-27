<?php
class DbSessionHandler implements SessionHandlerInterface {
    private $db;
    public function open($savePath, $sessionName): bool {
        global $mysqli;
        if (!$mysqli) {
            require_once __DIR__ . '/../config.php';
            require_once __DIR__ . '/../admin/services/database.php';
        }
        $this->db = $mysqli;
        return true;
    }
    public function close(): bool {
        return true;
    }
    public function read($id): string {
        if (!$this->db) return '';
        $stmt = $this->db->prepare("SELECT data FROM sessions WHERE id = ?");
        if (!$stmt) return '';
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return $row['data'];
        }
        return '';
    }
    public function write($id, $data): bool {
        if (!$this->db) return false;
        $access = time();
        $stmt = $this->db->prepare("REPLACE INTO sessions (id, access, data) VALUES (?, ?, ?)");
        if (!$stmt) return false;
        $stmt->bind_param("sis", $id, $access, $data);
        return $stmt->execute();
    }
    public function destroy($id): bool {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("s", $id);
        return $stmt->execute();
    }
    public function gc($maxlifetime): int|false {
        if (!$this->db) return false;
        $old = time() - $maxlifetime;
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE access < ?");
        if (!$stmt) return false;
        $stmt->bind_param("i", $old);
        return $stmt->execute() ? 1 : false;
    }
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../admin/services/database.php';

$handler = new DbSessionHandler();
session_set_save_handler($handler, true);
