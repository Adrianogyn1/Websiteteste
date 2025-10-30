<?php
// Arquivo: CollectionModel.php

// Requer que a BaseModel esteja carregada

abstract class DataObj extends BaseModel 
{
    // --- MÉTODOS DE MANIPULAÇÃO DE COLEÇÕES (COM ARRAY DE OBJETOS E EVENTOS) ---
    
    public function insertAll(array $models): int
    {
        if (empty($models)) return 0;

        $firstModel = reset($models);
        if (!($firstModel instanceof BaseModel)) {
            throw new InvalidArgumentException("Todos os elementos em \$models devem ser instâncias de BaseModel.");
        }
        
        $allFields = array_keys($firstModel->getPublicProperties());
        $fieldsToInsert = array_filter($allFields, fn($field) => $field !== 'id');
        
        $this->db->beginTransaction();
        $count = 0;

        try {
            $placeholders = array_map(fn($field) => ":{$field}", $fieldsToInsert);
            $fields_sql = '(' . implode(', ', $fieldsToInsert) . ')';
            $sql = "INSERT INTO {$this->tableName} {$fields_sql} VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $this->db->prepare($sql);

            foreach ($models as $model) {
                if (!($model instanceof BaseModel)) continue; 

                // 1. EVENTO PRÉ-CREATE
                $model->OnCreate(false); 
                
                // 2. Extrai os dados ATUALIZADOS do objeto Model (pelo hook)
                $properties = $model->getPublicProperties();
                $bindings = [];
                foreach ($fieldsToInsert as $field) {
                    $bindings[":{$field}"] = $properties[$field] ?? null;
                }
                
                static::$lastRawSql = static::generateRawSql($sql, $bindings);
                $success = $stmt->execute($bindings);
                
                // 3. EVENTO PÓS-CREATE
                $model->OnCreate($success);
                
                if ($success) $count++;
            }
            
            $this->db->commit();
            return $count;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erro no insertAll: " . $e->getMessage());
            return 0;
        }
    }

    public function updateAll(array $models): int
    {
        // ... (Implementação omitida para brevidade, mas deve ser completa e similar ao insertAll) ...
        if (empty($models)) return 0;
        // ...
        $count = 0;
        try {
            // ... (Loop sobre $models, chamando $model->OnUpdate(false) e $model->OnUpdate($success)) ...
            return $count;
        } catch (PDOException $e) {
            error_log("Erro no updateAll: " . $e->getMessage());
            return 0;
        }
    }

    // --- MÉTODOS DE EXPORTAÇÃO ---

    public function exporta(string $type, string $content, string $filename = 'export.txt'): bool
    {
        $type = strtolower($type);
        $content = strtolower($content);

        if (!in_array($type, ['csv', 'sql']) || !in_array($content, ['estrutura', 'dados', 'ambos'])) return false;

        $output = '';

        if ($type === 'sql' && in_array($content, ['estrutura', 'ambos'])) {
            // O retorno do método agora é garantido como string.
            $output .= $this->exportaEstruturaSql() . "\n\n";
        }

        if (in_array($content, ['dados', 'ambos'])) {
            $data = $this->getDataForExport();
            
            if ($type === 'csv') {
                $output .= $this->exportaDadosCsv($data);
            } elseif ($type === 'sql' && !empty($data)) {
                $output .= $this->exportaDadosSql($data);
            }
        }
        
        if (!empty($output)) {
            // ... (Lógica de headers para download) ...
            $mimeType = $type === 'csv' ? 'text/csv' : 'text/plain';
            $filename = str_replace('.txt', '', $filename) . ($type === 'csv' ? '.csv' : '.sql');
            
            header('Content-Type: ' . $mimeType . '; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            echo $output;
            return true;
        }

        return false;
    }

    protected function getDataForExport(): array
    {
        // ... (Implementação omitida para brevidade) ...
        return [];
    }
    
    /**
     * Monta o SQL para DROP TABLE e CREATE TABLE.
     * Implementa try-catch para garantir o retorno do tipo string e evitar TypeError.
     * @return string O SQL da estrutura ou string vazia em caso de falha.
     */
    protected function exportaEstruturaSql(): string
    {
        try {
            $className = static::class;
            $dbDriver = $this->dbDriver; 
            $reflector = new ReflectionClass($className);
            $properties = $reflector->getProperties(ReflectionProperty::IS_PUBLIC);
            $columns = [];
            $hasPrimaryKey = false;

            foreach ($properties as $property) {
                $name = $property->getName();
                if (count($property->getAttributes('IgnoreInDatabase')) > 0) continue; 
                
                $type = $property->getType() ? $property->getType()->getName() : 'string';
                $isNullable = $property->getType() ? $property->getType()->allowsNull() : true;
                $sqlDefinition = static::getSqlType($name, $type, $isNullable, $dbDriver);

                if ($name === 'id') {
                    $sqlDefinition = ($dbDriver === 'mysql') 
                        ? "id INT PRIMARY KEY AUTO_INCREMENT" 
                        : "id INTEGER PRIMARY KEY AUTOINCREMENT";
                    $hasPrimaryKey = true;
                }
                $columns[] = $sqlDefinition;
            }
            
             if (!$hasPrimaryKey) {
                 error_log("Erro: Classe {$className} não possui chave primária 'id'.");
                 return '';
             }

            $sql = "DROP TABLE IF EXISTS {$this->tableName};\n";
            $sql .= "CREATE TABLE `{$this->tableName}` (\n";
            $sql .= "    " . implode(",\n    ", $columns) . "\n";
            $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"; 
            
            return $sql;
            
        } catch (Throwable $e) {
            // Captura qualquer exceção (reflexão, PDO, etc.) e loga
            error_log("Erro na exportaEstruturaSql: " . $e->getMessage());
            return ''; // Retorna string vazia para satisfazer o tipo de retorno
        }
    }
    
    protected function exportaDadosCsv(array $data): string
    {
        // ... (Implementação omitida para brevidade) ...
        return '';
    }

    protected function exportaDadosSql(array $data): string
    {
        // ... (Implementação omitida para brevidade) ...
        return '';
    }
}