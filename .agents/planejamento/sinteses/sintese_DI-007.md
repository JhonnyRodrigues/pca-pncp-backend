A DI-007 — Portas necessárias na Application está concluída.

Ajustes em decisões anteriores:
Por solicitação explícita, a DI-002 anterior foi substituída:
* SGI é autoridade sobre PCA com vínculo local conhecido.
* Reconciliação compara somente presença de numeroItem = ID_DEMANDA.
* Não haverá baseline local.
* Conteúdo dos itens não será comparado na V1.
* PCA externo sem vínculo exige migração assistida.
* PCA vinculado comprovadamente ausente será recriado.
* Alterações ordinárias usam inclusão e exclusão de itens, preservando sequencialPca.
* Exclusão integral seguida de nova publicação não será usada como retificação ordinária.

Essa estratégia acompanha as operações específicas oferecidas pelo PNCP para inserir itens, excluir itens e excluir o PCA.
Também foi corrigida uma regra de negócio: a Demanda homologada admite alteração posterior de dataDesejada, persistida em PAC_DEMANDA.DATA_COMPRA_CONTRATACAO, mantendo o mesmo ID. Essa alteração não será propagada automaticamente pelo sincronizador V1.

Portas aprovadas
Ownership	Capacidade	Responsabilidade
Application	Autenticação PNCP	Autenticar uma vez e retornar sessão opaca reutilizável.
Application	Consulta PNCP	Observar referência conhecida e localizar candidatos sem vínculo.
Application	Operações PNCP	Publicar PCA, incluir itens, excluir itens e excluir PCA.
Application	Estratégia de publicação	Ler configuração atual, consultar estratégia adotada e registrar seu congelamento.
Application	Unidade de trabalho exclusiva	Iniciar transação com lock, confirmar ou desfazer.
Application	Contexto do usuário	Resolver conjuntamente representante e lotação.
Application	Registro de auditoria	Persistir o evento de negócio pronto definido na DI-006.
Domain	PlanoRepository	Listar identidades, reconstruir fotografia vigente e registrar confirmação tipada.
A observabilidade usará Psr\Log\LoggerInterface, com nomes de eventos padronizados e contexto estruturado. Não haverá porta proprietária de logging nesta versão.
Contratos internos
* A consulta PNCP retorna existência, referência externa e o conjunto completo de numeroItem; não retorna JSON nem compara conteúdo.
* Escritas recebem comandos tipados da Application, nunca payloads HTTP.
* Resultados intermediários distinguem aceitação, rejeição, resultado incerto e falha sistêmica.
* PublicarPcaResponse retorna um resultado por PCA externo:
        ◦ CONCLUIDO;
        ◦ SEM_ALTERACAO;
        ◦ FALHA_RECONCILIAVEL;
        ◦ BLOQUEADO.
* Falhas sistêmicas permanecem no nível global.
* PlanoRepository terá operações para:
        ◦ listar identidades lógicas por exercício;
        ◦ reconstruir o Plano vigente, inclusive sem Demandas ativas;
        ◦ registrar confirmação tipada por Plano.
* Não haverá consulta dinâmica a PAC_PROCEDIMENTOS; os IDs 5, 8 e 9 serão tipados.
* Confirmação e auditoria usarão Oracle SYSDATE.
Regra de reconciliação
A Application monta a unidade individual ou consolidada, mas a decisão permanece no Domain. Uma política de domínio pura recebe as fotografias desejada e observada e devolve:
* PUBLICAR;
* RETIFICAR;
* EXCLUIR_PCA;
* NENHUMA_ACAO;
* IDs a incluir;
* IDs a excluir.
