Depois de fixarmos o hash canônico:

1. implementaremos o runner do gate LOGS;
2. adicionaremos LOGS à mesma Blank 8.5;
3. executaremos o evento sintético com mais de 4.000 bytes;
4. confirmaremos a persistência integral por leitura;
5. pausaremos ao concluir o Microciclo 6.

---

eu tenho 3 ambientes de banco de dados / servidor de aplicação: desenvolvimento, homologação e produção.

- no ambiente de desenvolvimento, o scriptcase se conecta ap banco de dados DEV
- no ambiente de homologação, o validaSGI se conecta ao banco de dados de homologação (uma cópia semanal dos dados de produção)
- no ambiente de produção, o SGI se conecta ao banco de dados de produção

O que você recomenda: que eu execute a blank `teste_contracts_sprint85` no scriptcase (ambiente de desenvolvimento) ou no ambiente de homologação (servidor de aplicação validaSGI)?

e o smoke tests no futuro?

---

Com essa nova informação, recomendo que os gates reais da Sprint 8.5 sejam executados no validaSGI, conectado ao banco de homologação. O Scriptcase de desenvolvimento deve ser usado para construir, compilar e ensaiar a Blank.

| Ambiente | Uso recomendado |
| --- | --- |
| Scriptcase + banco DEV | Desenvolvimento, depuração, compilação e ensaio da Blank |
| validaSGI + banco de homologação | PRECHECK oficial, gate de logs, contenção e smoke test |
| SGI + banco de produção | Nenhum gate da Sprint 8.5; produção pertence à Fase 9 |

Há duas dimensões diferentes:

- $this->Db vem do servidor que executa a Blank. No Scriptcase DEV, consulta o banco DEV; no validaSGI, consulta homologação.
- O perfil externo determina qual PNCP será usado. perfil-dev.php possuir ambiente = HOMOLOGACAO significa “PNCP de homologação”; isso não muda o banco Oracle conectado pelo Scriptcase.

Portanto, uma Blank executada no Scriptcase DEV com perfil PNCP de homologação ainda usa o Oracle DEV.
Você pode manter um único código versionado para teste_contracts_sprint85:

1. criar e compilar no Scriptcase;
2. opcionalmente ensaiar no ambiente DEV;
3. publicar essa aplicação no validaSGI;
4. executar ali os gates oficiais com uma configuração externa própria da homologação.

O smoke futuro deve ocorrer obrigatoriamente no validaSGI, porque ele precisa comprovar em conjunto:

- PHP 8.1 do servidor de homologação;
- pacote autocontido instalado;
- autoload dentro do pacote;
- Blank real PCA_PUBLICAR_B;
- conexão emprestada com o Oracle de homologação;
- sc_log_add e CLOB reais;
- comportamento da interface instalada.

Um smoke no desenvolvimento pode encontrar erros antecipadamente, mas não cumpre o critério da sprint.

Existe uma consequência: a configuração atual está presa ao desenvolvimento: `'server_name' => 'dev.semaepiracicaba.sp.gov.br',`

E a fotografia que analisamos só pode ser usada como checkpoint oficial se tiver sido extraída do banco de homologação. Como esse banco recebe cópias semanais da produção, não podemos presumir que ele possua os planos 99–104, o PCA 36 e a mesma auditoria criada no DEV.

Antes de executar a Blank no validaSGI, precisamos:

1. confirmar de qual banco veio sprint85_fotografia_oracle_microciclo6.json;
2. preparar configuração externa específica para validasgi.semaepiracicaba.sp.gov.br;
3. repetir as 12 consultas no banco de homologação;
4. verificar se existe massa adequada;
5. gerar o hash canônico ADOdb no validaSGI;
6. somente então liberar LOGS.

---

O próximo passo é executar as mesmas 12 consultas no banco de homologação, por enquanto apenas como descoberta somente leitura. Você não precisa alterar as consultas. Podemos usar o roteiro atual para conhecer o estado das UOs 1 e 3 em 2026, mas suas cardinalidades comentadas pertencem ao DEV e não devem ser tomadas como expectativa da homologação.

Depois do resultado, faremos:

1. verificar se 2026 e as UOs 1 e 3 oferecem uma massa segura;
2. identificar os registros que precisam ser preservados;
3. criar um checkpoint específico de homologação, com valores e cardinalidades reais;
4. criar a configuração externa do validaSGI com: `'server_name' => 'validasgi.semaepiracicaba.sp.gov.br',`
5. publicar a nova Blank no validaSGI;
6. executar o PRECHECK automático para obter o hash ADOdb;
7. liberar LOGS somente depois desse hash.