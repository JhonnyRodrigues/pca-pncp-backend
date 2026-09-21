# Seeds por ambiente

Esta pasta é reservada para massa descartável, identificada e restrita a DEV
ou HML. Um seed deve declarar o ambiente permitido, como localizar os registros
criados e como removê-los com segurança.

Não coloque aqui:

- configurações obrigatórias em todos os ambientes — use `migrations/`;
- cópias de dados pessoais ou transacionais de produção;
- credenciais, tokens ou caminhos externos;
- correções de dados de produção.

Nenhum seed deve ser executado automaticamente durante a inicialização do
backend ou por uma requisição da Blank.

## Seeds em preparação

- `sprint85_contencao_hml/`: preparação reproduzível da massa descartável dos
  gates de contenção e smoke depois da replicação semanal do validaSGI.
