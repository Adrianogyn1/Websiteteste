<?php
class GestaoHistorico extends DataObj
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

    

    public function __construct()
    {
        /*$this->db = $pdo;
        $this->dataInicio = new DateTime();
        $this->dataPrevista = new DateTime();
        $this->dataFim = (clone $this->dataInicio)->modify('+30 days');
        $this->dataFimSave = (clone $this->dataFim);
        $this->valor = 1000;*/
        parent::__construct(); 
    }

    // =================== CRUD ===================

}