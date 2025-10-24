<?php

class ApiMessage
{
    
    public bool $sucess;
    public string $msg;
    public string $data;
    
    public function __toString(): string
    {
        return json_encode([
            'sucess' => $this->sucess,
            'msg' => $this->msg,
            'data' => $this->data
        ], JSON_PRETTY_PRINT);
    }
}
    ?>