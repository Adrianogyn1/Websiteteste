<?php

class Dashboard
{
    private PDO $db;
    private ?int $carteiraId;

    public float $saldo = 0;
    public float $deposito = 0;
    public float $retirada = 0;
    public float $lucro = 0;
    public int $dias = 0;
    public float $avg_dia = 0;
    public array $chart_labels = [];
    public array $chart_data = [];

    public function __construct(PDO $db, ?int $carteiraId = null)
    {
        $this->db = $db;
        $this->carteiraId = $carteiraId;
    }

    public function load(\DateTime $inicio = null, \DateTime $fim = null): void
    {
        if (!$fim) $fim = new \DateTime();
        if (!$inicio) $inicio = (clone $fim)->modify('-7 days');

        $where = '';
        $params = [
            ':inicio' => $inicio->format('Y-m-d H:i:s'),
            ':fim' => $fim->format('Y-m-d H:i:s')
        ];

        if ($this->carteiraId) {
            $where = 'AND carteiraId = :carteiraId';
            $params[':carteiraId'] = $this->carteiraId;
        }

        // Buscar saldo, depósitos, retiradas
        $sql = "SELECT 
                    COALESCE(SUM(valor),0) AS saldo,
                    COALESCE(SUM(CASE WHEN type = 1 THEN valor ELSE 0 END),0) AS deposito,
                    COALESCE(SUM(CASE WHEN type = 2 THEN valor ELSE 0 END),0) AS retirada,
                    COALESCE(SUM(CASE WHEN type = 3 THEN valor ELSE 0 END),0) AS lucro
                FROM PaymanetHistorico
                WHERE data BETWEEN :inicio AND :fim $where";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->saldo = (float) $res['saldo'];
        $this->deposito = (float) $res['deposito'];
        $this->retirada = (float) $res['retirada'];
        $this->lucro = (float) $res['lucro'];

        // Dias e média
        $this->dias = $fim->diff($inicio)->days + 1;
        $this->avg_dia = $this->dias ? $this->lucro / $this->dias : 0;

        // Chart diário
        $this->chart_labels = [];
        $this->chart_data = [];

        $interval = new DateInterval('P1D');
        $period = new DatePeriod($inicio, $interval, $fim->modify('+1 day')); // inclusivo

        foreach ($period as $d) {
            $this->chart_labels[] = $d->format('D');
            $stmt = $this->db->prepare(
                "SELECT COALESCE(SUM(valor),0) AS total 
                 FROM PaymanetHistorico
                 WHERE data BETWEEN :inicioDia AND :fimDia $where"
            );
            $inicioDia = $d->format('Y-m-d 00:00:00');
            $fimDia = $d->format('Y-m-d 23:59:59');
            $stmt->execute(array_merge($params, [':inicioDia' => $inicioDia, ':fimDia' => $fimDia]));
            $dayRes = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->chart_data[] = (float) $dayRes['total'];
        }
    }

    public function toJson(): string
    {
        return json_encode([
            'saldo' => $this->saldo,
            'deposito' => $this->deposito,
            'retirada' => $this->retirada,
            'lucro' => $this->lucro,
            'dias' => $this->dias,
            'avg_dia' => $this->avg_dia,
            'chart_labels' => $this->chart_labels,
            'chart_data' => $this->chart_data
        ], JSON_UNESCAPED_UNICODE);
    }
}
