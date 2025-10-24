<?php
class GestaoConfig
{
    public int $id = 0;
    public string $Motivacao = '';
    public string $nome = '';
    public int $carteiraId = 0;
    public float $valor = 100;
    public float $meta = 1000000;
    public float $valorAdd = 1;
    public int $sessoesDia = 3;
    public int $sessoesUpgrade = 12;
    public DateTime $dataInicio;
    public DateTime $dataFim;
    public float $stopLoss = 20;
    public float $stopWin = 10;
    public string $tipo = 'Porcetagem'; // TipoGestao
    public array $dias = [];
    public array $gestoes = []; // GestaoHistorico[]

    private PDO $db;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
        $this->dataInicio = new DateTime();
        $this->dataFim = (clone $this->dataInicio)->modify('+30 days');
        $this->dias = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
    }

    // =================== CRUD ===================
public function createTable(): void
{
    $sql = "
        CREATE TABLE IF NOT EXISTS gestao_configs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            Motivacao VARCHAR(255),
            nome VARCHAR(255) NOT NULL,
            carteiraId INT NOT NULL,
            valor FLOAT DEFAULT 100,
            meta FLOAT DEFAULT 1000000,
            valorAdd FLOAT DEFAULT 1,
            sessoesDia INT DEFAULT 3,
            sessoesUpgrade INT DEFAULT 12,
            dataInicio DATETIME,
            dataFim DATETIME,
            stopLoss FLOAT DEFAULT 20,
            stopWin FLOAT DEFAULT 10,
            tipo VARCHAR(50) DEFAULT 'Porcetagem'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    $this->db->exec($sql);
}



    public function create(): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO gestao_configs 
                (Motivacao, nome, carteiraId, valor, meta, valorAdd, sessoesDia, sessoesUpgrade, dataInicio, dataFim, stopLoss, stopWin, tipo)
            VALUES
                (:Motivacao, :nome, :carteiraId, :valor, :meta, :valorAdd, :sessoesDia, :sessoesUpgrade, :dataInicio, :dataFim, :stopLoss, :stopWin, :tipo)
        ");
        return $stmt->execute([
            ':Motivacao' => $this->Motivacao,
            ':nome' => $this->nome,
            ':carteiraId' => $this->carteiraId,
            ':valor' => $this->valor,
            ':meta' => $this->meta,
            ':valorAdd' => $this->valorAdd,
            ':sessoesDia' => $this->sessoesDia,
            ':sessoesUpgrade' => $this->sessoesUpgrade,
            ':dataInicio' => $this->dataInicio->format('Y-m-d H:i:s'),
            ':dataFim' => $this->dataFim->format('Y-m-d H:i:s'),
            ':stopLoss' => $this->stopLoss,
            ':stopWin' => $this->stopWin,
            ':tipo' => $this->tipo
        ]);
    }

    public function update(): bool
    {
        if ($this->id <= 0) return false;
        $stmt = $this->db->prepare("
            UPDATE gestao_configs SET
                Motivacao = :Motivacao,
                nome = :nome,
                carteiraId = :carteiraId,
                valor = :valor,
                meta = :meta,
                valorAdd = :valorAdd,
                sessoesDia = :sessoesDia,
                sessoesUpgrade = :sessoesUpgrade,
                dataInicio = :dataInicio,
                dataFim = :dataFim,
                stopLoss = :stopLoss,
                stopWin = :stopWin,
                tipo = :tipo
            WHERE id = :id
        ");
        return $stmt->execute([
            ':Motivacao' => $this->Motivacao,
            ':nome' => $this->nome,
            ':carteiraId' => $this->carteiraId,
            ':valor' => $this->valor,
            ':meta' => $this->meta,
            ':valorAdd' => $this->valorAdd,
            ':sessoesDia' => $this->sessoesDia,
            ':sessoesUpgrade' => $this->sessoesUpgrade,
            ':dataInicio' => $this->dataInicio->format('Y-m-d H:i:s'),
            ':dataFim' => $this->dataFim->format('Y-m-d H:i:s'),
            ':stopLoss' => $this->stopLoss,
            ':stopWin' => $this->stopWin,
            ':tipo' => $this->tipo,
            ':id' => $this->id
        ]);
    }

    public function delete(): bool
    {
        if ($this->id <= 0) return false;
        $stmt = $this->db->prepare("DELETE FROM gestao_configs WHERE id = :id");
        return $stmt->execute([':id' => $this->id]);
    }

    public function load(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT * FROM gestao_configs WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) return false;

        $this->id = (int)$data['id'];
        $this->Motivacao = $data['Motivacao'];
        $this->nome = $data['nome'];
        $this->carteiraId = (int)$data['carteiraId'];
        $this->valor = (float)$data['valor'];
        $this->meta = (float)$data['meta'];
        $this->valorAdd = (float)$data['valorAdd'];
        $this->sessoesDia = (int)$data['sessoesDia'];
        $this->sessoesUpgrade = (int)$data['sessoesUpgrade'];
        $this->dataInicio = new DateTime($data['dataInicio']);
        $this->dataFim = new DateTime($data['dataFim']);
        $this->stopLoss = (float)$data['stopLoss'];
        $this->stopWin = (float)$data['stopWin'];
        $this->tipo = $data['tipo'];

        return true;
    }
}
