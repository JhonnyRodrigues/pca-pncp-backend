# Sprint 6.2 — Integração controlada dos adapters

## Resumo

Comprovar o núcleo montado com os nove adapters concretos, usando somente fronteiras controladas: um ADOdb programável em memória e um executor cURL roteirizado, sem Oracle, PNCP ou Scriptcase reais.

A implementação será exclusivamente de testes e documentação. Nenhuma API ou classe de produção deverá mudar. A aprovação inicial libera somente o Microciclo 1; cada ciclo termina com diff, evidências e pausa obrigatória.

## Estratégia de integração

- Criar a suíte `tests/Integration/Composition/`, descoberta pela suíte padrão.
- Montar os seis adapters Oracle sobre a mesma `ConexaoAdodbEmprestada`.
- Montar autenticação, leitura e escrita PNCP sobre o mesmo `TransporteCurlPncp`, configuração de homologação, logger e executor controlado.
- Usar um registro cronológico comum para operações Oracle e PNCP.
- O ADOdb controlado responderá por fila estrita e registrará begin, commit, rollback, consultas, escritas e qualquer tentativa de `Close`.
- O executor cURL controlado exigirá método e URL esperados, produzirá respostas pelos callbacks reais do transporte e falhará diante de chamadas inesperadas ou respostas não consumidas.
- Credenciais, token, nomes físicos e dados serão fictícios; nenhuma chamada de rede será possível.

## Microciclos

1. **Núcleo concreto e reconstrução controlada**
   - Registrar o plano aprovado da Sprint 6.2.
   - Criar o cenário controlado e montar todos os nove adapters pelo `MontadorNucleoPublicacaoPca`.
   - Demonstrar uma unidade individual já convergente (`NENHUMA_ACAO`): contexto e Plano reconstruídos pelo Oracle, estratégia adotada, autenticação única e observações PNCP pelo transporte concreto.
   - Verificar duas unidades transacionais ordenadas — estratégia e publicação —, commits correspondentes, ausência de escrita PNCP e conexão emprestada nunca fechada.
   - Pausar antes do Microciclo 2.

2. **Retificação e sessão compartilhada**
   - Configurar fotografia desejada com item `1002`, PCA inicial com item `1001` e observação final convergente com `1002`.
   - Demonstrar `RETIFICAR` usando leitor, escritor, serializer e transporte concretos.
   - Exigir uma única autenticação e o mesmo Bearer em todas as leituras e escritas posteriores.
   - Comprovar a ordem: excluir item, inserir item, observar novamente, confirmar Planos, registrar estado e auditoria, então commit.
   - Confirmar resultado global `CONCLUIDA`, resultado unitário `CONCLUIDO` e ausência de rede real.
   - Pausar antes do Microciclo 3.

   **Adequação autorizada durante a execução:** como a retificação preserva o
   estado `PUBLICADO(77)`, o núcleo vigente não chama
   `EstadoOperacionalPublicacaoPort::registrar()` para repetir o mesmo valor. O
   mantenedor autorizou comprovar a ordem efetiva `excluir → inserir → observar
   novamente → confirmar Plano → registrar auditoria → commit` e demonstrar
   explicitamente a ausência correta dessa gravação redundante.

3. **Falha final e encerramento seguro**
   - Repetir a retificação, mas tornar inconclusiva a observação final após as escritas aceitas, esgotando as duas tentativas controladas de leitura sem espera real.
   - Exigir `CONCLUIDA_COM_PENDENCIAS` e resultado unitário `FALHA_RECONCILIAVEL`.
   - Comprovar rollback da unidade depois da última tentativa externa, sem confirmação de Plano, estado operacional, auditoria ou commit da unidade.
   - Confirmar que o commit anterior da estratégia permanece, a conexão emprestada não é fechada e eventos não expõem credenciais, token, payload ou SQL.
   - Pausar antes do Microciclo 4.

4. **Documentação e gate da sprint**
   - Marcar Sprint 6.2 como concluída, Fase 6 ainda em andamento e Sprint 6.3 como próxima etapa não autorizada.
   - Atualizar estado operacional, roadmap e documentação navegacional com os cenários e resultados reais executados.
   - Executar suíte de integração, suíte padrão, adapters, contratos controlados sem grupos live, PSR-4 estrito, lint, `git diff --check` e inspeção da direção das dependências.
   - Não executar preflight, grupos live ou qualquer gate externo.

## Interfaces, aceitação e limites

- Nenhuma alteração em `src/` é esperada. Se um teste revelar defeito de produção, o microciclo deve parar e apresentar a evidência antes de ampliar o escopo.
- O cenário usa `AmbientePncp::HOMOLOGACAO`, dados fictícios e doubles abaixo dos adapters; não substitui os adapters ou o montador por mocks.
- Não serão antecipados perfil de implantação, Composition Root final, Presentation, container, Service Locator ou ciclo do Scriptcase.
- `PUBLICAR`, `EXCLUIR_PCA`, modo consolidado e a matriz completa dos quatro fluxos permanecem para a Sprint 8.4.
- As alterações atuais da Sprint 6.1 e `regras_de_negocio.md` serão preservadas.
- Nenhum commit será criado.
