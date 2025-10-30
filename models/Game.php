<?php
//require_once 'Database.php';



class Game extends DataObj
{
    
    public int $lastId = 0;

    public string $nome = "";
    public string $provedor = "";
    public GameType $type;
    public string $demo = "";
    public string $url = "";
    public string $image = "";
    public ?\DateTime $ultimaData = null;

    //somente leitura 
    #[IgnoreInDatabase]
    public array $provedores = [
        "Pg" => "Pg Games",
        "PP" => "Pragmatic",
        "PP Live" => "Pragmatic Live",
        "Tada" => "Tada Gaming",
        "Ez" => "Ezugi",
        "PT" => "Paytech",
        "Evo" => "Evolution",
        "PPK" => "Popok Gaming",
        "NL" => "No Limite",
        "HC" => "Hacsaw",
        "GG" => "Global Gaming",
        "EP" => "Endorphina",
        "Sb" => "Stribe",
        "RT" => "Red Tiger",
        "Btg" => "Big Time Gaming",
        "Net" => "NetEnd",
        "??" => "Outros"
    ];

    

    public function __construct()
    {
       // $this->db = new Database();
        $this->type = GameType::Slot;
        parent::__construct(); 
    }
    
    
}
