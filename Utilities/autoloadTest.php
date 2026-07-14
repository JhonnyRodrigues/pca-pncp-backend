<?php
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/Saudacao.php';

$saudacao = new Saudacao();
echo $saudacao->dizer('Olá! Tudo certo com o autoload.');