<?php

spl_autoload_register(function ($classe) {
    $namespaceBase = 'Semae\\Pca\\';
    $diretorioBase = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
    $classe = ltrim($classe, '\\');
    
    # se a classe não pertence ao namespace base, não faz nada
    if (strncmp($namespaceBase, $classe, strlen($namespaceBase)) !== 0) {
        return;
    }

    # remove o namespace base da classe para obter o caminho relativo
    $classeRelativa = substr($classe, strlen($namespaceBase));

    # substitui as barras invertidas do namespace por barras normais para formar o caminho do arquivo
    $classeRelativa = str_replace('\\', DIRECTORY_SEPARATOR, $classeRelativa);

    # monta o caminho completo do arquivo da classe com extensão do PHP
    $arquivo = $diretorioBase . $classeRelativa . '.php';

    // verifica se o arquivo existe e o inclui, caso contrário registra erro e retorna false
    if (file_exists($arquivo)) {
        require_once $arquivo;
    } else {
        error_log("Autoload backend PCA falhou. Classe $classe não encontrada em $arquivo");
        return false;
    }
});