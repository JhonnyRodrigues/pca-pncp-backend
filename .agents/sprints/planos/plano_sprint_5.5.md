# Sprint 5.5 — Transporte cURL, TLS e autenticação

## Resumo

Implementar na Infrastructure o transporte HTTP PNCP com cURL nativo e autenticação única por execução, conforme Sprint 5.5, DI-009 e fluxo vigente. O transporte realizará exatamente uma requisição por chamada, separará status/headers/corpo, distinguirá falha cURL de resposta HTTP e aplicará TLS, hostname, limites e timeouts seguros.

A aprovação deste plano autoriza somente o primeiro incremento. Haverá pausa obrigatória ao final de cada microciclo, sem commits.

## Incrementos e componentes

1. **Configuração e contratos internos do transporte**
   - Criar ambiente fechado `HOMOLOGACAO`/`PRODUCAO`, associado somente aos endpoints canônicos; não aceitar URL livre.
   - Criar configuração validada com timeouts configuráveis — conexão 5 s, login/leitura 30 s e escrita 60 s —, limites obrigatórios de payload/resposta sem defaults inventados e CA bundle opcional.
   - Modelar requisição, resposta e falha técnica internas à Infrastructure, mantendo status, headers e corpo separados.
   - Validar HTTPS, caminho relativo, método permitido, limites positivos e ausência de timeout infinito.
   - **Pausa obrigatória:** apresentar testes focados, diff e decisões antes do transporte cURL.

2. **Transporte cURL nativo**
   - Implementar uma única inicialização/execução cURL por chamada, sem redirects, com verificação de certificado e hostname, protocolo restrito a HTTPS e CA configurável.
   - Capturar headers e corpo por callbacks com limite de tamanho; recusar payload acima do limite antes do envio.
   - Usar uma pequena fronteira interna para tornar a execução cURL testável sem rede.
   - Retornar respostas HTTP normalmente, inclusive erros HTTP; lançar somente falhas técnicas sanitizadas para erros de transporte.
   - Não decidir retry ou reconciliação e não executar probes reais.
   - **Pausa obrigatória:** apresentar testes de opções cURL, limites, captura e falhas antes da autenticação.

3. **Autenticação e sessão opaca**
   - Implementar o adapter do `AutenticadorPncp` e uma sessão concreta que implemente `SessaoPncp`, sem alterar suas assinaturas.
   - Enviar um único `POST` de login; exigir `Authorization: Bearer <token>` válido e tratar o JWT como texto opaco, sem decodificação, persistência, renovação ou segunda tentativa.
   - Traduzir falha de transporte, resposta inválida, `401` e `403` para `ResultadoAutenticacaoPncp::falhaSistemica()` com `FalhaSegura`, sem corpo, headers, credenciais ou token.
   - Registrar apenas resultado técnico sanitizado via PSR-3. Como o acumulador rejeita ações fora de `AcaoProcessamentoUnidade` e o fluxo exige observabilidade da autenticação, adicionar somente o caso aditivo `AUTENTICAR_PNCP`; nenhuma Port, comando ou resultado público será alterado.
   - **Pausa obrigatória:** apresentar comportamento completo de autenticação, inspeção de segurança e regressão focada.

4. **Validação e encerramento operacional**
   - Executar suíte focada, `composer tests`, `composer contract:tests` — que exclui `pncp-live` —, lint dos PHP alterados, `composer dump-autoload --strict-psr`, `git diff --check` e inspeções arquiteturais.
   - Confirmar que cURL permanece na Infrastructure, que Domain não mudou, que as Ports/comandos/resultados da Application permanecem compatíveis e que nenhuma dependência HTTP foi adicionada.
   - Inspecionar o diff por credenciais, JWT, Authorization, payloads, respostas integrais, dumps cURL e mensagens sensíveis.
   - Somente após código e validações concluídos, atualizar `estado_atual.md` com evidências reais da Sprint 5.5. Não alterar roadmap nem DI-009.
   - **Pausa obrigatória final:** entregar resultados, limitações e diff; não criar commit.

## Testes principais

- Configuração rejeita ambiente, URL, protocolo, limites e timeouts inválidos.
- Transporte aplica TLS/hostname, bloqueia redirects e executa exatamente uma requisição.
- Status, headers e corpo são capturados separadamente; excesso de payload/resposta falha de forma segura.
- Falha cURL permanece distinta de qualquer status HTTP.
- Login bem-sucedido cria sessão opaca; token não é decodificado nem exposto.
- Header ausente/malformado, `401`, `403`, timeout e falha técnica produzem falha sistêmica sanitizada.
- Logs e exceções não contêm login, senha, JWT, Authorization, payload ou resposta completa.
- Nenhum teste unitário ou contratual não real acessa o PNCP.

## Gates, riscos e adiamentos

- Permanecem condicionais no runtime efetivo do Scriptcase: `ext-curl`, OpenSSL, CA bundle e compatibilidade operacional.
- A suíte PNCP de treinamento e qualquer probe real exigem autorização específica; não serão executados nesta sprint.
- Continuam não comprovados e não bloqueantes: CNPJ alfanumérico e chamada com 1.001 itens; mantém-se o limite de 1.000.
- Ficam adiados: serialização de payloads de negócio, datas e decimais exatos; adapters concretos de leitura e escrita; paginação e ausência definitiva; extração de `Location`/sequencial; retry de GET e `Retry-After`; classificação/reconciliação das escritas.
- Não serão antecipados Presenter, fachada, Composition Root, Blank ou integração Scriptcase.
- Não haverá retry genérico de escrita, mudanças no Domain, alterações nas Ports/comandos/resultados públicos da Application, novas dependências ou commits.
