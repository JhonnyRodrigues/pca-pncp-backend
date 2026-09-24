# Registro provisório de implantações do banco

Esta pasta registra quais migrações foram aplicadas ou encontradas previamente
satisfeitas em cada ambiente. Ela é provisória enquanto o projeto não possui
uma ferramenta de migração com tabela de histórico no próprio banco.

Não altere arquivos já utilizados em `migrations/` ou `checks/` para registrar
uma execução. Esses arquivos representam o procedimento reproduzível e seus
hashes precisam permanecer estáveis.

Cada registro deve informar:

- ambiente e data;
- versão e SHA-256 da migração;
- executor;
- resultado do precheck;
- situação `APLICADA` ou `REQUISITO_JA_SATISFEITO`;
- se houve DML;
- resultado do post-check, quando houver execução.

Não registre credenciais, strings de conexão, dados pessoais ou conteúdo
transacional do ambiente.

Uma migração ainda não conferida pode ser registrada como
`PENDENTE_PRECHECK`, sempre com DML igual a `Não`. Esse registro deve ser
atualizado somente depois da evidência correspondente.

Quando o precheck aceitar o estado, mas a migração ainda não tiver sido
executada, use `PRECHECK_CONFIRMADO`. Essa situação não significa aplicação e
deve continuar com DML igual a `Não`.

Uma linha `DIVERGENTE` bloqueia a migration. Ela não deve ser interpretada como
estado ausente nem corrigida pelo operador durante a execução do script.
