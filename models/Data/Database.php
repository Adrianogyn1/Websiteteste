<?php

class Database
{
    // A propriedade estática armazena a instância em cache
    protected static ?Database $data = null; // Boa prática: inicializar com null e usar o tipo nullable

    private PDO $pdo;

    // Configurações do MySQL (Valores padrão de fallback ou para testes)
    // OBS: Em um construtor público, variáveis de instância devem ser tratadas
    // com cautela, pois cada 'new Database()' terá suas próprias cópias.
    public string $host = 'sql105.infinityfree.com';
    public string $dbname = 'if0_39810583_website';
    public string $user = 'if0_39810583';
    public string $pass = 'comsenha12';
    
    // Caminho do arquivo SQLite (Valor padrão)
    public string $sqlite_path = __DIR__ . '/app_database.sqlite';

    // Tempo de espera para SQLite em caso de bloqueio (em segundos)
    private int $sqlite_timeout = 5;

    // Construtor é mantido PÚBLICO conforme solicitado, permitindo 'new Database()'
    public function __construct()
    {
        // 1. Carrega as variáveis de ambiente
        $this->host        = getenv('DB_HOST') ?? $this->host;
        $this->dbname      = getenv('DB_NAME') ?? $this->dbname;
        $this->user        = getenv('DB_USER') ?? $this->user;
        $this->pass        = getenv('DB_PASS') ?? $this->pass;
        // Correção de path: garantindo que o getenv seja chamado corretamente
        $env_sqlite_path   = getenv('SQLITE_PATH');
        // Se a variável de ambiente existir, usa o path, senão usa o padrão
        $this->sqlite_path = $env_sqlite_path ? dirname(__DIR__, 1) . $env_sqlite_path : $this->sqlite_path;
        
        // Determina o driver a partir do ambiente (MySQL é o padrão se não for definido)
        $driver = getenv('DB_DRIVER') ?? 'mysql';

        // 2. Chama o método de conexão com o driver correto
        $this->connect($driver);
    }
    
    // Método estático para retornar a instância em cache (Factory com cache)
    public static function instance(): Database
    {
        // Uso de self:: em vez de static:: é comum, mas static:: é mais flexível 
        // em herança (Late Static Binding)
        if (!static::$data) {
            // Cria a instância APENAS se não existir
            static::$data = new Database(); 
        }
        
        return static::$data;
    }

    // BOA PRÁTICA: Impedir a clonagem da instância estática (mesmo com construtor público)
    private function __clone() {}

    // BOA PRÁTICA: Impedir a desserialização da instância estática
    public function __wakeup() {
        throw new \Exception("Cannot unserialize a static-cached object.");
    }
    
    // O resto da lógica de conexão permanece igual
    private function connect(string $driver): void
    {
        switch (strtolower($driver)) {
            case 'sqlite':
                $dsn = "sqlite:{$this->sqlite_path}";
                $this->pdo = new PDO($dsn);
                $this->pdo->setAttribute(PDO::ATTR_TIMEOUT, $this->sqlite_timeout);
                break;

            case 'mysql':
            default:
                $dsn = "mysql:host={$this->host};dbname={$this->dbname};port=3306;charset=utf8mb4";
                $this->pdo = new PDO($dsn, $this->user, $this->pass);
                break;
        }
        
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
    
    // Métodos de transação
    function beginTransaction(){
        return $this->pdo->beginTransaction();
    }
    
    function commit(){
        return $this->pdo->commit();
    }
    
    function inTransaction(){
        return $this->pdo->inTransaction();
    }
    
    function rollBack(){
        return $this->pdo->rollBack();
    }

    // Método auxiliar de consulta
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}