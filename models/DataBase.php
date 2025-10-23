<?php
class Database
{
    private PDO $pdo;


    public function __construct(
        private string $host = 'sql105.infinityfree.com',
        private string $dbname = 'if0_39810583_website',
        private string $user = 'if0_39810583',
        private string $pass = 'comsenha12'
    ) {
        $this->connect();
    }

    private function connect(): void
    {
        $this->pdo = new PDO(
            "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
            $this->user,
            $this->pass
        );
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    // Métodos auxiliares genéricos
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
