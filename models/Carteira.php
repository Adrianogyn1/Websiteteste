<?php

class Carteira
{
    
    public int $id = 0;
    public string $nome = "";
    public string $meta = "";
    public bool $useRelatorio = true;
    public int $PayerId = 0;
    public string $url = "";
    public string $login = "";
    public string $senha = "";
    public bool $teste = false;
    public bool $selected = false;
    public string $created_at ='';
    public string $update_at ='';
    
    public float $saldo = 0;

    /** @var LinkGame[] */
    public array $gamesLink = [];

    public ?GestaoConfig $config = null;

    /** @var GestaoHistorico[] */
    public array $gestoes = [];

    /** @var PaymanetHistorico[] */
    public array $pagamentos = [];

    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
       // $this->config = new GestaoConfig();
        $this->gamesLink = [];
        $this->gestoes = [];
        $this->pagamentos = [];
       // $this->createTable();
    }

    public function createTable(): void
{
    $this->db->getPdo()->exec("
        CREATE TABLE IF NOT EXISTS carteiras (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            meta VARCHAR(255),
            useRelatorio TINYINT(1) DEFAULT 1,
            PayerId INT,
            url VARCHAR(255),
            login VARCHAR(255),
            senha VARCHAR(255),
            teste TINYINT(1) DEFAULT 0,
            selected TINYINT(1) DEFAULT 0
        )
    ");
}


    // --- CRUD ---

    public function save(): bool
    {
        if($this->id>0)
        {
          return $this->update();
        }
        else 
        {
           return $this->create();
        }
    }
    
    public function create(): bool
    {
        $this->db->query("
            INSERT INTO Carteira 
            (nome, meta, useRelatorio, PayerId, url, login, senha, teste, selected,created_at,update_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?,NOW(),NOW())
        ", [
            $this->nome,
            $this->meta,
            $this->useRelatorio ? 1 : 0,
            $this->PayerId,
            $this->url,
            $this->login,
            $this->senha,
            $this->teste ? 1 : 0,
            $this->selected ? 1 : 0
        ]);

        $this->id = (int)$this->db->getPdo()->lastInsertId();
        return true;
    }

    public function update(): bool
    {
        if ($this->id <= 0) return false;
        
        $this->db->query("
            UPDATE Carteira SET 
                nome = ?, 
                meta = ?, 
                useRelatorio = ?, 
                PayerId = ?, 
                url = ?, 
                login = ?, 
                senha = ?, 
                teste = ?, 
                selected = ?,
                update_at=NOW(),
                
            WHERE id = ?
        ", [
            $this->nome,
            $this->meta,
            $this->useRelatorio ? 1 : 0,
            $this->PayerId,
            $this->url,
            $this->login,
            $this->senha,
            $this->teste ? 1 : 0,
            $this->selected ? 1 : 0,
            $this->id
        ]);

        return true;
    }

    public function delete(): bool
    {
        if ($this->id <= 0) return false;
        $this->db->query("DELETE FROM Carteira WHERE id = ?", [$this->id]);
        return true;
    }

    public function read(int $id): ?Carteira
    {
        $stmt = $this->db->query("SELECT * FROM Carteira WHERE id = ?", [$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) return null;

        $this->id = (int)$data['id'];
        $this->nome = $data['nome'];
        $this->meta = $data['meta'];
        $this->useRelatorio = (bool)$data['useRelatorio'];
        $this->PayerId = (int)$data['PayerId'];
        $this->url = $data['url'];
        $this->login = $data['login'];
        $this->senha = $data['senha'];
        $this->teste = (bool)$data['teste'];
        $this->selected = (bool)$data['selected'];

        // Carregar relações
        //$this->loadRelations();

        return $this;
    }

    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM carteiras ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function loadRelations(): void
    {
        // gamesLink
        $stmt = $this->db->query("SELECT * FROM link_games WHERE carteiraId = ?", [$this->id]);
        $this->gamesLink = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $link = new LinkGame();
            $link->id = (int)$row['id'];
            $link->carteiraId = (int)$row['carteiraId'];
            $link->gameId = (int)$row['gameId'];
            $link->url = $row['url'];
            $this->gamesLink[] = $link;
        }

        // config
        $stmt = $this->db->query("SELECT * FROM gestao_config WHERE carteiraId = ?", [$this->id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $this->config = new GestaoConfig();
            $this->config->id = (int)$data['id'];
            $this->config->carteiraId = (int)$data['carteiraId'];
            $this->config->settings = $data['settings'] ?? '';
        }

        // gestoes
        $stmt = $this->db->query("SELECT * FROM gestao_historico WHERE carteiraId = ?", [$this->id]);
        $this->gestoes = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $gestao = new GestaoHistorico();
            $gestao->id = (int)$row['id'];
            $gestao->carteiraId = (int)$row['carteiraId'];
            $gestao->descricao = $row['descricao'] ?? '';
            $gestao->valor = (float)$row['valor'];
            $gestao->data = isset($row['data']) ? new DateTime($row['data']) : null;
            $this->gestoes[] = $gestao;
        }

        // pagamentos
        $stmt = $this->db->query("SELECT * FROM paymanet_historico WHERE carteiraId = ?", [$this->id]);
        $this->pagamentos = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $pag = new PaymanetHistorico();
            $pag->id = (int)$row['id'];
            $pag->carteiraId = (int)$row['carteiraId'];
            $pag->valor = (float)$row['valor'];
            $pag->type = TransasaoType::from($row['type']);
            $pag->data = isset($row['data']) ? new DateTime($row['data']) : null;
            $this->pagamentos[] = $pag;
        }
    }

    // --- Métodos de cálculo ---

    public function GetSaldo(\DateTime $fim = null): float
{
    if ($fim === null) {
        $fim = new \DateTime();
    }

    $sql = "SELECT COALESCE(SUM(valor), 0) AS saldo
            FROM `PaymanetHistorico`
            WHERE carteiraId = :carteira_id
              AND data <= :data_fim";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([
        ':carteira_id' => $this->id,
        ':data_fim' => $fim->format('Y-m-d H:i:s')
    ]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $this->saldo = (float)$result['saldo'];
    
    // Atualiza o update_at com o saldo atual
    $this->update_at = $this->saldo;

    return $this->saldo;
}


    public function GetLucro(\DateTime $inicio, \DateTime $fim): float
{
    $sql = "SELECT COALESCE(SUM(valor), 0) AS lucro
            FROM `PaymanetHistorico`
            WHERE carteiraId = :carteira_id
              AND type = :type
              AND data BETWEEN :data_inicio AND :data_fim";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([
        ':carteira_id' => $this->id,
        ':type' => TransasaoType::Aposta, // ou 'Aposta' dependendo de como você define
        ':data_inicio' => $inicio->format('Y-m-d H:i:s'),
        ':data_fim' => $fim->format('Y-m-d H:i:s')
    ]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return (float)$result['lucro'];
}


    public function GetDepositos(\DateTime $inicio, \DateTime $fim): float
{
    $sql = "SELECT COALESCE(SUM(valor), 0) AS total
            FROM `PaymanetHistorico`
            WHERE carteiraId = :carteira_id
              AND type = :type
              AND data BETWEEN :data_inicio AND :data_fim";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([
        ':carteira_id' => $this->id,
        ':type' => TransasaoType::Deposito,
        ':data_inicio' => $inicio->format('Y-m-d H:i:s'),
        ':data_fim' => $fim->format('Y-m-d H:i:s')
    ]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return (float)$result['total'];
}

public function GetRetiradas(\DateTime $inicio, \DateTime $fim): float
{
    $sql = "SELECT COALESCE(SUM(valor), 0) AS total
            FROM `PaymanetHistorico`
            WHERE carteiraId = :carteira_id
              AND type = :type
              AND data BETWEEN :data_inicio AND :data_fim";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([
        ':carteira_id' => $this->id,
        ':type' => TransasaoType::Retirada,
        ':data_inicio' => $inicio->format('Y-m-d H:i:s'),
        ':data_fim' => $fim->format('Y-m-d H:i:s')
    ]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return (float)$result['total'];
}


    public function GetBalance(\DateTime $inicio, \DateTime $fim): float
    {
        $saldo = $this->GetSaldo($fim);
        $deposito = $this->GetDepositos($inicio, $fim);
        $retirada = $this->GetRetiradas($inicio, $fim);
        return (abs($retirada) - abs($deposito)) + $saldo;
    }
    
    public function toArray(): array
    {
        $this->saldo = $this->GetSaldo();
       // $this->update_at = "";
        
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'meta' => $this->meta,
            'useRelatorio' => $this->useRelatorio,
            'PayerId' => $this->PayerId,
            'url' => $this->url,
            'login' => $this->login,
            'senha' => $this->senha,
            'teste' => $this->teste,
            'selected' => $this->selected,
            'created_at' => $this->created_at,
            'update_at' => $this->update_at,
            'saldo' => $this->saldo
        ];
    }
    
    public function toJson(bool $pretty = false): string
    {
        $options = JSON_UNESCAPED_UNICODE;
        if ($pretty) {
            $options |= JSON_PRETTY_PRINT;
        }

        // Converte o objeto atual em JSON
        return json_encode($this->toArray(), $options);
    }
}
