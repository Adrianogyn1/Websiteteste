<?php

class Database
{
    private PDO $pdo;

    private string $host = 'sql105.infinityfree.com';
    private string $dbname = 'if0_39810583_website';
    private string $user = 'if0_39810583';
    private string $pass = 'comsenha12';
 

    public function __construct()
{
   
   $ip_do_host = $_SERVER['SERVER_ADDR'];
   
   // Verifica se é o IP fixo PÚBLICO OU se é um IP PRIVADO (10.x.x.x é o mais comum)
   $ip_google_fixo = "35.209.27.45"; 
   $is_private_network = (substr($ip_do_host, 0, 3) === "10."); 
   
   $is_google_cloud = ($ip_do_host == $ip_google_fixo) || $is_private_network;
   
   if($is_google_cloud)
   {
      // Configuração de Desenvolvimento
      $this->host="localhost";
      $this->dbname="site";
      $this->user="adriano";
      $this->pass="12345";
   }
   
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
