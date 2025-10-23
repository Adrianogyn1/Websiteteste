<?php
require_once 'User.php';
require_once 'Database.php';

class UserPdo
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
        
        $this->db->getPdo()->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                email VARCHAR(150) UNIQUE NOT NULL,
                senha VARCHAR(255) NOT NULL,
                criado_em DATETIME NOT NULL
            );
        ");
    }

    public function save(User $user): bool
    {
        return (bool) $this->db->query(
            "INSERT INTO users (nome, email, senha, criado_em) VALUES (?, ?, ?, ?)",
            [$user->nome, $user->email, $user->senha, $user->criado_em]
        );
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->db->query("SELECT * FROM users WHERE email = ?", [$email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? User::createFromArray($data) : null;
    }

    public function login(string $email, string $senha): ?User
    {
        $user = $this->findByEmail($email);
        if ($user && password_verify($senha, $user->senha)) {
            return $user;
        }
        return null;
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
