<?php
//require_once 'Database.php';

class LinkGame
{
    public int $id = 0;
    public int $carteiraId = 0;
    public int $gameId = 0;
    public string $url = "";

    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
        
    }
    
    public function createTable(): void
    {
        $this->db->getPdo()->exec("
            CREATE TABLE IF NOT EXISTS link_games (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                carteiraId INTEGER NOT NULL,
                gameId INTEGER NOT NULL,
                url TEXT NOT NULL
            )
        ");
    }
    
    

    public function create(): bool
    {
        $this->db->query("INSERT INTO link_games (carteiraId, gameId, url) VALUES (?, ?, ?)", [
            $this->carteiraId, $this->gameId, $this->url
        ]);
        $this->id = (int)$this->db->getPdo()->lastInsertId();
        return true;
    }

    public function update(): bool
    {
        if ($this->id <= 0) return false;
        $this->db->query("UPDATE link_games SET carteiraId=?, gameId=?, url=? WHERE id=?", [
            $this->carteiraId, $this->gameId, $this->url, $this->id
        ]);
        return true;
    }

    public function delete(): bool
    {
        if ($this->id <= 0) return false;
        $this->db->query("DELETE FROM link_games WHERE id=?", [$this->id]);
        return true;
    }

    public function read(int $id): ?LinkGame
    {
        $stmt = $this->db->query("SELECT * FROM link_games WHERE id=?", [$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $this->id = (int)$data['id'];
            $this->carteiraId = (int)$data['carteiraId'];
            $this->gameId = (int)$data['gameId'];
            $this->url = $data['url'];
            return $this;
        }
        return null;
    }

    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM link_games ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
