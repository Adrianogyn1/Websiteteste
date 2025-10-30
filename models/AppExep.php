<?php

// Usamos \RuntimeException como base, pois é um erro que ocorre
// durante a execução e geralmente pode ser tratado (logged, etc.).

class AppError extends \RuntimeException 
{
    /**
     * Construtor para a exceção de conversão de AppError.
     * @param string AppError O nome da classe  que deveria ser instanciada.
     * @param int $code O código do erro (opcional).
     * @param \Throwable $previous Exceção anterior (opcional).
     */
    public function __construct(
        
        string $msg, 
        int $code = 0, 
        \Throwable $previous = null
    ) {
      //  $valueType = gettype($value);
        $message = AppError; //" '{msg}' (Tipo: {$valueType}) para a classe Enum '{$enumClass}'. O valor não é um caso BackedEnum válido.";
        
        // Chama o construtor da classe pai (\RuntimeException)
        parent::__construct($message, $code, $previous);
    }
}