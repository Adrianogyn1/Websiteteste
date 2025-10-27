<?php


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
    
    private Database $db;

    


    public function __construct() 
    {
        $this->db = new Database();
        $this->dataCriacao = new DateTime();
        $this->data = new DateTime();
        $this->dificuldade = GameDificuldade::Normal;
    }

    // Métodos toArray e fromArray permanecem inalterados...
    
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
        $ph = new PaymanetHistorico(); // Passa a conexão
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

    // ----------------------------------------------------
    // --- MÉTODOS CRUD (Com consultas SQL preenchidas) ---
    // ----------------------------------------------------

    public function save(): bool
    {
        if (!$this->db) return false; // Verifica se a conexão existe
        if($this->id > 0)
        {
            return $this->update();
        }
        else 
        {
            return $this->create();
        }
    }
    
    // --- CREATE (C) ---
    public function create(): bool
    {
        if (!$this->db) return false;
        
        $sql = "INSERT INTO `PaymanetHistorico` (playerId, dataCriacao, gameId, gameNome, carteiraId, dificuldade, valor, bancaInicio, bancaFinal, tempo, data, type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $this->playerId,
            $this->dataCriacao->format('Y-m-d H:i:s'),
            $this->gameId,
            $this->gameNome,
            $this->carteiraId,
            $this->dificuldade->value,
            $this->valor,
            $this->bancaInicio,
            $this->bancaFinal,
            $this->tempo,
            $this->data->format('Y-m-d H:i:s'),
            $this->type->value
        ];

        // Executa a consulta e verifica o sucesso
        $success = $this->db->query($sql, $params);
        
        // Se a inserção foi bem-sucedida, obtém o ID
        if ($success) {
            $this->id = (int)$this->db->getPdo()->lastInsertId();
       return true;
        }
        
        return $success;//vários retorno?
    }

    // --- UPDATE (U) ---
    public function update(): bool
    {
        if ($this->id <= 0 || !$this->db) return false;
        
        $sql = "UPDATE `PaymanetHistorico` SET 
                playerId = ?, dataCriacao = ?, gameId = ?, gameNome = ?, carteiraId = ?, dificuldade = ?, 
                valor = ?, bancaInicio = ?, bancaFinal = ?, tempo = ?, data = ?, type = ?
                WHERE id = ?";

        $params = [
            $this->playerId,
            $this->dataCriacao->format('Y-m-d H:i:s'),
            $this->gameId,
            $this->gameNome,
            $this->carteiraId,
            $this->dificuldade->value,
            $this->valor,
            $this->bancaInicio,
            $this->bancaFinal,
            $this->tempo,
            $this->data->format('Y-m-d H:i:s'),
            $this->type->value,
            $this->id // O ID deve ser o último parâmetro do WHERE
        ];

        return $this->db->query($sql, $params);
    }

    // --- DELETE (D) ---
    public function delete(): bool
    {
        if ($this->id <= 0 || !$this->db) return false;
        
        return $this->db->query("DELETE FROM `PaymanetHistorico` WHERE id = ?", [$this->id]);
    }

    // --- READ (R) - Leitura por ID ---
    public function read(int $id): ?PaymanetHistorico // Corrigido o tipo de retorno de Carteira para PaymanetHistorico
    {
        if (!$this->db) return null;
        
        $stmt = $this->db->query("SELECT * FROM `PaymanetHistorico` WHERE id = ?", [$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            // Usa o método fromArray para popular a instância atual ou uma nova
            return self::fromArray($data); 
        }
        
        return null;
    }

    // --- READ (R) - Leitura de Todos ---
    public function all(): array
    {
        if (!$this->db) return [];
        
        $stmt = $this->db->query("SELECT * FROM `PaymanetHistorico` ORDER BY id DESC");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Opcionalmente, pode retornar um array de objetos PaymanetHistorico
        // $objects = [];
        // foreach ($results as $data) {
        //     $objects[] = self::fromArray($data, $this->db);
        // }
        // return $objects;

        return $results; // Retorna o array associativo, como no código original
    }
}