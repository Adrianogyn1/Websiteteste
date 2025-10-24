<?php

class ApiMessage
{
    public bool $sucess = false;
    public string $msg = '';
    public mixed $data = null; // pode conter array, objeto ou string

    public function __construct(bool $sucess = false, string $msg = '', mixed $data = null)
    {
        $this->sucess = $sucess;
        $this->msg = $msg;
        $this->data = $data;
    }

    public function __toString(): string
    {
        return json_encode([
            'sucess' => $this->sucess,
            'msg' => $this->msg,
            'data' => $this->data
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function toJson(): string
    {
        // Método alternativo pra enviar resposta diretamente
        header('Content-Type: application/json; charset=utf-8');
        echo $this;
        exit;
    }
}
