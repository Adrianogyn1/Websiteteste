<?php

class Dashboard
{
    private PDO $db;
    private ?int $carteiraId;

    public float $saldo = 0;
    public float $deposito = 0;
    public float $retirada = 0;
    public float $lucro = 0;
    public int $dias = 7;
    public float $avg_dia = 0;

    public array $chart_labels = [];
    public array $chart_data = [];

    public function __construct(PDO $db, ?int $carteiraId = null)
    {
        $this->db = $db;
        $this->carteiraId = $carteiraId;
    }

    public function carregar(\DateTime $inicio=null,DateTime $fim=null): void
    {
        if (!$fim) $fim = new \DateTime();
        if (!$inicio) $inicio = (clone $fim)->modify('-7 days');
        // Filtros base
        $where = "WHERE data BETWEEN :inicio AND :fim";
        $params = [
            ':inicio' => $inicio->format('Y-m-d H:i:s'),
            ':fim'    => $fim->format('Y-m-d H:i:s')
        ];

        if ($this->carteiraId && $this->carteiraId >0) {
            $where .= " AND carteiraId = :carteiraId";
            $params[':carteiraId'] = $this->carteiraId;
        }

        // ==== SALDO ATUAL ====
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(valor), 0) AS saldo
            FROM PaymanetHistorico
            " . ($this->carteiraId ? "WHERE carteiraId = :carteiraId" : "")
        );
        $stmt->execute($this->carteiraId ? [':carteiraId' => $this->carteiraId] : []);
        $this->saldo = (float) $stmt->fetchColumn();

        // ==== DEPÓSITOS ====
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(valor), 0) AS total
            FROM PaymanetHistorico
            $where AND type = :deposito
        ");
        $stmt->execute(array_merge($params, [':deposito' => TransasaoType::Deposito->value]));
        $this->deposito = (float) $stmt->fetchColumn();

        // ==== RETIRADAS ====
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(valor), 0) AS total
            FROM PaymanetHistorico
            $where AND type = :retirada
        ");
        $stmt->execute(array_merge($params, [':retirada' => TransasaoType::Retirada->value]));
        $this->retirada = (float) $stmt->fetchColumn();

        // ==== LUCRO ====
        $this->lucro = $this->saldo - $this->deposito + $this->retirada;

        // ==== DADOS DO GRÁFICO ====
        $this->chart_labels = [];
        $this->chart_data = [];

        $periodo = new DatePeriod($inicio, new DateInterval('P1D'), $fim);
        foreach ($periodo as $dia) {
            $inicioDia = $dia->format('Y-m-d 00:00:00');
            $fimDia    = $dia->format('Y-m-d 23:59:59');

            $paramsDia = [
                ':inicioDia' => $inicioDia,
                ':fimDia'    => $fimDia
            ];
            if ($this->carteiraId) {
                $paramsDia[':carteiraId'] = $this->carteiraId;
            }

            $sql = "
                SELECT COALESCE(SUM(valor), 0) AS total
                FROM PaymanetHistorico
                WHERE data BETWEEN :inicioDia AND :fimDia
                " . ($this->carteiraId ? ' AND carteiraId = :carteiraId' : '');

            $stmt = $this->db->prepare($sql);
            $stmt->execute($paramsDia);
            $totalDia = (float) $stmt->fetchColumn();

            $this->chart_labels[] = $dia->format('D');
            $this->chart_data[]   = $totalDia;
        }

        // Média diária
        $this->dias = iterator_count($periodo);
        $this->avg_dia = $this->dias > 0 ? $this->lucro / $this->dias : 0;
    }

    public function toJson(): string
    {
        return json_encode([
            'saldo'       => $this->saldo,
            'deposito'    => $this->deposito,
            'retirada'    => $this->retirada,
            'lucro'       => $this->lucro,
            'dias'        => $this->dias,
            'avg_dia'     => $this->avg_dia,
            'chart_labels'=> $this->chart_labels,
            'chart_data'  => $this->chart_data,
        ], JSON_UNESCAPED_UNICODE);
    }
}
