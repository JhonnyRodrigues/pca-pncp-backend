# Exemplos didáticos iniciais

**Classificação documental:** Histórico  
**Autoridade/Escopo:** material de aprendizado sem participação na aplicação ativa

Esta pasta preserva exercícios usados no início do projeto para compreender
autoload, classes simples e execução local de testes. Eles foram úteis durante a
aprendizagem, mas não integram o backend PCA/PNCP.

- O [guia histórico de compreensão do backend](guia_de_compreensao_do_backend.md)
preserva a investigação didática realizada quando a camada Application havia
acabado de ser construída. Ele pode conter caminhos e explicações superados e
deve ser confrontado com a documentação vigente.

- `autoload_manual.php` demonstra `spl_autoload_register()` e delegava primeiro
  ao autoloader do Composer.
- `utilities/` contém uma classe elementar, um pequeno script de execução, uma
  configuração experimental e a antiga cópia local do executável PHPUnit.

O projeto ativo usa `vendor/autoload.php`, autoload PSR-4 configurado em
`composer.json` e a suíte definida pelo `phpunit.xml` da raiz. O teste fictício
que apenas verificava `true` e os placeholders vazios de Infrastructure não
possuíam valor pedagógico adicional e foram removidos.

Os antigos scripts `tests:unit`, `tests:integration`, `tests:functional`,
`tests:contract` e `tests:stage` ilustravam categorias possíveis de teste, mas
apontavam para suítes inexistentes e executavam zero testes. Eles foram removidos
do Composer. Essa organização só deverá retornar quando o projeto possuir uma
necessidade real e testes configurados para cada nível.
