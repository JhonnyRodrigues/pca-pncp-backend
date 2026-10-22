# Artefatos gerados

Esta pasta recebe os resultados locais das ferramentas de construção,
verificação e empacotamento do projeto. Esses artefatos podem refletir o estado
de uma cópia de trabalho específica e não constituem fonte do código, da
configuração ou da documentação vigente.

O único arquivo versionado em `build/` é este README. Os demais arquivos e
subdiretórios são ignorados pelo Git.

## Conteúdo comum

- `pca-pncp-backend/`: pacote autocontido produzido pelo fluxo normal;
- `pca-pncp-backend_DATA_COMMIT.tar.gz`: release compactada para transferência;
- `composer-cache/`: cache local usado durante a construção;
- outros diretórios e arquivos: artefatos temporários de verificações, gates ou
  procedimentos expressamente autorizados.

Os nomes disponíveis podem variar conforme as construções já executadas e os
artefatos que ainda precisam ser transferidos ou comparados.

## Construção e verificação

O fluxo normal é executado a partir da raiz do repositório:

```bash
composer package:build
composer package:verify
composer package:archive
```

Consulte as [ferramentas do projeto](../tools/README.md) para compreender o
fluxo e o
[catálogo de comandos Composer](../docs/vigentes/apoio/catalogo_de_comandos_composer.md)
para conhecer os comandos ativos e seus efeitos.

Nenhum artefato presente nesta pasta comprova, por si só, que uma implantação
foi realizada em um servidor.

## Limpeza local

Com exceção deste README, todo o conteúdo de `build/` é gerado e não
versionado. **Ele pode ser apagado quando nenhum pacote ainda precisar ser
transferido ou comparado**. A limpeza de `build/` não substitui a remoção
controlada de artefatos já enviados a um servidor.
