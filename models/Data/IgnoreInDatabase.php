<?php

// IgnoreInDatabase.php

namespace App\Model\Attributes; // Use um namespace adequado

use Attribute;

/**
 * Atributo usado para marcar propriedades que devem ser ignoradas
 * nas operações de banco de dados (CREATE TABLE, INSERT, UPDATE).
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class IgnoreInDatabase
{
    // A classe não precisa de lógica interna.
}