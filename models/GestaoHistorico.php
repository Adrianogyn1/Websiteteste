<?php
class GestaoHistorico
{
    public int $id = 0;
    public int $gestaoConfigId = 0;
    public int $carteiraId = 0;
    public DateTime $dataPrevista;
    public DateTime $dataInicio;
    public DateTime $dataFim;
    public DateTime $dataFimSave;
    public float $valor = 0;
    public float $valorWin = 0;
    public float $valorLoss = 0;
    public float $menorValor = 0;
    public float $valorInicial = 0;
    public int $sessaoDia = 3;
    public int $sessao = 0;
    public float $stopLoss = 20;
    public float $stopWin = 10;
    public bool $completo = false;
    public string $tipo = 'Aposta'; // ConclusaoTipo

    private PDO $db;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
        $this->dataInicio = new DateTime();
        $this->dataPrevista = new DateTime();
        $this->dataFim = (clone $this->dataInicio)->modify('+30 days');
        $this->dataFimSave = (clone $this->dataFim);
        $this->valor = 1000;
    }

    // =================== CRUD ===================

public function createTable(): void
{
    $sql = "
        CREATE TABLE IF NOT EXISTS gestao_historico (
            id INT AUTO_INCREMENT PRIMARY KEY,
            gestaoConfigId INT NOT NULL,
            carteiraId INT NOT NULL,
            dataPrevista DATETIME,
            dataInicio DATETIME,
            dataFim DATETIME,
            dataFimSave DATETIME,
            valor FLOAT DEFAULT 0,
            valorWin FLOAT DEFAULT 0,
            valorLoss FLOAT DEFAULT 0,
            menorValor FLOAT DEFAULT 0,
            valorInicial FLOAT DEFAULT 0,
            sessaoDia INT DEFAULT 3,
            sessao INT DEFAULT 0,
            stopLoss FLOAT DEFAULT 20,
            stopWin FLOAT DEFAULT 10,
            completo TINYINT(1) DEFAULT 0,
            tipo VARCHAR(50) DEFAULT 'Aposta',
            FOREIGN KEY (gestaoConfigId) REFERENCES gestao_configs(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    $this->db->exec($sql);
}



    public function create(): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO gestao_historico 
                (gestaoConfigId, carteiraId, dataPrevista, dataInicio, dataFim, dataFimSave, valor, valorWin, valorLoss, menorValor, valorInicial, sessaoDia, sessao, stopLoss, stopWin, completo, tipo)
            VALUES
                (:gestaoConfigId, :carteiraId, :dataPrevista, :dataInicio, :dataFim, :dataFimSave, :valor, :valorWin, :valorLoss, :menorValor, :valorInicial, :sessaoDia, :sessao, :stopLoss, :stopWin, :completo, :tipo)
        ");
        return $stmt->execute([
            ':gestaoConfigId' => $this->gestaoConfigId,
            ':carteiraId' => $this->carteiraId,
            ':dataPrevista' => $this->dataPrevista->format('Y-m-d H:i:s'),
            ':dataInicio' => $this->dataInicio->format('Y-m-d H:i:s'),
            ':dataFim' => $this->dataFim->format('Y-m-d H:i:s'),
            ':dataFimSave' => $this->dataFimSave->format('Y-m-d H:i:s'),
            ':valor' => $this->valor,
            ':valorWin' => $this->valorWin,
            ':valorLoss' => $this->valorLoss,
            ':menorValor' => $this->menorValor,
            ':valorInicial' => $this->valorInicial,
            ':sessaoDia' => $this->sessaoDia,
            ':sessao' => $this->sessao,
            ':stopLoss' => $this->stopLoss,
            ':stopWin' => $this->stopWin,
            ':completo' => $this->completo ? 1 : 0,
            ':tipo' => $this->tipo
        ]);
    }

    public function update(): bool { /* similar ao create() */ }
    public function delete(): bool { /* similar ao create() */ }
    public function load(int $id): bool { /* similar ao GestaoConfig::load */ }
}
