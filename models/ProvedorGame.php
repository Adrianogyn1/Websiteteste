<?php
//require_once 'Database.php';

class ProvedorGame
{
    public int $id = 0;
    public string $nome = "";

    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
        
    }
    
    public function createTable(): void
{
    $this->db->getPdo()->exec("
        CREATE TABLE IF NOT EXISTS provedores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL
        )
    ");
}


    public function create(): bool
    {
        $this->db->query("INSERT INTO provedores (nome) VALUES (?)", [$this->nome]);
        $this->id = (int)$this->db->getPdo()->lastInsertId();
        return true;
    }

    public function update(): bool
    {
        if ($this->id <= 0) return false;
        $this->db->query("UPDATE provedores SET nome = ? WHERE id = ?", [$this->nome, $this->id]);
        return true;
    }

    public function delete(): bool
    {
        if ($this->id <= 0) return false;
        $this->db->query("DELETE FROM provedores WHERE id = ?", [$this->id]);
        return true;
    }

    public function read(int $id): ?ProvedorGame
    {
        $stmt = $this->db->query("SELECT * FROM provedores WHERE id = ?", [$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $this->id = (int)$data['id'];
            $this->nome = $data['nome'];
            return $this;
        }
        return null;
    }

    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM provedores ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
