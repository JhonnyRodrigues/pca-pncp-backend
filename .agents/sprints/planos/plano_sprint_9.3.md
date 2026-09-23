# Sprint 9.3 — Empacotamento e implantação

**Status:** concluída em 22/09/2026 após implantação inicial autorizada.

## Objetivo e fontes

Produzir uma release autocontida, documentar a implantação inicial e o
rollback e preparar a instalação manual do backend em produção, sem executar
Composer no servidor.

O escopo vem da [Sprint 9.3 do roadmap](../../../docs/vigentes/autoritativos/roadmap.md),
da [DI-010](../../../docs/vigentes/autoritativos/decisoes_de_integracao.md), do
[estado operacional](../../../docs/vigentes/operacional/estado_atual.md) e da
decisão do mantenedor registrada neste plano.

## Decisões do mantenedor

- Esta é a implantação inicial da nova aplicação; a aplicação anterior será
  descontinuada.
- A entrega será manual e temporariamente feita com FileZilla pelo protocolo
  SFTP. A integração com o Gitea e seu deploy automático fica como melhoria
  futura não bloqueante.
- O destino do backend é
  `/var/www/html/PCA_PUBLICAR_B/pca-pncp-backend/`.
- A aplicação anterior não será mantida como mecanismo de rollback. Se a nova
  instalação falhar, ela será retirada de serviço antes de qualquer publicação.
- Esta sprint não executa Oracle, PNCP, publicação real nem smoke test. Esses
  avanços pertencem às sprints seguintes.

## Implementação local

1. Proteger por teste e ajustar o caminho do autoload usado pela Blank para
   `pca-pncp-backend/vendor/autoload.php`.
2. Acrescentar os aliases permanentes `package:build` e `package:verify`,
   reutilizando as ferramentas de pacote existentes e preservando os aliases
   históricos da Sprint 8.5.
3. Documentar construção, envio SFTP, instalação inicial, atualização e
   rollback no guia operacional da Sprint 9.3.
4. Atualizar os índices, catálogos, glossário, roadmap e estado operacional.
5. Executar teste focado, suíte local, lint, validação do Composer, construção,
   verificação do pacote e inspeção do diff.

Em ampliação autorizada pelo mantenedor durante a implantação inicial manual,
acrescentar `package:archive` para compactação e checksum e `package:release`
para orquestrar construção, verificação e compactação em um único comando.

## Pausa obrigatória antes de produção

A preparação local não autoriza acesso ao servidor. Depois de um commit que
identifique a release, o pacote final deve ser reconstruído, compactado como
`.tar.gz` e acompanhado de seu SHA-256. A implantação em produção exige nova
autorização explícita e segue o
[guia de implantação e rollback](../../../docs/vigentes/apoio/guia_de_implantacao_e_rollback.md).

## Artefatos e justificativas

- Nenhuma classe nova: não existe nova regra nem responsabilidade de runtime.
- `PackageReleaseToolTest.php`: necessário para proteger os novos aliases, a
  topologia do `.tar.gz`, a recusa de sobrescrita e o worktree limpo.
- `tools/package/archive.php`: necessário porque a identificação pelo commit,
  a compactação portátil, a prevenção de colisões e o SHA-256 formavam uma
  sequência manual repetitiva e sujeita a erro.
- Nenhuma pasta nova: os documentos entram nas estruturas vigentes.
- `plano_sprint_9.3.md`: preserva escopo, limites e autorização desta sprint.
- `guia_de_implantacao_e_rollback.md`: transforma decisões operacionais em um
  procedimento executável e verificável pelo mantenedor.

## Critérios de conclusão

- release contém somente `src/`, `vendor/` e `PACKAGE-MANIFEST.json`;
- pacote passa em `package:verify` e não contém configuração ou segredo;
- Composer não é necessário em produção;
- caminho da Blank corresponde ao destino aprovado;
- instalação, atualização e rollback estão documentados;
- produção é alterada somente após autorização específica.

## Evidências da preparação local

- O ciclo TDD focado falhou primeiro apenas pelo caminho antigo e passou depois
  da alteração com 9 testes e 115 asserções.
- A suíte local passou com 954 testes e 4.542 asserções; o lint aprovou 402
  arquivos PHP; e o autoload PSR-4 estrito gerou 1.843 classes.
- `composer.json` e `composer.lock` estão coerentes. Permanece apenas o aviso
  anterior de licença não declarada.
- O pacote candidato passou em `package:verify` com 196 arquivos de conteúdo e
  197 no total, sem dependências de desenvolvimento, configurações ou segredos.
  As duas fachadas públicas são carregáveis. O SHA-256 do manifesto candidato é
  `6c55a1566e795d2765d4048ea38570d923312b163bfd2e47621ab2461bc89b18`.
- A release `137f933` foi compactada e transferida com SHA-256
  `ca1600244391efbc6acc3150e0868da5e3787cdbac87fa2352df9495691ae97c`.
  O backend foi extraído em produção com a topologia esperada. O PHP CLI como
  `www-data` carregou as duas fachadas, mas informou que o OCI8 não encontrou
  `libmql1.so`; isso não valida o runtime Apache nem a conexão Oracle.
- A ampliação da automação passou por TDD com 3 testes e 35 asserções. A suíte
  completa passou com 957 testes e 4.577 asserções; o lint aprovou 404 arquivos
  PHP; e o PSR-4 estrito gerou 1.844 classes.
- O `.tar.gz` e a Blank anterior foram removidos do webroot. A nova versão da
  Blank foi compilada e publicada, e o mantenedor confirmou que a tela
  `PCA_PUBLICAR_B` abriu normalmente pelo menu do SGI.
- A abertura da tela encerra a verificação de implantação desta sprint sem
  constituir smoke funcional. O botão **Publicar** não foi acionado; nenhuma
  publicação, conexão Oracle ou chamada PNCP foi executada nesta sprint.
