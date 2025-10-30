<?php
// Arquivo: PaymanetHistorico.php

// Requisitos: BaseModel/CollectionModel, Database, GameDificuldade (Enum), TransasaoType (Enum)

class PaymanetHistorico extends BaseModel 
{
    protected string $tableName = 'PaymanetHistorico'; // Define a tabela
    
    // --- PROPRIEDADES DE BANCO DE DADOS ---
    
    // foreign keys
    public ?int $playerId = 0;
    public int $carteiraId = 0;
    public ?int $gameId = null;
    public ?int $bilheteId = null;
    
    // Enums (Tipadas)
    public ?\GameDificuldade $dificuldade = null; // \GameDificuldade::Normal é definido no __construct
    public ?\TransasaoType $type = null;
    
    // Valores (Tipadas)
    public ?float $valor = 0.0; // Float
    public ?float $bancaInicio = 0.0;
    public ?float $bancaFinal = 0.0;
    public ?float $tempo = 0.0;
    
    // Timestamps
    public ?\DateTime $dataCriacao = null; // Usar objeto DateTime
    public ?\DateTime $data = null;
    
    // --- PROPRIEDADES SOMENTE LEITURA/RELACIONAMENTO (Ignoradas no DB) ---
    
    #[IgnoreInDatabase]
    public ?string $gameNome = "";
    
    // Objetos relacionados
    #[IgnoreInDatabase]
    public ?object $game = null;
    #[IgnoreInDatabase]
    public ?object $bilhete = null;
    #[IgnoreInDatabase]
    public ?object $carteira = null; // Adicionado para consistência
    

    public function __construct() 
    {
        // Garante que o construtor pai seja chamado no final.
        // O construtor pai (BaseModel) chama Database::instance()->getPdo()
        // e define $this->db.
        
        // Inicializa objetos e Enums no escopo global
        if ($this->dataCriacao === null) {
            $this->dataCriacao = new \DateTime();
        }
        if ($this->data === null) {
            $this->data = new \DateTime();
        }
        // Inicializa o Enum padrão se não for definido
        if ($this->dificuldade === null) {
            $this->dificuldade = \GameDificuldade::Normal;
        }

        parent::__construct(); 
    }
    
    // --- MÉTODOS AUXILIARES (HELPS) PARA CARREGAR OBJETOS ---

    /**
     * Helper: Carrega a carteira relacionada a este histórico.
     * @return \Carteira|null
     */
    public function getCarteira(): ?\Carteira
    {
        if ($this->carteira === null && $this->carteiraId > 0) {
            // Assume que existe uma classe Carteira que herda de BaseModel
            $this->carteira = \Carteira::find($this->carteiraId); 
        }
        return $this->carteira;
    }

    /**
     * Helper: Carrega o objeto Game relacionado a este histórico.
     * @return \Game|null
     */
    public function getGame(): ?\Game
    {
        if ($this->game === null && $this->gameId !== null && $this->gameId > 0) {
            // Assume que existe uma classe Game que herda de BaseModel
            $this->game = \Game::find($this->gameId); 
            // Atualiza a propriedade de somente leitura se o objeto for encontrado
            if ($this->game !== null) {
                 // Assume que a classe Game tem uma propriedade $nome
                 $this->gameNome = $this->game->nome  ?? ''; 
            }
        }
        return $this->game;
    }

    /**
     * Helper: Carrega o objeto Bilhete relacionado a este histórico.
     * @return \Bilhete|null
     */
    public function getBilhete(): ?\Bilhete
    {
        if ($this->bilhete === null && $this->bilheteId !== null && $this->bilheteId > 0) {
            // Assume que existe uma classe Bilhete que herda de BaseModel
            $this->bilhete = \Bilhete::find($this->bilheteId); 
        }
        return $this->bilhete;
    }
    
    // --- Métodos Adicionais (Opcional) ---
    
    /**
     * Retorna a representação do objeto em array para uso em JSON/API.
     * Inclui os valores legíveis dos Enums.
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        
        // Converte objetos DateTime para string (se necessário)
        $data['dataCriacao'] = $this->dataCriacao?->format('Y-m-d H:i:s') ?? null;
        $data['data'] = $this->data?->format('Y-m-d H:i:s') ?? null;
        
        // Adiciona valores legíveis dos Enums (se existirem)
        $data['dificuldade'] = $this->dificuldade?->name ?? $data['dificuldade'];
        $data['type'] = $this->type?->name ?? $data['type'];
        
        // Inclui o nome do jogo (que pode ser carregado via getGame())
        if (empty($this->gameNome) && $this->gameId) {
             $this->getGame();
        }
        $data['gameNome'] = $this->gameNome;
        
        return $data;
    }
}