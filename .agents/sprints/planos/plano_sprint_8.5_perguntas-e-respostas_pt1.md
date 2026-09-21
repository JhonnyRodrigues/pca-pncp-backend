Suas perguntas fazem sentido. Elas revelam uma diferença que a documentação poderia deixar mais clara: o pacote atual é um candidato de homologação, enquanto a Fase 9 produzirá e implantará a release de produção.

# O pacote foi construído cedo demais?

Não, porque ele pode ser reconstruído várias vezes. O pacote atual não está congelado, versionado ou pronto para produção. Ele é descartável, fica fora do Git e serve para descobrir problemas de empacotamento antes do smoke test.

O fluxo esperado é:

```text
código atual
    ↓
pacote candidato
    ↓
testes em homologação
    ↓
correções e ajuda ao operador
    ↓
pacote candidato reconstruído
    ↓
Fase 9: release de produção
```

Construir cedo permite descobrir agora problemas como:

- dependência ausente em vendor/;
- autoload que funciona apenas no checkout;
- arquivo de configuração incluído indevidamente;
- caminho incompatível com o Scriptcase;
- código que depende de ferramentas de desenvolvimento.

Se todo o código ainda estivesse mudando profundamente, seria cedo. Neste roadmap, o backend principal já está construído. Após o Microciclo 5, a alteração funcional restante é principalmente a ajuda da Blank, no Microciclo 9. O Microciclo 10 reconstrói e verifica o artefato.

Eu deveria ter chamado o resultado do Microciclo 5 de pacote candidato de homologação. Apenas “pacote” pode sugerir que ele já é a release definitiva.

# O que teremos ao terminar a Sprint 8.5?

Teremos a V1 funcional comprovada em homologação:

- backend integrado à Blank;
- falhas e contenção verificadas;
- eventos técnicos persistidos pelo Scriptcase;
- pacote candidato executado com PHP 8.1;
- interface e resultados verificados;
- ajuda para o operador;
- regressões e evidências finais.

Ainda não será um projeto pronto para produção.

A Fase 9 cuidará do que transforma uma aplicação funcional em um serviço operável:

| Sprint 8.5 | Fase 9 |
|---|---|
| Pacote candidato para homologação | Release versionada de produção |
| Perfil de homologação | Perfil e segredos de produção |
| Smoke funcional | Implantação e smoke de produção |
| Eventos tecnicamente comprovados | Monitoramento, alertas e retenção |
| Recuperação observada nos testes | Runbooks e procedimentos operacionais |
| Massa controlada | Reconciliação dos dados legados |
| Verificação do pacote | Instalação, atualização e rollback da release |
| Resultado funcional | Responsáveis e rotina de suporte |

A Sprint 8.5 conclui a integração técnica. A Fase 9 prepara a operação real.

# Por que `tools/package/build.php`?

O primeiro nome escolhido foi `build-sprint85-package.php`, orientado à ação:

```text
build-sprint85-package.php
│     │        └── objeto produzido
│     └── escopo temporário
└── ação
```

Scripts de linha de comando frequentemente começam com um verbo: build, verify, run ou render. Por isso não usei package-builder.php, que descreve o componente, mas não deixa tão direta a ação executada.

O problema é que o script continuará útil depois da Sprint 8.5. Colocar a
sprint no nome o tornava artificialmente temporário. O corretivo do
Microciclo 5 adotou nomes menores e permanentes:

```text
tools/package/build.php
tools/package/verify.php
```

## Diferença entre construir e verificar

Os scripts possuem responsabilidades diferentes:

- Build: apaga o pacote candidato anterior, copia o código, executa Composer, gera o autoload e cria o manifesto. Ele modifica arquivos.
- Verify: recebe um pacote já construído e confere estrutura, hashes, conteúdo proibido e carregamento da fachada. Ele somente inspeciona.

Separá-los permite verificar novamente o mesmo pacote sem reconstruí-lo. Isso é útil imediatamente antes de enviá-lo pelo FileZilla.

# Por que `tools/package/`?

`tools` indica programas auxiliares que não pertencem ao runtime do backend.
`release` é um nome comum para ferramentas de empacotamento, versionamento e
publicação.

Porém, não é um padrão obrigatório. Neste projeto, release pode sugerir que já estamos produzindo a release definitiva da Fase 9. Um caminho mais claro seria:

```text
tools/package/build.php
tools/package/verify.php
```

Esse nome comunica diretamente:

- são ferramentas;
- trabalham com o pacote;
- uma constrói;
- outra verifica.

Essa estrutura foi adotada porque descreve a atividade atual sem sugerir que o
pacote candidato já é a release definitiva da Fase 9.

# Era necessário criar tests/Contract/Sprint85?

A pasta tem uma razão real: o plano aprovado exigiu suporte opt-in, consultas completas e PRECHECK. Os arquivos ali não criam outra camada do backend. Eles ficam fora do pacote e existem para impedir que os gates reais sejam executados com:

- ambiente errado;
- consentimentos errados;
- mutação PNCP habilitada;
- massa Oracle divergente;
- SQL mutante;
- checkpoint diferente do aprovado.

Esse suporte é necessário porque os Microciclos 6, 7 e 8 acessarão recursos reais.

Uma parte ficou excessiva: `Sprint85PackageContractTest.php` inspecionava o
texto dos scripts de pacote. O próprio verificador comprova o pacote real com
mais valor, por isso esse teste foi removido no corretivo do Microciclo 5.

Eu manteria:

- configuração protegida;
- consultas Oracle;
- PRECHECK;
- probe Scriptcase;
- testes comportamentais de segurança.

O teste que apenas procurava textos dentro dos scripts foi removido.

# `build/pca-pncp-backend` ou `build/sprint85/backend`?

A forma adotada foi `build/pca-pncp-backend`, um caminho reutilizável que
identifica o projeto.

build/sprint85/backend mistura três conceitos:

- diretório de construção;
- sprint que o criou;
- nome esperado no servidor.

A sprint não deveria fazer parte da identidade permanente do pacote. O pacote representa o projeto.

Existe apenas um cuidado: a Blank procura: backend/vendor/autoload.php

Portanto, no FileZilla, o conteúdo de build/pca-pncp-backend deverá ser enviado para a pasta remota chamada backend. O nome local e o destino remoto não precisam ser iguais, mas isso deve ficar explícito nas instruções.

O corretivo aceito antes do Microciclo 6:

1. moveu os scripts para `tools/package/build.php` e `tools/package/verify.php`;
2. passou a gerar em `build/pca-pncp-backend`;
3. removeu `Sprint85PackageContractTest.php`;
4. manter os contratos de segurança dos gates;
5. atualizou comandos e documentação;
6. reconstruiu e verificou o pacote candidato.

Isso reduz nomes temporários e testes pouco úteis sem enfraquecer a segurança dos próximos gates.
