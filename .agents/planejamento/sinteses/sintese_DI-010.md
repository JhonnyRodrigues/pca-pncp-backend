DI-010 — Composition Root e integração com Scriptcase
Síntese
DI significa Decisão de Integração.
A DI-010 fica aceita, condicionada aos testes contratuais no ambiente real do Scriptcase. O backend será um produto independente, consumido em processo pela aplicação Blank.
Contratos e arquitetura
    • O Composition Root pertence ao backend e realiza wiring manual por construtores, sem container.
    • O Scriptcase carrega o autoloader e chama uma única fachada pública.
    • A fachada recebe:
        ◦ conexão $this->Db emprestada;
        ◦ login autenticado confiável;
        ◦ SERVER_NAME;
        ◦ ano selecionado.
    • O Request da Application contém somente Cnpj e AnoExecucao.
    • CNPJ permanece explícito no Use Case, mas na implantação atual vem do perfil autorizado, não da tela.
    • Ambiente, credenciais e usuário logado saem do Request.
    • O Controller e o Presenter são próprios da integração em processo:
        ◦ Controller fino, sem macros ou wiring;
        ◦ Presenter transforma a Response em ViewModel;
        ◦ Blank apenas renderiza o ViewModel.
Configuração e ciclos de vida
    • SERVER_NAME apenas seleciona, por allowlist exata, um perfil conhecido.
    • O perfil externo declara explicitamente:
        ◦ HOMOLOGACAO ou PRODUCAO;
        ◦ CNPJ autorizado;
        ◦ credenciais PNCP;
        ◦ demais parâmetros da implantação.
    • Perfil desconhecido, ausente ou incoerente falha fechado, sem fallback para produção.
    • Os arquivos de perfil ficam fora do repositório e do webroot.
    • Cada chamada do Blank cria um grafo novo:
        ◦ uma autenticação PNCP;
        ◦ uma sessão PNCP opaca;
        ◦ uma conexão Oracle compartilhada pelos adapters.
    • A conexão emprestada nunca é fechada pelo backend.
    • Se o gate ADOdb falhar, aplica-se o fallback OCI8 próprio já aprovado; nesse caso, o backend possui e encerra a conexão.
    • Token e recursos HTTP são descartados ao final da chamada.
    • A V1 é síncrona, sem fila, worker ou persistência de progresso.
    • A tela bloqueia nova submissão enquanto aguarda; concorrência residual é protegida pelos locks unitários e pela reconciliação existente.
Respostas e falhas
Estados globais:
    • SOLICITACAO_INVALIDA
    • CONCLUIDA
    • CONCLUIDA_COM_PENDENCIAS
    • INTERROMPIDA
Estados por unidade externa já aprovados:
    • CONCLUIDO
    • SEM_ALTERACAO
    • FALHA_RECONCILIAVEL
    • BLOQUEADO
Cada resultado informa identidade externa, modo, UASG, ação, sequencial PNCP conhecido, contagens relevantes, código/mensagem segura e correlação de diagnóstico.
Em interrupção sistêmica:
    • resultados já conhecidos são preservados;
    • o resumo informa quantidades planejada, processada e não processada;
    • não são fabricados resultados para unidades não visitadas.
Nenhuma exceção atravessa a fachada. Falhas inesperadas são registradas com correlação e convertidas em ViewModel seguro, sem stack trace, payload, SQL, senha ou JWT.
Implantação e testes
    • Distribuir release autocontida com código e vendor/, sem Composer em produção.
    • Manter perfis e segredos externos ao artefato.
    • Testar:
        ◦ seleção allowlisted de perfil e falha fechada;
        ◦ ausência de inferência do ambiente pelo hostname;
        ◦ construção do Request com CNPJ configurado e ano informado;
        ◦ resolução do representante e lotação pelo login vinculado ao bootstrap;
        ◦ wiring manual e compartilhamento da mesma conexão;
        ◦ conexão emprestada nunca fechada;
        ◦ uma autenticação por execução;
        ◦ todos os estados globais e unitários;
        ◦ sanitização de exceções e segredos;
        ◦ resposta parcial após interrupção sistêmica;
        ◦ integração real com a aplicação Blank;
        ◦ pacote autocontido em homologação antes da produção.
A autorização para abrir e acionar o Blank permanece no Scriptcase. O backend exige identidade autenticada e contexto de representante/lotação resolvível, mas não inventa uma política adicional de papéis na V1.
