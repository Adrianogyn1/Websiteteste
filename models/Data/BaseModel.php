<?php
// Arquivo: BaseModel.php

// Dependências externas presumidas:
// require_once 'Database.php';
// require_once 'IgnoreInDatabase.php';
// E todas as classes Enum personalizadas (ex: GameType.php)

abstract class BaseModel
{
    // --- Eventos (Hooks) ---
    protected function OnCreate(bool $sucess): bool { return true; } 
    protected function OnUpdate(bool $sucess): bool { return true; }
    
    // --- Configuração ---
    protected string $tableName = ''; 
    protected \PDO $db; 
    protected string $dbDriver = ''; 

    protected static ?string $lastRawSql = null;
    public ?int $id = null;
    
    public function __construct()
    {
        try {
            // Usa \PDO e \Database com referência global
            $this->db = \Database::instance()->getPdo();
            $this->dbDriver = $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME); 
        } catch (\Throwable $e) { // \Throwable também é referenciado globalmente
            throw new \Exception("Falha ao obter conexão PDO: " . $e->getMessage());
        }
        
        if (empty($this->tableName)) {
        $className = static::class;
        $shortClassName = (new \ReflectionClass($className))->getShortName();
        $baseName = \str_ireplace('Model', '', $shortClassName); 
        $snakeCase = \strtolower(\preg_replace('/(?<!^)[A-Z]/', '_$0', $baseName));
        
        $this->tableName = $snakeCase; 

        if (!\str_ends_with($this->tableName, 's')) {
             $this->tableName;// .= 's';
        }
    }
    }

    // ----------------------------------------------------------------------
    // --- MÉTODOS DE DEBUG (SQL RAW) ---
    // ----------------------------------------------------------------------
    
    public static function getLastRawSql(): ?string
    {
        return static::$lastRawSql;
    }

    protected static function generateRawSql(string $sql, array $bindings): string
    {
        $db = \Database::instance()->getPdo();
        $rawSql = $sql;
        
        foreach ($bindings as $placeholder => $value) {
            if (str_starts_with($placeholder, ':')) {
                if ($value === null) {
                    $quotedValue = 'NULL';
                } 
                // Usa \BackedEnum para referenciar a interface globalmente
                else if ($value instanceof \BackedEnum) {
                     $quotedValue = $db->quote((string)$value->value);
                }
                else if (is_object($value) && !method_exists($value, '__toString')) {
                    $quotedValue = $db->quote('[OBJECT CONVERSION FAILED]'); 
                }
                else {
                    $quotedValue = $db->quote((string)$value); 
                }
                $rawSql = str_replace($placeholder, $quotedValue, $rawSql); 
            }
        }
        
        return $rawSql;
    }

    // ----------------------------------------------------------------------
    // --- MÉTODOS DE REFLEXÃO E POPULAÇÃO (Com Tratamento de Enum e DateTime) ---
    // ----------------------------------------------------------------------

    protected function getPublicProperties(): array
    {
        // Usa \ReflectionClass com referência global
        $reflector = new \ReflectionClass($this);
        $properties = [];
        
        foreach ($reflector->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            
            // Usa \IgnoreInDatabase (se for um Attribute no namespace global)
            if (count($property->getAttributes('IgnoreInDatabase')) > 0) continue; 
            
            $name = $property->getName();
            $value = $property->getValue($this);
            
            // Referencia as interfaces globais
            if ($value instanceof \BackedEnum) {
                $properties[$name] = $value->value; 
            }
            else if ($value instanceof \UnitEnum) {
                $properties[$name] = $value->name; 
            }
            // Usa \DateTime com referência global
            else if ($value instanceof \DateTime) {
                $properties[$name] = $value->format('Y-m-d H:i:s');
            } 
            else {
                $properties[$name] = $value;
            }
        }

        return $properties;
    }
    
    public function populate(array $dados): self
    {
        $reflector = new \ReflectionClass($this);

        foreach ($dados as $chave => $valor) {
            $chave = strtolower($chave);
            
            if (!$reflector->hasProperty($chave)) continue;
            
            $property = $reflector->getProperty($chave);
            if (!$property->isPublic()) continue;
            
            $type = $property->getType();
            $phpType = $type ? $type->getName() : null;
            $isNullable = $type ? $type->allowsNull() : true;

            if ($phpType === 'DateTime' && is_string($valor)) {
                try {
                    $this->$chave = new \DateTime($valor);
                } catch (\Exception $e) {
                    $this->$chave = $isNullable ? null : $valor; 
                }
            } 
            // Usa \enum_exists e referências globais para Enums
            else if ($phpType && \enum_exists($phpType) && $valor !== null) {
                if (\is_subclass_of($phpType, \BackedEnum::class)) {
                    try {
                        // $phpType é o nome da classe Enum (Ex: 'GameType')
                        $this->$chave = $phpType::from($valor); 
                    } catch (\ValueError $e) {
                        $this->$chave = $isNullable ? null : $valor;
                    }
                } 
            }
            else {
                $this->$chave = $valor;
            }
        }
        return $this;
    }
    
    public function toArray(): array
    {
        return $this->getPublicProperties();
    }
    
    // ----------------------------------------------------------------------
    // --- CRIAÇÃO DA TABELA (ESTÁTICO) ---
    // ----------------------------------------------------------------------

    public static function createTable(): bool
    {
        $className = static::class;
        $db = \Database::instance()->getPdo(); 
        $instance = new $className();
        $tableName = $instance->tableName;
        $dbDriver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);

        $reflector = new \ReflectionClass($className);
        $properties = $reflector->getProperties(\ReflectionProperty::IS_PUBLIC);
        $columns = [];
        $hasPrimaryKey = false;

        foreach ($properties as $property) {
            $name = $property->getName();
            if (count($property->getAttributes('IgnoreInDatabase')) > 0) continue; 
            
            $type = $property->getType() ? $property->getType()->getName() : 'string';
            $isNullable = $property->getType() ? $property->getType()->allowsNull() : true;
            
            $sqlDefinition = self::getSqlType($name, $type, $isNullable, $dbDriver);

            if ($name === 'id') {
                $sqlDefinition = ($dbDriver === 'mysql') 
                    ? "id INT PRIMARY KEY AUTO_INCREMENT" 
                    : "id INTEGER PRIMARY KEY AUTOINCREMENT";
                $hasPrimaryKey = true;
            }
            $columns[] = $sqlDefinition;
        }
        
        if (!$hasPrimaryKey) {
             throw new \Exception("A classe {$className} deve ter uma propriedade pública \$id.");
        }

        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (" . implode(', ', $columns) . ")";
        
        try {
            $db->exec($sql);
            return true;
        } catch (\PDOException $e) {
            error_log("Erro ao criar tabela {$tableName}: " . $e->getMessage()); 
            return false;
        }
    }

    protected static function getSqlType(string $propertyName, string $phpType, bool $isNullable, string $dbDriver): string
    {
        $phpTypeLower = strtolower($phpType);
        
        // Verifica se é Enum e qual o tipo de dado subjacente
        if (\enum_exists($phpType) && \is_subclass_of($phpType, \BackedEnum::class)) {
            // Usa \ReflectionEnum para obter o tipo de suporte
            $backingType = (new \ReflectionEnum($phpType))->getBackingType()->getName();
            $phpTypeLower = strtolower($backingType);
        }

        $sqlType = match ($phpTypeLower) {
            'int' => ($dbDriver === 'mysql') ? 'INT' : 'INTEGER',
            'bool' => ($dbDriver === 'mysql') ? 'TINYINT(1)' : 'INTEGER',
            'float' => 'REAL',
            'datetime' => ($dbDriver === 'mysql') ? 'DATETIME' : 'TEXT',
            default => ($dbDriver === 'mysql') ? 'VARCHAR(255)' : 'TEXT',
        };
        
        $nullability = $isNullable ? 'NULL' : 'NOT NULL';
        return "{$propertyName} {$sqlType} {$nullability}";
    }
    
    // ----------------------------------------------------------------------
    // --- MÉTODOS CRUD UNITÁRIO (E FETCH COLLECTION) ---
    // ----------------------------------------------------------------------
    
    public function select(string $campos='*', string $where = '', array $params = []): array
    {
        $sql = "SELECT {$campos} FROM {$this->tableName}";
        if (!empty($where)) {
             $sql .= " WHERE " . $where;
        }
        return static::query($sql, $params);
    }
    
    public static function find(int $id): ?self
    {
        $className = static::class; 
        $db = \Database::instance()->getPdo();
        $instance = new $className();
        
        $sql = "SELECT * FROM {$instance->tableName} WHERE id = :id";
        $bindings = [':id' => $id];
        
        static::$lastRawSql = static::generateRawSql($sql, $bindings);
        
        $stmt = $db->prepare($sql);
        $stmt->execute($bindings);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($data) {
            return $instance->populate($data);
        }
        return null;
    }

    public function save(): bool
    {
        if ($this->id !== null && $this->id > 0) {
            $this->OnUpdate(false); 
            $success = $this->update();
            $this->OnUpdate($success); 
            return $success;
        }
        
        $this->OnCreate(false); 
        $success = $this->create();
        $this->OnCreate($success); 
        return $success;
    }
    
    protected function create(): bool
    {
        $properties = $this->getPublicProperties();
        if (array_key_exists('id', $properties)) unset($properties['id']);
        if (empty($properties)) return false;
        
        $fields = array_keys($properties);
        $placeholders = array_map(fn($field) => ":{$field}", $fields);

        $sql = "INSERT INTO {$this->tableName} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";

        $bindings = [];
        foreach ($properties as $field => $value) {
            $bindings[":{$field}"] = $value;
        }
        
        static::$lastRawSql = static::generateRawSql($sql, $bindings);
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute($bindings);
        
        if ($success) {
            $this->id = (int)$this->db->lastInsertId();
        }

        return $success;
    }
    
    protected function update(): bool
    {
        if ($this->id === null || $this->id === 0) return false;
        
        $properties = $this->getPublicProperties();
        if (array_key_exists('id', $properties)) unset($properties['id']);
        
        $setClauses = [];
        $bindings = [];

        foreach ($properties as $field => $value) {
            $setClauses[] = "{$field} = :{$field}";
            $bindings[":{$field}"] = $value;
        }
        
        if (empty($setClauses)) return true;

        $sql = "UPDATE {$this->tableName} SET " . implode(', ', $setClauses) . " WHERE id = :id";
        $bindings[':id'] = $this->id;

        static::$lastRawSql = static::generateRawSql($sql, $bindings);
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($bindings);
    }
    
    public function delete(): bool
    {
        if ($this->id === null || $this->id === 0) return false;

        $sql = "DELETE FROM {$this->tableName} WHERE id = :id";
        $bindings = [':id' => $this->id];
        
        static::$lastRawSql = static::generateRawSql($sql, $bindings);
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($bindings);
    }
    
    public static function query(string $sql, array $bindings = []): array
    {
        $db = \Database::instance()->getPdo();
        return static::fetchCollection($db, $sql, $bindings);
    }
    
    public static function all(array $options = []): array
    {
        $className = static::class; 
        $instance = new $className();
        $tableName = $instance->tableName;
        $db = \Database::instance()->getPdo();

        $sql = "SELECT * FROM {$tableName}";
        $bindings = [];
        
        // ... (Lógica de WHERE, ORDER BY, LIMIT e OFFSET omitida por brevidade, mas deve ser completa) ...

        return static::fetchCollectionWithIntBinding($db, $sql, $bindings);
    }
    
    protected static function fetchCollection(\PDO $db, string $sql, array $bindings = []): array
    {
        static::$lastRawSql = static::generateRawSql($sql, $bindings);
        $className = static::class;
        
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($bindings);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $collection = [];

            foreach ($results as $data) {
                $collection[] = (new $className())->populate($data); 
            }
            return $collection;
        } catch (\PDOException $e) {
            error_log("Erro na consulta [fetchCollection]: " . $e->getMessage()); 
            return [];
        }
    }
    
    protected static function fetchCollectionWithIntBinding(\PDO $db, string $sql, array $bindings = []): array
    {
        static::$lastRawSql = static::generateRawSql($sql, $bindings);
        $className = static::class;
        
        try {
            $stmt = $db->prepare($sql);
            $limitOffsetBindings = [];
            
            // ... (Lógica de bindValue omitida por brevidade, mas deve ser completa) ...
            
            $stmt->execute();
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $collection = [];

            foreach ($results as $data) {
                $collection[] = (new $className())->populate($data); 
            }
            return $collection;
        } catch (\PDOException $e) {
            error_log("Erro na consulta [fetchCollectionWithIntBinding]: " . $e->getMessage()); 
            return [];
        }
    }
}