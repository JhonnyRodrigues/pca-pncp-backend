# Sprint 5.9 — Suíte de contratos dos adapters

## Resumo

Criar uma suíte opt-in que exercite os adapters concretos das Sprints 5.2–5.8, primeiro em ambiente controlado e depois no Oracle/Scriptcase real e no PNCP de treinamento. Nenhuma Port, API de produção, regra ou adapter novo será introduzido.

A Sprint 5.9 só será concluída quando os dois gates reais forem confirmados. Mesmo assim, a Fase 5 permanecerá bloqueada pela ausência do adapter de `EvidenciaCancelamentoHomologadoPort`, e a Sprint 6.1 não será autorizada.

## Implementação

1. **Suíte e segurança**
   - Criar `tests/Contract/Sprint59` e configuração PHPUnit própria, preservando intacta a suíte histórica da Sprint 5.1.
   - Usar `PCA_SPRINT59_CONFIG_FILE` para configuração externa, sem segredos ou nomes físicos implícitos no repositório.
   - Adicionar comandos Composer para testes focados dos adapters, contratos controlados, preflight Oracle/PNCP e PNCP live.
   - Testar preflight fail-closed: somente homologação PNCP, `allow_writes=true`, massa dedicada, mapeamentos Oracle explícitos e relatórios sanitizados.
   - Exercitar as classes de produção; não duplicar transporte, serialização ou lógica dos adapters em probes paralelos.

2. **Contrato PNCP real**
   - Montar no teste o transporte cURL, serializer, autenticador, leitor, escritor e acumulador de eventos reais.
   - No ambiente `treina.pncp.gov.br`: autenticar uma vez, impedir alvo desconhecido, publicar itens com IDs lacunados e decimais exatos, observar, inserir, excluir itens, excluir o PCA e comprovar ausência definitiva.
   - Aceitar apenas sequenciais criados pela própria execução ou um resíduo explicitamente autorizado na configuração.
   - Executar limpeza best-effort e considerar o gate falho enquanto houver resíduo possível.
   - Validar TLS, sessão opaca, paginação, resultados tipados e ausência de credenciais, identidades, corpos ou headers nos eventos e relatórios.

3. **Contrato Oracle real**
   - Disponibilizar um entrypoint de Scriptcase `executar_contratos_adapters_oracle_sprint_59($db, $login, $mode)` com modos `adapters`, `lock_holder`, `lock_expect_blocked` e `lock_expect_available`.
   - Instanciar sobre uma única `ConexaoAdodbEmprestada` os adapters existentes de unidade de trabalho, estratégia, Plano, estado operacional, contexto e auditoria.
   - Em massa dedicada, comprovar leituras, binds, mesma sessão, escrita visível dentro da transação, confirmação/estado/auditoria atômicos e restauração por rollback.
   - Com duas sessões, comprovar contenção imediata traduzida de `ORA-00054` e nova aquisição após liberação.
   - Confirmar que a conexão emprestada permanece utilizável e nunca é fechada pelo backend.
   - Validar traduções de erro e relatórios somente por códigos e mensagens seguras.

4. **Encerramento e documentação**
   - Corrigir em TDD apenas defeitos dos adapters existentes que contrariem contratos vigentes. Incompatibilidade externa reabre somente a DI afetada antes de qualquer mudança; o adapter ausente permanece fora do escopo.
   - Registrar o plano aprovado e um relatório investigativo sanitizado com ambiente, data, runtime, hashes dos relatórios externos, resultados e limitações, sem versionar os JSON integrais.
   - Atualizar índice documental, catálogo, guia de leitura e estado operacional.
   - Marcar a Sprint 5.9 concluída somente com Oracle e PNCP reais aprovados; caso contrário, mantê-la aberta.
   - Registrar expressamente que a Fase 5 não está encerrada, que `EvidenciaCancelamentoHomologadoPort` não possui adapter concreto e que a Fase 6 está bloqueada até planejamento e autorização próprios para resolver essa lacuna.
   - Não alterar Roadmap, RN, modelo, Ports ou APIs públicas e não criar commit.

## Interfaces de teste

- Novos comandos: `composer adapters:tests`, `composer contract:adapters:tests`, `composer contract:adapters:preflight`, variantes Oracle/PNCP e `composer contract:adapters:pncp`.
- Nova variável externa: `PCA_SPRINT59_CONFIG_FILE`.
- O entrypoint Scriptcase pertence apenas à suíte contratual; não é fachada nem Composition Root de produção.

## Validação

- Linha de base atual: 799 testes/2.865 asserções e 24 contratos estruturais/51 asserções em PHP 8.4.7.
- Executar suíte dos adapters, contratos controlados, suíte padrão, contratos legados não reais, lint, PSR-4 estrito, `git diff --check` e inspeção de segurança.
- Gate PNCP: fluxo real completo, TLS confirmado, limpeza comprovada e relatório sanitizado.
- Gate Oracle: adapters reais na sessão Scriptcase, rollback, contenção concorrente, tradução de erros e ausência de `Close()`.
- CNPJ alfanumérico e chamada única com 1.001 itens continuam não comprovados e não bloqueantes; não haverá opt-in para ampliá-los nesta sprint.
