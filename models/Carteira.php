<?php
/*
require_once 'Database.php';
require_once 'LinkGame.php';
require_once 'GestaoConfig.php';
require_once 'GestaoHistorico.php';
require_once 'PaymanetHistorico.php';
require_once 'TransasaoType.php';
*/

class Carteira
{
    public static ?Carteira $Current = null;

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
        $this->config = new GestaoConfig();
        $this->gamesLink = [];
        $this->gestoes = [];
        $this->pagamentos = [];
        $this->createTable();
    }

    private function createTable(): void
    {
        $this->db->getPdo()->exec("
            CREATE TABLE IF NOT EXISTS carteiras (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                meta TEXT,
                useRelatorio INTEGER DEFAULT 1,
                PayerId INTEGER,
                url TEXT,
                login TEXT,
                senha TEXT,
                teste INTEGER DEFAULT 0,
                selected INTEGER DEFAULT 0
            )
        ");
    }

    // --- CRUD ---

    public function create(): bool
    {
        $this->db->query("
            INSERT INTO carteiras 
            (nome, meta, useRelatorio, PayerId, url, login, senha, teste, selected)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
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
            UPDATE carteiras SET 
                nome = ?, 
                meta = ?, 
                useRelatorio = ?, 
                PayerId = ?, 
                url = ?, 
                login = ?, 
                senha = ?, 
                teste = ?, 
                selected = ?
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
        $this->db->query("DELETE FROM carteiras WHERE id = ?", [$this->id]);
        return true;
    }

    public function read(int $id): ?Carteira
    {
        $stmt = $this->db->query("SELECT * FROM carteiras WHERE id = ?", [$id]);
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
        $this->loadRelations();

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

    public function GetSaldo(\DateTime $fim): float
    {
        return array_sum(array_map(fn($p) => $p->valor, $this->pagamentos));
    }

    public function GetLucro(\DateTime $inicio, \DateTime $fim): float
    {
        return array_sum(array_map(
            fn($p) => ($p->type === TransasaoType::Aposta && $p->data >= $inicio && $p->data <= $fim) ? $p->valor : 0,
            $this->pagamentos
        ));
    }

    public function GetDepositos(\DateTime $inicio, \DateTime $fim): float
    {
        return array_sum(array_map(
            fn($p) => ($p->type === TransasaoType::Deposito && $p->data >= $inicio && $p->data <= $fim) ? $p->valor : 0,
            $this->pagamentos
        ));
    }

    public function GetRetiradas(\DateTime $inicio, \DateTime $fim): float
    {
        return array_sum(array_map(
            fn($p) => ($p->type === TransasaoType::Retirada && $p->data >= $inicio && $p->data <= $fim) ? $p->valor : 0,
            $this->pagamentos
        ));
    }

    public function GetBalance(\DateTime $inicio, \DateTime $fim): float
    {
        $saldo = $this->GetSaldo($fim);
        $deposito = $this->GetDepositos($inicio, $fim);
        $retirada = $this->GetRetiradas($inicio, $fim);
        return (abs($retirada) - abs($deposito)) + $saldo;
    }
}
