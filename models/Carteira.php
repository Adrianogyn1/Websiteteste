<?php
// Arquivo: Carteira.php

// Pressupõe que BaseModel, Database, IgnoreInDatabase e TransasaoType (Enum) estejam carregados.

// A classe Carteira deve herdar de BaseModel (ou CollectionModel) para usar o ORM
class Carteira extends BaseModel
{
    // Define a tabela (mantém a primeira letra maiúscula, mas o ORM deve lidar com isso)
    protected string $tableName = 'carteira'; // Alterado para o plural minúsculo (convenção)
    
    // --- PROPRIEDADES DE BANCO DE DADOS ---
    // (A ordem não importa, mas a tipagem sim)
    
    // Propriedades not null
    public string $nome = "";
    public int $PayerId = 0;
    
    // Propriedades nullable
    public ?string $meta = null; // Inicializar com null (padrão)
    public ?bool $useRelatorio = true;
    public ?int $userId = null;
    public ?string $url = null;
    public ?string $login = null;
    public ?string $senha = null;
    public ?bool $teste = false;
    public ?bool $selected = false;
    
    // Timestamps
    public ?string $created_at = null; // \DateTime é melhor, mas mantemos string para DB
    public ?string $update_at = null;
    
    // --- PROPRIEDADES SOMENTE LEITURA/CALCULADAS ---
    // Usar a propriedade na classe pai (ou definir o atributo #[IgnoreInDatabase])
    #[IgnoreInDatabase]
    public float $saldo = 0;
    

    // --- EVENTOS (Hooks) ---
    // Estes métodos são apenas chamados se definidos.
    protected function OnCreate(bool $sucess): bool
    {
        return true;
    }
    
    protected function OnUpdate(bool $sucess): bool
    {
        return true;
    }
    
    // --- CONSTRUTOR ---
    /** * Removemos o construtor manual.
     * O construtor da BaseModel cuidará de:
     * 1. Conexão PDO: $this->db = Database::instance()->getPdo();
     * 2. Definição da Tabela: (já está definida acima).
     */
    // public function __construct() { parent::__construct(); } // É implícito
    
    
    // --- MÉTODOS ESTÁTICOS DE CONSULTA ---

    /**
     * Retorna a Carteira selecionada para um usuário.
     */
    public static function getSelected(int $userId): ?self 
    {
        $carts =  static::allUser($userId); // Chama o método corrigido
        
        if (empty($carts)) {
            return null;
        }

        // 1. Tenta encontrar a selecionada
        foreach ($carts as $value) {
            if ($value->selected) {
                return $value;
            }
        }

        // 2. Retorna a primeira se nenhuma estiver explicitamente selecionada
        return $carts[0];
    }
    
    /**
     * Busca todas as carteiras e retorna um array de objetos Carteira.
     *
     * @return array<Carteira> Um array contendo instâncias da classe Carteira.
     */
    public static function allUser(int $userId): array
    {
        // Usa o método query estático da BaseModel
        $sql = 'SELECT * FROM carteiras WHERE PayerId = :id ORDER BY id DESC';
        /** @var array<Carteira> $result */
        $result = static::query($sql, [':id' => $userId]);
        
        // Calcula o saldo para cada carteira
        foreach ($result as $value) {
            $value->GetSaldo(); // Calcula o saldo e armazena em $value->saldo
        }
        return $result;
    }


    // --- MÉTODOS DE AÇÃO ---
    
    /**
     * Marca esta carteira como selecionada e desmarca as outras para o mesmo PayerId.
     */
    public function selectCarteira(): void 
    {
        // Garante que o ID e o PayerId estejam definidos
        if ($this->id === null || $this->PayerId === 0) {
             throw new \Exception("ID da Carteira ou PayerId não definidos para seleção.");
        }

        // O PDO é acessado via $this->db (herdado da BaseModel)
        $pdo = $this->db; 
        
        // 1. Desmarca todas as outras carteiras do usuário
        $sqlUnselect = "UPDATE {$this->tableName} SET selected = 0 WHERE PayerId = :user";
        $stmtUnselect = $pdo->prepare($sqlUnselect);
        $stmtUnselect->execute([':user' => $this->PayerId]);
        
        // 2. Marca esta carteira como selecionada
        $sqlSelect = "UPDATE {$this->tableName} SET selected = 1 WHERE id = :id";
        $stmtSelect = $pdo->prepare($sqlSelect);
        $stmtSelect->execute([':id' => $this->id]);
        
        // 3. Atualiza o objeto atual
        $this->selected = true;
    }


    // --- Métodos de cálculo ---

    /**
     * Calcula o saldo da carteira até uma data específica.
     */
    public function GetSaldo(\DateTime $fim = null): float
    {
        if ($this->id === null) return 0.0;
        
        // Usa a data atual se nenhuma for fornecida
        if ($fim === null) {
            $fim = new \DateTime();
        }
        
        $dataFimFormatada = $fim->format('Y-m-d H:i:s');
        $pdo = $this->db;

        // 1. Consulta para a SOMA dos valores (Saldo)
        $sqlSaldo = "SELECT COALESCE(SUM(valor), 0) AS saldo
                     FROM `PaymanetHistorico`
                     WHERE carteiraId = :carteira_id
                       AND data <= :data_fim";

        $stmtSaldo = $pdo->prepare($sqlSaldo);
        
        $stmtSaldo->execute([
            ':carteira_id' => $this->id,
            ':data_fim' => $dataFimFormatada 
        ]);

        $resultSaldo = $stmtSaldo->fetch(\PDO::FETCH_ASSOC);
        $this->saldo = (float)$resultSaldo['saldo']; // Armazena na propriedade de somente leitura

        // 2. Consulta para a Última Atualização (MAX data)
        $sqlUltimo = "SELECT MAX(data) AS ultima_data
                      FROM `PaymanetHistorico`
                      WHERE carteiraId = :carteira_id";

        $stmtUltimo = $pdo->prepare($sqlUltimo); 
        $stmtUltimo->execute([':carteira_id' => $this->id]);
        $ultimo = $stmtUltimo->fetch(\PDO::FETCH_ASSOC);

        // Atualiza a propriedade update_at do objeto
        // Se MAX(data) retornar NULL, mantemos o valor existente ou null.
        if ($ultimo['ultima_data']) {
            $this->update_at = $ultimo['ultima_data']; 
        }

        return $this->saldo;
    }

    /**
     * Busca um total de transações de um tipo específico em um período.
     * @param string $type O valor do Enum TransasaoType (ex: 'Aposta', 'Deposito').
     */
    protected function getTotalByType(string $type, \DateTime $inicio, \DateTime $fim): float
    {
        if ($this->id === null) return 0.0;
        
        $sql = "SELECT COALESCE(SUM(valor), 0) AS total
                FROM `PaymanetHistorico`
                WHERE carteiraId = :carteira_id
                  AND type = :type
                  AND data BETWEEN :data_inicio AND :data_fim";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':carteira_id' => $this->id,
            ':type' => $type,
            ':data_inicio' => $inicio->format('Y-m-d H:i:s'),
            ':data_fim' => $fim->format('Y-m-d H:i:s')
        ]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (float)$result['total'];
    }


    public function GetLucro(\DateTime $inicio, \DateTime $fim): float
    {
        // Assume que TransasaoType::Aposta->value retorna o valor correto
        return $this->getTotalByType(\TransasaoType::Aposta->value, $inicio, $fim);
    }

    public function GetDepositos(\DateTime $inicio, \DateTime $fim): float
    {
        return $this->getTotalByType(\TransasaoType::Deposito->value, $inicio, $fim);
    }

    public function GetRetiradas(\DateTime $inicio, \DateTime $fim): float
    {
        return $this->getTotalByType(\TransasaoType::Retirada->value, $inicio, $fim);
    }

    /**
     * Calcula o balanço líquido (saldo final + retiradas - depósitos) no período.
     */
    public function GetBalance(\DateTime $inicio, \DateTime $fim): float
    {
        $saldoFinal = $this->GetSaldo($fim); // Saldo acumulado até o final
        $depositoPeriodo = $this->GetDepositos($inicio, $fim);
        $retiradaPeriodo = $this->GetRetiradas($inicio, $fim);
        
        // A lógica de balanço líquido é geralmente:
        // Saldo Acumulado NO FIM - (Depósitos no Período - Retiradas no Período)
        // Se o saldo final já é o acumulado, a lógica original pode estar tentando
        // calcular o 'Profit/Loss' (Lucro/Prejuízo) do período:
        
        // Retiradas são tipicamente valores negativos no DB. Se GetRetiradas retorna ABS,
        // E GetDepositos retorna ABS (como a query SUM sugere), a lógica é:
        // Saldo Inicial = Saldo Final - Depósitos + Retiradas (para transações do período)
        
        // Mantendo a sua lógica original (que parece ser Saldo Final + Movimentação Líquida):
        return ($retiradaPeriodo - $depositoPeriodo) + $saldoFinal;
    }
    
    // --- EXPORTAÇÃO DE DADOS ---
    
    /**
     * Retorna a representação do objeto em array para uso em JSON/API.
     */
    public function toArray(): array
    {
        // Garante que o saldo seja calculado ANTES de exportar
        if ($this->saldo === 0.0) {
             $this->GetSaldo(); 
        }
        
        // Usa o toArray da BaseModel (que usa getPublicProperties) para consistência
        $data = parent::toArray(); 
        
        // Adiciona $saldo manualmente se for ignorado pelo parent::toArray
        $data['saldo'] = $this->saldo; 
        
        return $data;
    }
    
    public function toJson(bool $pretty = false): string
    {
        $options = JSON_UNESCAPED_UNICODE;
        if ($pretty) {
            $options |= JSON_PRETTY_PRINT;
        }

        // Converte o array para JSON
        return \json_encode($this->toArray(), $options);
    }
}