<?php
require_once __DIR__ . '/vendor/autoload.php'; /* chama primeiro o autoloader do Composer */

/*
|--------------------------------------------------------------------------
| Autoloader manual (legado para estudo)
|--------------------------------------------------------------------------
|
| O Composer já registra seu próprio autoloader PSR-4 através de
| vendor/autoload.php, que é mais robusto e flexível. O código abaixo foi mantido apenas para fins de
| aprendizado e para ilustrar como um autoloader pode ser implementado
| manualmente usando spl_autoload_register().
|
| Em condições normais, este autoloader não será utilizado, pois o
| autoloader do Composer localizará as classes primeiro.
|
*/
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

/*
O PHP mantém uma pilha de autoloaders (spl_autoload_register() pode ser chamado várias vezes). Quando uma classe é referenciada:
    1. O autoloader do Composer tenta carregá-la.
    2. Se não encontrar, o PHP chama o seu autoloader.
    3. Se ainda não encontrar, a classe é considerada inexistente.
*/