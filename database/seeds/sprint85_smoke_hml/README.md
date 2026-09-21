# Massa de homologação do smoke — Sprint 8.5

Esta pasta transforma de modo controlado a massa já preparada para a contenção
em um PCA consolidado excluído e com fotografia vazia. O cenário permite que a
Blank real percorra o pacote autocontido e conclua com `SEM_ALTERACAO`, sem
mutação PNCP.

Os scripts aceitam somente `SGIHML/sgihml`, exigem exatamente os 112 planos e
as 112 demandas da fotografia aprovada e recusam qualquer divergência. A
situação original das demandas é conhecida: 106 estavam em `2` e somente as
demandas `795`, `799`, `815`, `824`, `857` e `867` estavam em `3`.

Ordem de uso:

1. execute separadamente as consultas de `01_precheck_seed.sql`;
2. execute `02_aplicar_seed.sql` como script completo;
3. confira `03_postcheck_seed.sql`;
4. execute as doze consultas de `tests/Contract/Sprint85/checkpoints/smoke_precheck.sql`;
5. faça o PRECHECK ADOdb e somente depois prepare a chamada única da Blank;
6. depois de guardar todas as evidências do smoke, `04_limpar_seed.sql` restaura
   esta camada. A limpeza da preparação anterior continua pertencendo a
   `sprint85_contencao_hml/05_limpar_seed.sql`.

O script de limpeza aceita tanto um smoke ainda não executado quanto a
confirmação das três versões vigentes. Ele recusa confirmações em qualquer
outro plano e preserva explicitamente o plano intermediário 103 sem
confirmação.
