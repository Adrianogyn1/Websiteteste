<?php

//require_once 'TransasaoType.php';
//require_once 'GameDificuldade.php';

class PaymanetHistorico
{
    public int $id = 0;
    public int $playerId = 0;
    public DateTime $dataCriacao;
    public int $gameId = 0;
    public ?object $game = null;
    public int $bilheteId = 0;
    public ?object $bilhete = null;
    public string $gameNome = "";
    public int $carteiraId = 0;
    public GameDificuldade $dificuldade;
    public float $valor = 0;
    public float $bancaInicio = 0;
    public float $bancaFinal = 0;
    public float $tempo = 0;
    public DateTime $data;
    public TransasaoType $type;

    public function __construct()
    {
        $this->dataCriacao = new DateTime();
        $this->data = new DateTime();
        $this->dificuldade = GameDificuldade::Normal;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'playerId' => $this->playerId,
            'dataCriacao' => $this->dataCriacao->format('c'),
            'gameId' => $this->gameId,
            'gameNome' => $this->gameNome,
            'carteiraId' => $this->carteiraId,
            'dificuldade' => $this->dificuldade->value,
            'valor' => $this->valor,
            'bancaInicio' => $this->bancaInicio,
            'bancaFinal' => $this->bancaFinal,
            'tempo' => $this->tempo,
            'data' => $this->data->format('c'),
            'type' => $this->type->value
        ];
    }

    public static function fromArray(array $data): PaymanetHistorico
    {
        $ph = new PaymanetHistorico();
        $ph->id = $data['id'] ?? 0;
        $ph->playerId = $data['playerId'] ?? 0;
        $ph->dataCriacao = isset($data['dataCriacao']) ? new DateTime($data['dataCriacao']) : new DateTime();
        $ph->gameId = $data['gameId'] ?? 0;
        $ph->gameNome = $data['gameNome'] ?? '';
        $ph->carteiraId = $data['carteiraId'] ?? 0;
        $ph->dificuldade = isset($data['dificuldade']) ? GameDificuldade::from($data['dificuldade']) : GameDificuldade::Normal;
        $ph->valor = $data['valor'] ?? 0;
        $ph->bancaInicio = $data['bancaInicio'] ?? 0;
        $ph->bancaFinal = $data['bancaFinal'] ?? 0;
        $ph->tempo = $data['tempo'] ?? 0;
        $ph->data = isset($data['data']) ? new DateTime($data['data']) : new DateTime();
        $ph->type = isset($data['type']) ? TransasaoType::from($data['type']) : TransasaoType::Deposito;
        return $ph;
    }
}
