# Migrações do banco de dados

Esta pasta guarda alterações de estrutura e dados de referência necessários
para implantar o backend. Ela não contém massa transacional de PCA, Demandas,
auditorias ou credenciais.

## Organização

- `migrations/`: alterações versionadas, aplicadas em ordem;
- `checks/`: consultas somente leitura executadas antes e depois da migração;
- `seeds/`: reservado para massa descartável e identificada de DEV ou HML;"
- `deployments/`: registro manual provisório do estado das migrações por
  ambiente."

O projeto ainda não adotou um executor como Flyway ou Liquibase. Até essa
decisão, cada aplicação deve ser registrada em `deployments/` e, quando
existir, também no procedimento institucional de mudança do ambiente, junto
com a versão do arquivo e seu SHA-256. Uma migração aplicada não deve ser
editada; uma correção exige outro arquivo com versão posterior.

## Execução controlada

Para cada migração:

1. execute o `precheck` correspondente;
2. interrompa diante de qualquer estado divergente;
3. se todo o estado já estiver conforme, não execute DML: registre a migração
   como requisito já satisfeito no ambiente;
4. se houver estado ausente e nenhum divergente, obtenha a autorização do
   ambiente e execute a migração como script completo;
5. quando houver execução, rode o `postcheck` e confira todas as condições
   esperadas;
6. registre ambiente, data, executor, versão, SHA-256 e uma das situações:
   `APLICADA` ou `REQUISITO_JA_SATISFEITO`.

Os scripts não são executados pelo backend, pela Blank ou durante uma
requisição. Eles também não integram o pacote autocontido da aplicação.

Ainda não existe uma baseline versionada do DDL legado. Ela só deve ser criada
depois de inventariar e comparar as estruturas efetivas de DEV, HML e PROD.
