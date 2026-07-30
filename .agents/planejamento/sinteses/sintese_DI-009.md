DI-009 — Transporte HTTP
A DI-009 está concluída no planejamento e aceita condicionalmente.
Contrato oficial adotado
    • Manual PNCP vigente: versão 2.5.
    • Integração REST/HTTP com JSON.
    • Ambientes oficiais:
        ◦ homologação: https://treina.pncp.gov.br/api/pncp;
        ◦ produção: https://pncp.gov.br/api/pncp.
    • Autenticação por login e senha, com JWT retornado no header Authorization.
    • Validade documentada do token: uma hora.
    • CNPJ alfanumérico de 14 posições aceito em todos os campos das APIs.
Fontes: acesso e ambientes, manual oficial v2.5.
Transporte HTTP
    • Usar cURL nativo, sem nova dependência de runtime.
    • cURL fica encapsulado em adapter da Infrastructure.
    • Handles, constantes, códigos e mensagens cURL não chegam à Application.
    • O transporte:
        ◦ envia uma única requisição;
        ◦ captura status, headers e corpo separadamente;
        ◦ diferencia falha de transporte de resposta HTTP;
        ◦ não decide retry, reconciliação ou regra de negócio.
    • Redirecionamentos automáticos ficam desabilitados, evitando envio do JWT para host inesperado.
    • Respostas e payloads terão limite de tamanho configurado.
    • O host Scriptcase deverá comprovar ext-curl, OpenSSL e CA bundle antes da implementação definitiva.
Ambientes e URLs
    • O ambiente é representado por tipo interno:
        ◦ HOMOLOGACAO;
        ◦ PRODUCAO.
    • Cada valor mapeia para a URL oficial correspondente.
    • Request e usuário não fornecem URL livre.
    • Caminhos são montados como segmentos relativos e codificados com segurança.
    • Testes unitários usam doubles do transporte, sem liberar destinos arbitrários.
TLS
    • Somente HTTPS.
    • Verificação de certificado e hostname obrigatória.
    • Usar cadeia de autoridades certificadoras do ambiente.
    • CA bundle pode ter caminho configurável.
    • Não haverá:
        ◦ equivalente a curl -k;
        ◦ opção para desabilitar TLS;
        ◦ pinning de chave pública.
    • Falha de confiança TLS é erro sistêmico.
Autenticação
    • Uma única autenticação por execução.
    • O JWT é tratado como valor opaco.
    • Não decodificar claims para controlar expiração.
    • Exigir header Authorization válido com esquema Bearer.
    • Headers são lidos sem sensibilidade a maiúsculas/minúsculas.
    • O token:
        ◦ não é persistido;
        ◦ não é compartilhado entre execuções;
        ◦ não aparece em logs ou exceções.
    • 401 após autenticação encerra como falha sistêmica.
    • 403 representa autorização negada.
    • Não haverá renovação ou segunda autenticação na mesma execução.
    • A origem das credenciais será definida na DI-010.
Timeouts
Valores iniciais, configuráveis por ambiente:
    • conexão: 5 segundos;
    • login e leituras: 30 segundos;
    • escritas: 60 segundos.
Regras:
    • nenhum timeout pode ser infinito;
    • configuração ausente ou inválida bloqueia o bootstrap;
    • timeout de escrita significa resultado incerto, não rejeição conhecida;
    • os limites protegem o tempo de retenção do lock Oracle.
Retry de leitura
    • Permitido somente para GET.
    • Máximo de duas tentativas totais.
    • Elegível para:
        ◦ falha transitória de transporte;
        ◦ 408;
        ◦ 429;
        ◦ 502;
        ◦ 503;
        ◦ 504.
    • Espera curta e configurável.
    • Retry-After só é respeitado até um teto configurado.
    • Cada tentativa é observável.
    • Login, POST e DELETE não reutilizam essa política.
    • O transporte cURL nunca possui retry genérico.
Leitura e paginação
    • pagina e tamanhoPagina serão sempre explícitos.
    • Paginar até comprovar o fim do conjunto.
    • O tamanho da página depende de teste contratual.
    • Validar entre todas as páginas:
        ◦ unicidade de numeroItem;
        ◦ ausência de páginas repetidas;
        ◦ coerência de CNPJ, ano, UASG e sequencial;
        ◦ limite total de 1000 itens.
    • Nunca decidir convergência com resultado parcial.
    • Resposta paginada inconsistente ou acima de 1000 itens é bloqueante.
Fonte: consulta de itens do PCA.
Ausência definitiva do PCA
Exigir duas evidências:
    1. consulta pelo sequencialPca conhecido indica ausência;
    2. listagem paginada por órgão/ano não contém PCA da mesma UASG.
Resultados:
    • nenhuma ocorrência: ausência definitivamente comprovada;
    • outro sequencial para a mesma UASG: conflito/migração assistida;
    • respostas divergentes ou inconclusivas: não recriar;
    • falha de qualquer consulta: ausência não comprovada.
A forma concreta da resposta de ausência deverá ser validada em homologação, pois o manual não documenta claramente 404 para esses endpoints.
Leitura tolerante do JSON
    • Campos adicionais desconhecidos são ignorados.
    • Campos utilizados pelo sistema continuam obrigatórios e tipados.
    • Valores contraditórios, duplicados ou ausentes invalidam a observação.
    • JSON malformado ou profundo demais é rejeitado.
    • Textos devem ser UTF-8 válido.
    • Não substituir caracteres inválidos silenciosamente.
    • Mensagens textuais do PNCP não controlam o fluxo; servem apenas para diagnóstico sanitizado.
Classificação das escritas
    • Resposta documentada e semanticamente completa: ACEITA.
    • 400 ou 422 válidos: REJEITADA.
    • 401 ou 403: falha sistêmica de autenticação/autorização.
    • Timeout, conexão interrompida após envio, 429, 5xx, resposta malformada ou sucesso incompleto: RESULTADO_INCERTO.
    • Escrita incerta nunca é repetida diretamente.
    • Após resultado incerto:
        1. observar novamente;
        2. comparar desejado e publicado;
        3. recalcular;
        4. permitir somente a reconciliação adicional aprovada na DI-004.
Ordem da retificação
Quando houver exclusões e inclusões:
    1. excluir itens cancelados;
    2. incluir itens novos;
    3. observar novamente o PCA completo.
Consequências:
    • evita ultrapassar temporariamente 1000 itens;
    • remove primeiro itens que deixaram de ser vigentes;
    • falha parcial pode deixar inclusões pendentes;
    • convergência parcial produz FALHA_RECONCILIAVEL;
    • não haverá compensação automática.
As operações serão feitas em lote, sem particionamento acima de 1000 na V1.
Justificativas
    • Exclusão de itens com motivos diferentes:
        ◦ um único DELETE em lote;
        ◦ mensagem padronizada;
        ◦ informa cancelamentos homologados no SGI e quantidade de itens;
        ◦ motivos completos permanecem nas Demandas locais.
    • Não concatenar nem truncar motivos.
    • A Application constrói a justificativa.
    • Infrastructure apenas serializa.
    • Exclusão integral do PCA usa o motivo do cancelamento que retirou o último item.
Fonte: exclusão em lote de itens.
Decimais e JSON
    • Manter representação decimal exata.
    • Nunca converter para float.
    • Usar um marcador interno específico para números JSON exatos.
    • Encoder da Infrastructure emite token numérico sem aspas somente após validação lexical.
    • Não aceitar fragmentos JSON arbitrários.
    • HALF_UP é aplicado apenas na borda PNCP.
    • valorTotal e valorOrcamentoExercicio permanecem autoritativos.
    • Não recalcular totais a partir do unitário arredondado.
    • Datas puras são serializadas como YYYY-MM-DD.
numeroItem
    • Manter ID_DEMANDA → numeroItem.
    • Nenhum mapeamento alternativo será criado antecipadamente.
    • Testar IDs reais, únicos e com lacunas.
    • Produção fica bloqueada até a homologação confirmar a aceitação.
    • Rejeição concreta reabre DI-001 e DI-002.
Observabilidade e segurança
Registrar por PSR-3:
    • ambiente;
    • operação lógica;
    • método e caminho sanitizado;
    • duração;
    • tentativa;
    • status HTTP;
    • categoria do erro;
    • resultado da classificação;
    • identificador de execução e unidade externa.
Nunca registrar:
    • login e senha;
    • JWT;
    • header Authorization;
    • payload completo;
    • resposta completa contendo dados sensíveis;
    • handles ou dumps cURL.
Suíte de contrato PNCP
    • PHPUnit opt-in, separado da suíte unitária.
    • Execução somente por comando explícito.
    • Credenciais fornecidas externamente.
    • Massa controlada em CNPJ/UASG/exercício dedicados.
    • Execução serial.
    • Preflight obrigatório confirmando:
        ◦ ambiente HOMOLOGACAO;
        ◦ host treina.pncp.gov.br.
    • Estruturalmente proibida contra produção.
    • Estado externo observado antes de qualquer escrita.
    • Resíduos anteriores são reconciliados conscientemente.
    • Limpeza final best-effort, com diagnóstico quando não for possível.
A suíte deverá comprovar:
    • autenticação e header JWT;
    • método real do login;
    • códigos e formato de ausência;
    • paginação e tamanho aceito;
    • headers Location;
    • extração do sequencialPca;
    • CNPJ alfanumérico;
    • ID_DEMANDA com lacunas;
    • decimal exato como número JSON;
    • diferença entre total autoritativo e produto arredondado;
    • classificação de falhas de leitura e escrita;
    • comportamento após timeout ou resultado incerto.
Gates condicionantes
Antes da Infrastructure definitiva:
    • validar ext-curl, OpenSSL e CA bundle no Scriptcase;
    • corrigir o carregamento duplicado de cURL no WSL;
    • alinhar o runtime efetivo com a versão PHP suportada pelo projeto;
    • executar a suíte opt-in no PNCP de treinamento;
    • registrar versão do manual/OpenAPI, data e resultados observados;
    • reabrir qualquer decisão cujo contrato real seja incompatível.