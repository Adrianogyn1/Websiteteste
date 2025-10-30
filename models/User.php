<?php

class User extends DataObj
{
    protected string $tableName = 'users';
    
    //public int $id;
    public string $nome;
    public string $email;
    public string $senha='';
    public ?string $avatar='';
    public ?string $criado_em;

   public function __construct()
    {
        $db =  Database::instance();
        parent::__construct($db->getPdo());
       // $this->createTable();
    }

    // Método para preencher dados manualmente
    public function init(string $nome, string $email, string $senha, ?int $id = null, ?string $criado_em = null)
    {
        $this->id = 0;
        $this->nome = $nome;
        $this->email = $email;
        $this->senha = password_hash($senha, PASSWORD_DEFAULT);
        $this->criado_em = $criado_em ?? date('Y-m-d H:i:s');
    }

    
    public static function findByEmail(string $email): ?User
    {
   // 1. Usa o método estático 'query' da BaseModel, passando a conexão $db
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        
        // NOVIDADE: Usamos placeholder nomeado (:email) no lugar de '?' 
        // para maior clareza e padronização com a BaseModel.
        $bindings = [':email' => $email];

        // 2. Chama a query()
        $results = static::query( $sql, $bindings);

        // 3. Retorna o primeiro resultado (ou null se o array estiver vazio)
        return $results[0] ?? null;
    
    }

    public function login($pass): ?bool
{
    $user = static::findByEmail($this->email);
    
    if ($user && password_verify($pass, $user->senha)) {
        $this->populate($user->toArray());
        return true;
    } else {
        // O throw está aqui APENAS PARA TESTE.
        // Ele vai interromper a execução e mostrar a mensagem.
        // A linha "return false" NUNCA será alcançada se esta exceção for lançada.
       // throw new Exception("DEBUG: Login Falhou. User: ".json_encode($user)." Hash: ".$user->senha." Input Hash: ".password_hash($pass, PASSWORD_DEFAULT));
    }
    
    // Este código abaixo NUNCA será alcançado se o throw acima for executado.
    return false;
}
    
    public function cadastrar(): ?bool
    {
        $this->senha = password_hash($this->senha , PASSWORD_DEFAULT);
        $this->criado_em = $criado_em ?? date('Y-m-d H:i:s');
        
      //iniciana transacção  
        Database::instance()->beginTransaction();
        try
        {
       //salva o user
       $this->save();
       //cria a primeira carteira
       $carteira = new Carteira();
       $carteira->userId=$this->id;
       $carteira->nome='Principal';
       $carteira->save();
       //fecha a transacção 
       Database::instance()->commit();
       //retorna
       return true;
        }
    catch(ex $err){
        Database::instance()->rollBack();
    }
        return false;
    }

    
}
