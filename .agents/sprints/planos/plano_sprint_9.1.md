# Sprint 9.1 — Configuração e segredos de produção

**Status:** concluída em 22/09/2026 após preparação local, pausa e
provisionamento manual autorizado no servidor de produção.

## Resumo

Preparar o procedimento de configuração e, após uma pausa explícita, orientar o provisionamento manual no servidor de produção. Não haverá publicação, acesso Oracle, chamada ao PNCP nem implantação da release nesta Sprint.

O código já valida a configuração e falha fechado; portanto, não serão criados novos probes, contratos, ferramentas, classes ou testes automatizados.

## Implementação

- Registrar o plano aprovado em `.agents/sprints/planos/plano_sprint_9.1.md`, necessário para preservar o escopo autorizado e a pausa antes de produção.
- Criar um único guia de configuração de produção em `docs/vigentes/apoio/`, necessário porque o guia atual trata probes de desenvolvimento/homologação, não o provisionamento definitivo.
- Documentar a topologia externa escolhida:
  - `/var/www/configuracoes/pca-pncp/registro.php`;
  - `/var/www/configuracoes/pca-pncp/perfil-producao.php`;
  - `/var/www/configuracoes/pca-pncp/ca-bundle.crt`.
- Definir no guia:
  - `registro.php` como mapa literal do `SERVER_NAME` de produção para o caminho absoluto do perfil;
  - perfil com `ambiente = PRODUCAO`;
  - CNPJ e credenciais fornecidos diretamente no servidor, nunca no Git, chat ou relatório;
  - mapeamentos e limites copiados campo a campo do perfil já validado em homologação;
  - timeouts aprovados pela DI-009: conexão 5 s, login/leitura 30 s e escrita 60 s;
  - CA bundle copiado do arquivo já validado, com hash registrado sem expor seu conteúdo;
  - `PCA_PNCP_PROFILE_REGISTRY_FILE` configurada persistentemente no Apache/PHP, não apenas exportada em um terminal.
- Aplicar a política de leitura mínima:
  - diretório com modo `2750`, preservando o grupo `www-data` por `setgid`;
  - arquivos com modo `640`;
  - `administrator` como proprietário e `www-data` como grupo autorizado;
  - PHP pode ler, mas não escrever; demais usuários não acessam.
- Atualizar o índice documental e o glossário, incluindo “runbook” como “procedimento operacional passo a passo”.
- Após o provisionamento confirmado, atualizar Roadmap e estado operacional com evidências sanitizadas e indicar a Sprint 9.2 como próxima.

## Pausa e provisionamento externo

- Pausar após concluir e validar toda a documentação local.
- Somente após autorização específica, orientar o mantenedor no servidor de produção.
- Não transferir nem solicitar credenciais pelo chat.
- Interromper o provisionamento se houver hostname incerto, parâmetro não aprovado, caminho dentro do webroot/release, permissão excessiva, erro de sintaxe ou divergência do CA bundle.
- A existência possível de publicações legadas será apenas registrada como risco; sua reconciliação pertence à Sprint 9.4.

## Validação e critérios de aceite

- Documentação: conferir links, consistência com DI-009/DI-010, ausência de segredos e `git diff --check`.
- Não executar a suíte PHP completa, pois não haverá alteração de código.
- No servidor, sem Oracle ou PNCP:
  - confirmar caminhos absolutos e externos ao webroot e à release;
  - conferir proprietário, grupo e modos `2750/640`;
  - comprovar leitura e ausência de escrita pela conta do PHP;
  - executar lint de `registro.php` e `perfil-producao.php`;
  - conferir o hash do `ca-bundle.crt`;
  - validar a configuração do Apache e a persistência da variável localizadora.
- Considerar a Sprint concluída somente quando documentação e provisionamento forem comprovados, sem registrar valores sensíveis.

## Artefatos novos e limites

- **Novas classes:** nenhuma; a validação já pertence a `CarregadorPerfilPublicacaoPca` e `PerfilPublicacaoPca`.
- **Novos testes:** nenhum; não haverá comportamento novo de código.
- **Novas pastas no repositório:** nenhuma.
- **Novas ferramentas/probes/contracts:** nenhuma; verificações operacionais simples são suficientes.
- **Novos documentos:** apenas o plano aprovado e o guia de configuração, cada um com consumidor claro.
- Implantação da release, monitoramento, reconciliação do legado e smoke de
  produção permanecem nas Sprints 9.2–9.5.

## Evidências de conclusão

- O perfil de homologação foi usado apenas como referência estrutural
  sanitizada. O perfil local de produção foi validado pelas próprias classes
  `CarregadorPerfilPublicacaoPca` e `PerfilPublicacaoPca`; credenciais e CNPJ
  integral não foram exibidos.
- O registro seleciona literalmente `sgi.semaepiracicaba.sp.gov.br`; o perfil
  declara `PRODUCAO`; e `ca-bundle.crt` contém 152 certificados públicos, sem
  chave privada, com SHA-256
  `b7a69ed0ce1e25aa4a278dd9ef1b7d4dc1fee893be19c20cfd7e866679de61cc`.
- No Debian 12 de produção, Apache usa `mod_php` e `env_module`, com
  `DocumentRoot /var/www/html`. A configuração externa ficou em
  `/var/www/configuracoes/pca-pncp/`, portanto fora do webroot e da release.
- `PCA_PNCP_PROFILE_REGISTRY_FILE` foi provisionada no `VirtualHost` HTTPS. A
  configuração do Apache passou no `configtest`, foi recarregada e permaneceu
  ativa.
- O diretório ficou em `2750`; os três arquivos, em `640`, pertencentes a
  `administrator:www-data`. A conta `www-data` comprovou leitura sem escrita.
- Os dois arquivos PHP passaram no lint, e a validação sanitizada no servidor
  confirmou configuração de produção válida e legível.
- Nenhuma chamada Oracle ou PNCP, instalação de release, execução da Blank ou
  smoke test de produção ocorreu nesta Sprint.
