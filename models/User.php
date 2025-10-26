<?php

class User
{
    public ?int $id;
    public string $nome;
    public string $email;
    public string $senha;
    public string $criado_em;
    
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
        
    }

    // Método para preencher dados manualmente
    public function create(string $nome, string $email, string $senha, ?int $id = null, ?string $criado_em = null)
    {
        $this->id = $id;
        $this->nome = $nome;
        $this->email = $email;
        $this->senha = password_hash($senha, PASSWORD_DEFAULT);
        $this->criado_em = $criado_em ?? date('Y-m-d H:i:s');
    }

    // Método para criar a partir de um array (por exemplo, do banco)
    public static function createFromArray(array $data): User
    {
        $user = new User();
        $user->id = $data['id'] ?? null;
        $user->nome = $data['nome'] ?? '';
        $user->email = $data['email'] ?? '';
        $user->senha = $data['senha'] ?? ''; // normalmente já está hash
        $user->criado_em = $data['criado_em'] ?? date('Y-m-d H:i:s');
        return $user;
    }

    public function __toString(): string
    {
        return json_encode([
            'id' => $this->id,
            'nome' => $this->nome,
            'email' => $this->email,
            'criado_em' => $this->criado_em
        ], JSON_PRETTY_PRINT);
    }
    
    /**/
    public function save(): bool
    {
        if ($this->id > 0) {
return update();
        } else {
            insert();
        }
    }
    
    public function insert(): bool
    {
        
            $insert = (bool) $this->db->query(
                "INSERT INTO users (nome, email, senha, criado_em) VALUES (?, ?, ?, ?)",
                [$this->nome, $this->email, $this->senha, $user->criado_em]
            );
            
            $this->id = (int)$this->db->getPdo()->lastInsertId();
   
            return true;
        
    }
    
    public function update(): bool
    {
        if ($this->id > 0) {
            // 🔧 Corrigido: UPDATE usa SET, não VALUES
            return (bool) $this->db->query(
                "UPDATE users SET nome = ?, email = ?, senha = ?, criado_em = ? WHERE id = ?",
                [$this->nome, $this->email, $this->senha, $this->criado_em, $this->id]
            );
        } 
        return false;
    }
    
    
    
    
    
    public function findByEmail(string $email): ?User
    {
        $stmt = $this->db->query("SELECT * FROM users WHERE email = ?", [$email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? User::createFromArray($data) : null;
    }

    public function login($pass): ?bool
    {
        $user = $this->findByEmail($this->email);
        if ($user && password_verify($this->email, $pass)) {
            return true;
        }
        return false;
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM users ORDER BY id DESC");
        $users = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = User::createFromArray($row);
        }

        return $users;
    }
    
    
}
