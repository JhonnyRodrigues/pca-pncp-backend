# Ferramentas do projeto

Esta pasta contém programas versionados que constroem ou verificam
entregáveis. Ela guarda as ferramentas; os resultados descartáveis ficam em
`build/`, que é ignorado pelo Git e pode ser removido e reconstruído.

## Fluxo normal de release

Execute a partir da raiz do repositório:

```bash
composer package:build
composer package:verify
composer package:archive
```

- `build.php` monta o pacote autocontido com `src/`, dependências de produção e
  manifesto;
- `verify.php` confere hashes, topologia, conteúdo permitido e autoload;
- `archive.php` exige o estado Git esperado e produz o arquivo de transferência.

O atalho `composer package:release` executa as três etapas na ordem. Nenhuma
delas implanta arquivos ou acessa Oracle, PNCP ou Scriptcase.

## Ferramentas temporárias preservadas

Os construtores e verificadores que mencionam Sprint 8.5 ou Sprint 9.4
reproduzem gates e pacotes corretivos já encerrados. Eles permanecem
versionados como fonte e evidência; não fazem parte do fluxo normal de release
e não devem ser executados novamente sem um plano e autorização específicos.

Não remova uma ferramenta isoladamente: seus consumidores podem estar em
`composer.json`, testes e documentação. Para consultar todos os comandos e
seus efeitos, use o
[catálogo de comandos Composer](../docs/vigentes/apoio/catalogo_de_comandos_composer.md).

## Limpeza local

Todo o conteúdo de `build/` é gerado e não versionado. Ele pode ser apagado
quando nenhum pacote ainda precisar ser transferido ou comparado. A limpeza de
`build/` não substitui a remoção controlada de artefatos já enviados a um
servidor.
