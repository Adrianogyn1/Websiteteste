<?php

// Inbox.php

//namespace App\Model;

//use App\Model\Attributes\IgnoreInDatabase;

class Inbox extends BaseModel
{
    protected string $tableName = 'inbox';

    // Campos do BD
    public ?int $id = 0;
    public ?string $remetente = '';
    public ?string $assunto = '';
    public ?string $mensagem = '';
    public ?bool $lida = false; // Corrigido para bool
    public ?string $created_at = null;
    public ?string $updated_at = null;

    // Campo IGNORADO no BD
    #[IgnoreInDatabase]
    public ?string $logEnvio = null; 
}