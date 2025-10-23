<?php
spl_autoload_register(function ($class) {
    // Caminho base do projeto (raiz)
    $baseDir = __DIR__ . '/';

    // Mapeia namespaces ou pastas simples
    $paths = [
        $baseDir . 'models/',     // pasta das classes
      //  $baseDir . 'app/',       // se tiver classes dentro de app/
    ];

    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
