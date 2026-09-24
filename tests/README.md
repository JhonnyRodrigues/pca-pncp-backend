# Testes e contratos

Esta pasta reúne testes locais, integrações controladas e contratos opt-in.
Estar dentro de `tests/` não significa, por si só, que uma execução pode acessar
um recurso real: o comando e a configuração PHPUnit determinam esse limite.

## Suíte padrão

```bash
composer tests
```

A suíte padrão executa `Domain`, `Application`, `Infrastructure`,
`Composition`, `Presentation` e `Integration`. Ela exclui `tests/Contract` e
não deve acessar Oracle, PNCP ou Scriptcase reais.

As pastas das camadas espelham o código de produção. `tests/Support` contém
objetos reutilizáveis; `tests/Integration` verifica colaborações usando
fronteiras controladas.

## Contratos e gates

`tests/Contract` preserva contratos locais e gates de sprints anteriores.
Alguns comandos são seguros e locais; outros são opt-in e podem depender de
configuração externa, ambiente autorizado e recursos reais.

Antes de executar qualquer preflight, grupo `live`, runner manual ou Blank de
teste:

1. consulte o
   [catálogo de comandos Composer](../docs/vigentes/apoio/catalogo_de_comandos_composer.md);
2. confirme o ambiente, os consentimentos e o plano vigente;
3. nunca reutilize configuração real em um teste local comum;
4. não repita um gate real já encerrado apenas para obter nova evidência.

O comando local específico da Sprint 9.4 é:

```bash
composer contract:sprint94:tests
```

## Convenções

- teste comportamento observável, regra ou contrato, não detalhes internos
  triviais;
- use `Red -> Green -> Refactor` em passos pequenos;
- mantenha credenciais, tokens, perfis reais e respostas brutas fora do Git;
- declare somente validações efetivamente executadas;
- testes que possam realizar I/O real devem permanecer separados e opt-in.
