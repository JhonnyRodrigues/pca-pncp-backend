DI-001 — Contrato de publicação e sincronização do PCA no PNCP
    • Plano representa o estado homologado vigente por Ano + Unidade.
    • A fotografia inclui Demandas homologadas vigentes de todas as versões de origem.
    • Primeira publicação cria o PCA por POST.
    • Retificação mantém o sequencialPca e sincroniza itens no PCA existente.
    • Fluxo normal usa POST para novas Demandas e DELETE para canceladas.
    • PATCH não será implementado sem regra que permita alterar Demanda homologada.
    • Se não restarem Demandas e houver PCA publicado, o Domain decide EXCLUIR_PCA.
    • A exclusão usa a justificativa estruturada do cancelamento que esvaziou o Plano.
    • ID_DEMANDA é enviado como numeroItem; essa compatibilidade será provada no ambiente de homologação.
    • SequencialPcaPncp será Value Object opcional do Plano.
    • numeroControlePNCP permanece fora do Domain até existir necessidade de negócio.
    • PAC_PLANOS.ID_PLANO e PAC_DEMANDAS.FK_PLANO não fazem parte do modelo recomendado.
    • O Domain decide a intenção recebendo um fato externo abstrato obtido pela Application.
    • O Repository reconstrói o estado vigente; não consulta apenas Demandas ligadas à última linha de PAC_PLANOS.
Impactos obrigatórios: revisar catálogo, modelo de domínio, fluxo, Plano, Demanda, enum de decisão, testes e contrato de PlanoRepository antes de criar Infrastructure.
Gostaria de simplificar esta decisão.
Sobre publicações legadas no PNCP, optei por não tratá-las automaticamente nesta versão do sistema. Hoje existe apenas uma publicação realizada manualmente via Swagger e ela é conhecida. Portanto, considero esse um problema de implantação/migração inicial, e não um caso que justifique aumentar a complexidade do fluxo operacional.
Quanto ao cenário de falha após confirmação no PNCP, também prefiro uma abordagem simples neste momento.
O fluxo pretendido é:
1. Autenticar no PNCP;
2. Consultar o estado atual do PCA;
3. Decidir a ação;
4. Abrir transação Oracle;
5. Executar a operação no PNCP;
6. Persistir os resultados localmente;
7. Commit.
A autenticação já funciona como validação de disponibilidade, conectividade, credenciais e permissões, dispensando uma chamada genérica apenas para teste do serviço.
Além disso, pretendo abrir a transação Oracle antes da operação de publicação e registrar os metadados necessários durante o processamento. Embora continue existindo a possibilidade teórica de o PNCP confirmar a operação e o Oracle falhar antes do commit, considero esse risco aceitável diante das características do sistema.
Sugestão para ADR:
"Por decisão de simplicidade, publicações legadas não serão tratadas automaticamente nesta versão. Eventuais publicações existentes no PNCP serão reconciliadas manualmente durante a implantação.
O fluxo de publicação utilizará transação Oracle aberta durante a sincronização com o PNCP. Embora permaneça possível um cenário de confirmação externa seguida de falha local, o risco foi considerado aceitável diante do baixo volume de operações, baixa concorrência e natureza administrativa do sistema PCA. Caso essas premissas mudem no futuro, a estratégia deverá ser revisitada."