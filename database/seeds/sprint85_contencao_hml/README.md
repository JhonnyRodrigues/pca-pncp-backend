# Massa de homologação da contenção — Sprint 8.5

Esta pasta prepara uma massa descartável para os Microciclos 7 e 8 depois que
a replicação semanal de produção substituiu a fotografia anterior do validaSGI.

## Limite de segurança

Os scripts desta pasta são exclusivos do banco `SGIHML`. Eles nunca devem ser
executados em produção. A replicação semanal pode apagar a massa e, nesse caso,
todo o procedimento deve recomeçar pelo diagnóstico.

Não se deve transformar a fotografia corrente em checkpoint apenas alterando
o hash. Também não se deve apagar ou modificar os planos trazidos de produção
para abrir espaço para o gate.

## Preparação em etapas

1. Execute `01_diagnostico_pre_seed.sql`, somente leitura, e confira todas as
   seções.
2. Para o gate de contenção, é permitido aproveitar a fotografia replicada
   corrente, desde que o checkpoint inclua todas as UOs participantes do modo
   consolidado. Na fotografia de 21/09/2026, são 3, 94 e 149; não é seguro
   excluir a UO 149 apenas do checkpoint.
3. Confira `02_precheck_seed.sql`. Se todas as cardinalidades coincidirem,
   `03_aplicar_seed.sql` altera somente estratégia/UASG dos 112 planos de 2026
   e cria os dois controles técnicos. Qualquer diferença causa rollback.
4. Para o smoke, a fotografia corrente não substitui a massa vazia com PCA
   excluído. Depois da exportação completa e da aprovação dessa fotografia, a
   camada adicional fica em `database/seeds/sprint85_smoke_hml/`.
5. Depois do seed, execute `04_postcheck_seed.sql` e as doze consultas do gate.
   `05_limpar_seed.sql` remove somente a preparação se os 112 planos e os dois
   controles ainda estiverem exatamente no estado produzido pelo seed.
6. Somente após revisar e autorizar cada seed, execute-o em uma transação
   controlada, confira o post-check e registre a aplicação.
7. Atualize a configuração externa, materialize novamente as doze consultas
   do gate e execute um novo PRECHECK. `CONTENCAO` permanece proibida até lá.

Os scripts recusam qualquer banco diferente de `SGIHML` e qualquer alteração
na fotografia replicada que não corresponda às cardinalidades aprovadas.

## Arquivos

- `01_diagnostico_pre_seed.sql`: investigação somente leitura mais ampla;
- `02_precheck_seed.sql`: fotografia exata imediatamente anterior ao DML;
- `03_aplicar_seed.sql`: DML mínimo, com guardas, commit e rollback;
- `04_postcheck_seed.sql`: comprovação independente do estado preparado;
- `05_limpar_seed.sql`: reversão recusada diante de qualquer divergência.
- `06_postcheck_limpeza.sql`: comprova por seis consultas somente leitura que
  projeção, controles, demandas, plano intermediário e auditoria voltaram ao
  estado anterior aos gates.
