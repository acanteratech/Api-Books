<?php

spl_autoload_register(function ($className) {
    $className = str_replace("App\\", "", $className);
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $className) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

function loadConfig($configFile) {
    $file = __DIR__ . '/config/' . $configFile . '.php';
    if (file_exists($file)) {
        return require $file;
    }

    throw new Exception("Archivo de configuración no encontrado: $configFile");
}
