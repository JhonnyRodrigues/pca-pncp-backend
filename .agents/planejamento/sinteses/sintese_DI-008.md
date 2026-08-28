Fica formalmente decidido na DI-008 — Mecanismo Oracle:
Usar PAC_PLANOS como projeção operacional corrente da integração PNCP, preservando seu papel de registro dos Planos homologados.
O motivo é que a DI-008 já respondeu as perguntas arquiteturais mais importantes:

✅ Como reconstruir a fotografia do Plano.

✅ Como identificar a versão vigente.

✅ Quais Demandas participam da fotografia.

✅ Como resolver a UASG.

✅ Como congelar a estratégia.

✅ Como representar o estado operacional.

✅ Onde persistir os metadados da integração.

✅ Como fazer exclusividade Oracle.

✅ Quando bloquear por inconsistência.

✅ Como modelar a projeção operacional em PAC_PLANOS.

Regras de negócio
    • A fotografia vigente inclui Demandas:
        ◦ 2 — HOMOLOGADA;
        ◦ 3 — COMPRADA;
        ◦ 5 — PUBLICADA.
    • Não participam:
        ◦ 1 — PLANEJADA;
        ◦ 4 — CANCELADA.
    • As transições HOMOLOGADA → COMPRADA → PUBLICADA não removem itens do PCA nem geram exclusão no PNCP.
    • A fotografia reúne Demandas de todas as versões de origem da mesma UO e exercício; não se limita ao FK_PLANO da última versão.
    • O Plano vigente pode possuir zero Demandas ativas, permitindo decidir pela exclusão integral do PCA.
    • A identidade lógica permanece:
        ◦ Unidade Orçamentária;
        ◦ ano de execução.
    • Como PAC_PLANOS só recebe registros durante a homologação e DATA_HOMOLOGACAO é NOT NULL DEFAULT SYSDATE, a versão vigente é fisicamente a maior VERSAO da UO/exercício.
    • A UASG é resolvida por:
        ◦ PAC_PLANOS.FK_UNIDADE_ORCAMENTARIA;
        ◦ PAC_UNIDADES_ORCAMENTARIAS.FK_LOTACAO.
    • Antes da adoção da estratégia, deve existir exatamente uma UO ativa, com ATIVA = 'S' e UASG preenchida. Ausência, duplicidade ou UASG nula bloqueiam a execução.
Estado operacional
    • Estados persistidos:
        ◦ NAO_PUBLICADO;
        ◦ PUBLICADO;
        ◦ EXCLUIDO.
    • Codificação Oracle por texto semântico, não por N/P/E.
    • Invariantes:
        ◦ NAO_PUBLICADO: sequencialPca nulo;
        ◦ PUBLICADO: sequencialPca positivo;
        ◦ EXCLUIDO: preserva o último sequencial positivo.
    • Recriação após exclusão gera novo sequencialPca; o anterior permanece apenas no histórico.
    • PAC_PLANOS é a fonte canônica exclusiva do sequencial operacional.
    • O eventual campo equivalente em PAC_DEMANDA não será lido, gravado, comparado nem usado como fallback.
Projeção em PAC_PLANOS
A projeção operacional mínima terá quatro informações:
    • modo anual adotado;
    • UASG externa adotada;
    • estado operacional do PCA;
    • sequencialPca.
Também ficou decidido:
    • O modo será persistido semanticamente como:
        ◦ INDIVIDUAL;
        ◦ CONSOLIDADO.
    • DATA_PUBLICACAO permanece separada, com significado de confirmação versionada da convergência.
    • numeroControlePNCP não será persistido nessa projeção na V1.
    • Não serão criadas agora tabelas de cabeçalho anual ou de instâncias externas.
    • Estratégia anual é propagada para todas as linhas do exercício.
    • No modo individual, estado, UASG e sequencial são propagados para todas as versões da UO/exercício.
    • No modo consolidado, são propagados para todas as linhas do exercício.
    • Uma nova versão pode nascer com todo o bloco operacional nulo até a próxima propagação.
    • Preenchimento parcial ou valores não nulos divergentes são inconsistências bloqueantes.
    • Não haverá autorreparo silencioso.
Reconstrução Oracle
A fotografia será reconstruída em duas etapas:
    1. Descoberta preliminar das identidades lógicas, sem montar Demandas.
    2. Após adquirir o lock correto, releitura autoritativa da unidade e reconstrução completa.
A consulta deverá:
    • partir de PAC_PLANOS, preservando Plano vazio;
    • selecionar a maior versão da UO/exercício para os metadados atuais;
    • localizar Demandas ligadas a qualquer versão de origem da mesma identidade lógica;
    • incluir somente SITUACAO IN (2, 3, 5);
    • não restringir Demandas ao FK_PLANO da versão máxima.
Conexão e transação
    • Estratégia principal: reutilizar $this->Db, fornecido pelo Scriptcase.
    • A conexão é emprestada:
        ◦ o backend não controla seu ciclo de vida completo;
        ◦ nunca chama Close().
    • Scriptcase e ADOdb ficam encapsulados na Infrastructure.
    • Repository, unidade de trabalho, lock, metadados, confirmação e auditoria compartilham a mesma sessão Oracle.
    • O fallback oficial é uma conexão OCI8 própria.
    • PDO_OCI não será utilizado como fallback.
    • A adoção da estratégia anual ocorre em transação curta e confirmada antes do acesso ao PNCP.
    • A transação da unidade permanece aberta durante observação e escrita no PNCP, conforme a DI-004.
Exclusividade
    • Será criada uma tabela técnica dedicada.
    • Ela conterá somente linhas permanentes usadas como âncoras.
    • Não armazenará:
        ◦ indicação de lock ativo;
        ◦ sessão;
        ◦ expiração;
        ◦ histórico;
        ◦ fila.
    • O lock real pertence à transação Oracle e será adquirido com SELECT ... FOR UPDATE NOWAIT.
    • Contenção retorna BLOQUEADO imediatamente.
    • A chave será estruturada por:
        ◦ ESCOPO;
        ◦ ANO_EXERCICIO;
        ◦ IDENTIFICADOR_ALVO.
    • Haverá âncoras distintas para:
        ◦ adoção da estratégia anual;
        ◦ processamento de cada unidade externa.
    • As linhas serão provisionadas idempotentemente em transação técnica curta.
    • commit, rollback ou encerramento da sessão liberam automaticamente o lock.
Datas e valores
    • Datas de calendário não sofrem conversão de timezone.
    • Instantes usam DateTimeImmutable e fuso operacional explícito.
    • Consultas Oracle usam máscaras explícitas, sem depender de NLS_DATE_FORMAT.
    • Confirmação e auditoria usam SYSDATE.
    • Valores decimais são representados exatamente, sem float.
    • HALF_UP é aplicado somente na borda PNCP.
    • Totais autoritativos do SGI não são recalculados a partir do valor unitário arredondado.
Ownership arquitetural
    • O Domain representa a fotografia vigente, estados e invariantes sem conhecer códigos Oracle.
    • A Application escolhe a estratégia, solicita exclusividade e coordena confirmação.
    • PlanoRepository permanece responsável exclusivamente pelo Aggregate.
    • Estado operacional compartilhado entre versões ou Planos consolidados usa uma porta própria da Application.
    • A Infrastructure:
        ◦ traduz códigos e tipos físicos;
        ◦ executa consultas;
        ◦ valida coerência do schema;
        ◦ propaga a projeção;
        ◦ não decide vigência, modo, reconciliação ou correção automática.
Gates obrigatórios
A aceitação é condicional aos seguintes testes:
    • $this->Db/ADOdb:
        ◦ início de transação;
        ◦ commit;
        ◦ rollback;
        ◦ bind de parâmetros;
        ◦ tradução de erros;
        ◦ compartilhamento da mesma sessão;
        ◦ rollback real de alteração controlada.
    • Schema:
        ◦ DDL completo e nomes físicos;
        ◦ constraints e foreign keys;
        ◦ tipos, tamanhos e nulabilidade;
        ◦ índices existentes, evitando duplicação;
        ◦ unicidade e situação ativa da UO;
        ◦ contrato de PAC_PLANOS.DATA_HOMOLOGACAO.
    • Falha no contrato ADOdb ativa automaticamente o fallback OCI8.
    • Publicações legadas serão reconciliadas de forma assistida; não haverá backfill automático usando dados de Demandas.