# Sprint 5.3 — Mapeamento e PlanoRepository Oracle

## Resumo

Implementar, com TDD, `Infrastructure\Oracle\Plano\OraclePlanoRepository` para o contrato `Domain\Ports\PlanoRepository`, conforme RN-DEM-009, RN-PLA-001 a RN-PLA-007 e DI-008.

A implementação reutilizará a mesma `ConexaoAdodbEmprestada`, não alterará Domain/Application, não executará DDL ou integrações reais e não antecipará a Sprint 5.4. Cada incremento terminará com validação e pausa obrigatória para aprovação.

## Interfaces e decisões técnicas

- Preservar sem alterações os métodos públicos:
  - `listarIdentidades(AnoExecucao): array`;
  - `reconstruirFotografiaVigente(IdentidadePlano): Plano`;
  - `confirmarSincronizacao(Plano): void`.
- Criar no namespace `Infrastructure\Oracle\Plano`:
  - `OraclePlanoRepository`;
  - mapeamentos imutáveis e allowlisted para Planos, Demandas e Unidades Orçamentárias;
  - exceção interna `DadosPlanoOracleInvalidosException`.
- Injetar no Repository a conexão compartilhada, os três mapeamentos físicos e o `DateTimeZone` operacional, todos sem defaults.
- Validar identificadores Oracle; usar aliases canônicos e binds via `ConexaoAdodbEmprestada`; nunca aceitar SQL arbitrário, nomes físicos implícitos ou `float`.
- Converter datas com máscaras Oracle explícitas e parsing estrito. Converter `NUMBER` para representação decimal canônica antes de criar `DecimalExato`.

## Incrementos de implementação

1. **Mapeamentos e tradução de tipos**
   - Mapear chave técnica e colunas de UO, exercício, versão, homologação e confirmação dos Planos.
   - Mapear relação com Plano de origem e todos os campos exigidos pelo construtor de `Demanda`.
   - Mapear identidade da UO, Lotação, descrição, UASG, condição de cadastro ativo e relações físicas.
   - Implementar conversores privados estritos para inteiros positivos, textos, situações, datas e decimais.
   - Validar com testes focados, suíte disponível, lint e diff; pausar.

2. **Descoberta de identidades**
   - Consultar apenas identidades distintas de Planos do exercício.
   - Resolver exatamente um cadastro ativo de UO com Lotação e UASG válidas.
   - Retornar identidades em ordem determinística e `[]` quando não houver Planos.
   - Rejeitar ausência, duplicidade ou dados físicos incoerentes; não escolher registros arbitrariamente.
   - Permitir esta operação antes ou durante uma transação, pois ela atende tanto à descoberta preliminar quanto à releitura sob lock.
   - Validar e pausar.

3. **Reconstrução autoritativa**
   - Exigir transação ativa, preservando a responsabilidade da Application pela aquisição prévia do lock.
   - Selecionar exatamente a maior versão da combinação UO + exercício, sem considerar a confirmação.
   - Partir da linha de Plano para permitir fotografia vazia.
   - Reunir Demandas ligadas a todas as versões de origem da identidade, filtrando somente Homologada, Comprada e Publicada.
   - Não ler sequencial externo de Demandas e não restringir a consulta à chave da versão máxima.
   - Construir `Demanda` e `Plano` com tipos do Domain, rejeitando duplicidades, cardinalidade inválida ou conteúdo inconsistente.
   - Validar versões sucessivas, Demandas antigas e atuais, fotografia vazia e erros de mapeamento; pausar.

4. **Confirmação e encerramento**
   - Exigir transação ativa e Plano ainda pendente.
   - Conferir a linha exata da identidade e versão, atualizar somente `DATA_PUBLICACAO IS NULL` com `SYSDATE` e reler para confirmar a intenção.
   - Nunca atualizar versões anteriores, abrir/confirmar transação ou mutar o `Plano` carregado.
   - Atualizar apenas o estado operacional vigente e o catálogo de arquivos, registrando resultados reais e a Sprint 5.4 como próxima; não alterar RN, DI, modelo, fluxo ou roadmap prescritivo.
   - Executar validação final e pausar.

## Testes e critérios de aceitação

- Cobrir mapeamentos inválidos, binds, aliases, datas, decimais exatos e falhas Oracle sanitizadas.
- Cobrir zero, uma e múltiplas identidades; UO ausente, duplicada, inativa ou inválida.
- Cobrir maior versão independentemente de confirmação, Demandas oriundas de versões diferentes, situações 1–5 e Plano vazio.
- Cobrir confirmação pendente, já confirmada, versão inexistente, ausência de transação e falha de releitura.
- Demonstrar compartilhamento da sessão e ausência de `Close()`, DDL, commit ou rollback autônomo.
- Em cada pausa: testes focados, suíte padrão, lint dos PHP alterados e inspeção do diff. Ao final: suíte contratual não real, autoload PSR-4 e `git diff --check`.

## Premissas

- A cadência escolhida é de pausas obrigatórias após cada incremento.
- Composer e `vendor/` estão ausentes no ambiente atual; as dependências de desenvolvimento serão restauradas antes do primeiro ciclo, e nenhuma implementação começará se a linha de base não puder ser validada.
- Nenhum probe Oracle/PNCP real será executado; esses contratos permanecem nas sprints futuras previstas.
- A alteração local em `.agents/sprints/prompts/prompt_sprint_curto.md` permanecerá preservada e fora do escopo.
- Nenhum commit será criado sem solicitação explícita.
