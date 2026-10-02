# Investigação para encerramento da Fase 5 — Sprint 5.10

**Classificação documental:** Histórico  
**Data de referência:** 28/08/2026  
**Autoridade/Escopo:** inventário histórico e procedimento investigativo; não cria regra,
decisão física ou contrato

## Nota de encerramento

Em 31/08/2026, `RN-PUB-003` foi revisada para usar a justificativa
institucional fixa `Exclusão integral do PCA por ausência de demandas vigentes
no SGI.`. A fonte histórica, a ordenação do último cancelamento e o adapter
investigado deixaram de ser requisitos vigentes.

Por isso, a execução Oracle real descrita abaixo foi cancelada antes de ocorrer.
O conteúdo restante preserva o inventário e o procedimento disponíveis em
28/08/2026 como registro histórico; não representa mais uma pendência normativa
nem autoriza implementar o adapter. A Fase 5 somente poderá ser encerrada após
a implementação simplificada, a remoção da capacidade obsoleta e o gate final
aprovado nos microciclos posteriores.

## 1. Resultado do inventário

A Sprint 5.9 comprovou os adapters concretos então existentes no Oracle real e
no PNCP de treinamento. O inventário das Ports encontrou uma única lacuna de
Infrastructure: `EvidenciaCancelamentoHomologadoPort` não possui adapter
concreto. Não há no código ou na documentação vigente um mapeamento físico
confirmado para recuperar o cancelamento homologado que tornou vazia a unidade.

O contrato vigente já distingue evidência disponível, ausente, conflitante e
falha sistêmica. A Application também já verifica se a identidade retornada
pertence à unidade processada. Portanto, a investigação não deve alterar a Port
nem substituir a fonte ausente por inferência sobre o estado corrente das
Demandas.

| Critério da Fase 5 | Evidência atual | Estado |
| --- | --- | --- |
| Adapters Oracle das Sprints 5.2–5.4 | Testes controlados e gate Scriptcase real da Sprint 5.9 | Confirmado |
| Adapters PNCP das Sprints 5.5–5.8 | Testes controlados e ciclo real no treinamento | Confirmado |
| Sessão compartilhada, transação, rollback e contenção | Gate Oracle nos quatro modos contratuais | Confirmado |
| TLS, sessão opaca, paginação, escritas e limpeza | Gate PNCP real | Confirmado |
| Ausência de regra de negócio nos adapters existentes | Fronteiras e testes das Sprints 5.2–5.9 | Confirmado no escopo existente |
| Adapter de evidência de cancelamento homologado | Nenhuma implementação ou fonte física confirmada | Bloqueante |
| DI-008 | Falta mapear e provar a nova fonte Oracle | Condicional |
| DI-009 | Gates reais concluídos; incompatibilidade futura reabre a decisão | Aceita |
| DI-010 | Composition Root e integração pública pertencem às fases posteriores | Fora do encerramento da Fase 5 |

## 2. Evidência externa necessária

Antes de desenhar o adapter, o DBA deve fornecer ou confirmar:

- DDL, tipos, tamanhos, nulabilidade, chaves, relacionamentos e índices das
  fontes candidatas;
- evento e estado que tornam o cancelamento efetivamente homologado;
- fonte da justificativa integral, sem concatenação, inferência ou truncamento;
- vínculo entre cancelamento, Demanda, versão do Plano, UO e exercício;
- ordenação total do histórico e chave estável de desempate;
- comportamento físico quando eventos concorrentes ou empatados não permitem
  determinar um último cancelamento inequívoco;
- forma de demonstrar que o evento tornou vazia a UO individual ou a união das
  UOs da unidade consolidada.

A massa deve ser criada pelo fluxo oficial do SGI e reservar casos individual,
consolidado e sem evidência. O backend não realizará DML para prepará-la.

## 3. Probe read-only

A suíte `tests/Contract/Sprint510/` usa `PCA_SPRINT510_CONFIG_FILE`, que deve
apontar para configuração externa ao repositório e ao webroot. Cada check
declara externamente um único `SELECT`, seus binds, aliases e expectativas. O
preflight exige confirmação explícita da fonte física, da massa dedicada e do
fluxo oficial e recusa autorização de escrita, comentários SQL, múltiplas
instruções, DML, DDL, controle transacional ou `FOR UPDATE`.

O entrypoint Scriptcase é:

```php
executar_investigacao_evidencia_cancelamento_oracle_sprint_510(
    $db,
    $login,
    $mode
);
```

Os modos são `metadata`, `individual`, `consolidado` e `ausente`. O runner usa
uma única `ConexaoAdodbEmprestada`, confere a mesma sessão antes e depois das
consultas e envolve o ADOdb em uma guarda que impede transação, comandos e
`Close()` antes que alcancem a conexão do Scriptcase.

O relatório registra apenas modo, runtime, cardinalidade, número de aliases,
resultado booleano das verificações, preservação da sessão e hash sanitizado.
SQL, DDL, nomes físicos, binds, valores esperados, justificativas, identificadores
e credenciais não são registrados. O JSON integral deve permanecer externo.

A validação controlada aprovou 17 testes e 46 asserções. As regressões também
aprovaram 476 testes/1.804 asserções dos adapters, 54/149 dos contratos da
Sprint 5.9, 24/51 dos contratos legados e 815/2.949 da suíte padrão. O autoload
PSR-4 estrito gerou 1.745 classes. Os arquivos PHP passaram no lint, o catálogo
contém todos os caminhos de `src/` e `tests/`, e `git diff --check` não apontou
erro. As execuções usaram PHP 8.4.7.

## 4. Checkpoint original

Em 28/08/2026, a investigação real ainda não havia sido executada e a fonte
permanecia como condição para a Sprint 5.10 e a Fase 5. Esse checkpoint foi
superado pela revisão normativa registrada na nota de encerramento acima. Não
houve execução Oracle, desenho de mapeamento ou implementação de adapter para a
capacidade posteriormente removida.
