<?php

declare(strict_types=1);

/**
 * Copie para registro.php fora do repositório, da release e do webroot.
 * Substitua o hostname pelo SERVER_NAME literal, sem normalização.
 * Em homologação, use perfil-homologacao.php no caminho abaixo.
 * Este modelo não é carregado automaticamente pela aplicação.
 */
return [
    'SERVER_NAME_EXATO_DE_PRODUCAO' => __DIR__ . '/perfil-producao.php',
];
