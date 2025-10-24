<?php
//require_once 'Database.php';



class Game
{
    public int $id = 0;
    public int $lastId = 0;

    public string $nome = "";
    public string $provedor = "";
    public GameType $type;
    public string $demo = "";
    public string $url = "";
    public string $image = "";
    public ?\DateTime $ultimaData = null;

    public array $provedores = [
        "Pg" => "Pg Games",
        "PP" => "Pragmatic",
        "PP Live" => "Pragmatic Live",
        "Tada" => "Tada Gaming",
        "Ez" => "Ezugi",
        "PT" => "Paytech",
        "Evo" => "Evolution",
        "PPK" => "Popok Gaming",
        "NL" => "No Limite",
        "HC" => "Hacsaw",
        "GG" => "Global Gaming",
        "EP" => "Endorphina",
        "Sb" => "Stribe",
        "RT" => "Red Tiger",
        "Btg" => "Big Time Gaming",
        "Net" => "NetEnd",
        "??" => "Outros"
    ];

    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
        $this->type = GameType::Slot;
    }
    
    public function createTable(): void
    {
        $this->db->getPdo()->exec("
            CREATE TABLE IF NOT EXISTS games (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                lastId INTEGER DEFAULT 0,
                nome TEXT NOT NULL,
                provedor TEXT,
                type INTEGER DEFAULT 0,
                demo TEXT,
                url TEXT,
                image TEXT,
                ultimaData TEXT
            )
        ");
    }
    
    

    public function create(): bool
    {
        $this->db->query("
            INSERT INTO games (lastId, nome, provedor, type, demo, url, image, ultimaData)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $this->lastId,
                $this->nome,
                $this->provedor,
                $this->type->value,
                $this->demo,
                $this->url,
                $this->image,
                $this->ultimaData?->format('Y-m-d H:i:s')
            ]
        );
        $this->id = (int)$this->db->getPdo()->lastInsertId();
        return true;
    }

    public function update(): bool
    {
        if ($this->id <= 0) return false;
        $this->db->query("
            UPDATE games SET lastId=?, nome=?, provedor=?, type=?, demo=?, url=?, image=?, ultimaData=? WHERE id=?",
            [
                $this->lastId,
                $this->nome,
                $this->provedor,
                $this->type->value,
                $this->demo,
                $this->url,
                $this->image,
                $this->ultimaData?->format('Y-m-d H:i:s'),
                $this->id
            ]
        );
        return true;
    }

    public function delete(): bool
    {
        if ($this->id <= 0) return false;
        $this->db->query("DELETE FROM games WHERE id=?", [$this->id]);
        return true;
    }

    public function read(int $id): ?Game
    {
        $stmt = $this->db->query("SELECT * FROM games WHERE id=?", [$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $this->id = (int)$data['id'];
            $this->lastId = (int)$data['lastId'];
            $this->nome = $data['nome'];
            $this->provedor = $data['provedor'];
            $this->type = GameType::from((int)$data['type']);
            $this->demo = $data['demo'];
            $this->url = $data['url'];
            $this->image = $data['image'];
            $this->ultimaData = $data['ultimaData'] ? new \DateTime($data['ultimaData']) : null;
            return $this;
        }
        return null;
    }

    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM games ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
