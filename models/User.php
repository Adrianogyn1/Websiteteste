<?php

class User
{
    public ?int $id;
    public string $nome;
    public string $email;
    public string $senha;
    public string $criado_em;

    // Método para preencher dados manualmente
    public function create(string $nome, string $email, string $senha, ?int $id = null, ?string $criado_em = null)
    {
        $this->id = $id;
        $this->nome = $nome;
        $this->email = $email;
        $this->senha = password_hash($senha, PASSWORD_DEFAULT);
        $this->criado_em = $criado_em ?? date('Y-m-d H:i:s');
    }

    // Método para criar a partir de um array (por exemplo, do banco)
    public static function createFromArray(array $data): User
    {
        $user = new User();
        $user->id = $data['id'] ?? null;
        $user->nome = $data['nome'] ?? '';
        $user->email = $data['email'] ?? '';
        $user->senha = $data['senha'] ?? ''; // normalmente já está hash
        $user->criado_em = $data['criado_em'] ?? date('Y-m-d H:i:s');
        return $user;
    }

    public function __toString(): string
    {
        return json_encode([
            'id' => $this->id,
            'nome' => $this->nome,
            'email' => $this->email,
            'criado_em' => $this->criado_em
        ], JSON_PRETTY_PRINT);
    }
}
