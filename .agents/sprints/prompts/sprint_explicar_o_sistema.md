Agora quero cumprir a etapa de leitura dialogada antes de iniciar a Sprint 5.1.

Meu objetivo não é apenas possuir documentação sobre o projeto: eu preciso recuperar minha capacidade de entender, navegar e explicar o código que construí.

Use como principais guias:

* `docs/vigentes/apoio/guia_de_leitura_do_codigo.md`;
* `docs/vigentes/apoio/catalogo_de_arquivos.md`;

e consulte o código real sempre que necessário para explicar o comportamento concreto.

Não altere código nem documentação durante esta atividade.

## Método de ensino

Ensine do todo para o detalhe.

Não apresente dezenas de classes de uma vez e não tente me fazer memorizar todos os Results, Enums e Ports isoladamente.

Sempre que introduzir uma classe, explique de forma prática:

1. qual problema ela resolve;
2. quem a chama;
3. quem ela chama;
4. o que recebe;
5. o que devolve;
6. onde ela aparece no fluxo geral;
7. por que essa responsabilidade não está no Domain ou na Infrastructure.

Use exemplos concretos do código e linguagem acessível, mas preserve os conceitos técnicos corretos de DDD e Clean Architecture.

Quando ajudar, represente o fluxo com diagramas Mermaid, árvores ou sequências simples.

## Leitura dialogada em etapas

Quero estudar em cinco etapas:

### 1. Visão geral do produto e das camadas

Explique primeiro:

* qual problema o backend resolve;
* o papel de Domain, Application, Ports e futura Infrastructure;
* o fluxo geral:

`Request → UseCase → preparação global → varredura → processamento da unidade → decisão do Domain → escrita → observação/reconciliação → persistência/auditoria → Response`

Apresente apenas as classes essenciais para eu enxergar esse mapa.

### 2. Domain

Explique:

* `Plano`;
* `Demanda`;
* principais Value Objects;
* `PoliticaProcessamentoPlano`;
* as decisões `PUBLICAR`, `RETIFICAR`, `EXCLUIR_PCA` e `NENHUMA_ACAO`.

Quero entender claramente onde termina a decisão de negócio e começa a coordenação da Application.

### 3. Application pelo fluxo feliz

Percorra primeiro um cenário concreto de `PUBLICAR`, desde `PublicarPcaUseCase` até a `PublicarPcaResponse`.

Mostre a cadeia real de chamadas e explique progressivamente:

* `PreparadorExecucaoPublicacaoPca`;
* `CoordenadorVarreduraUnidades`;
* `ProcessadorUnidadeInterno`;
* `PreparadorUnidadeSobExclusividade`;
* `DecisorUnidadeSobExclusividade`;
* `ExecutorComandosUnidade`;
* `FinalizadorProcessamentoUnidadeInterno`.

Depois compare brevemente o que muda em `RETIFICAR`, `EXCLUIR_PCA` e `NENHUMA_ACAO`.

Não aprofunde todos os Results e Enums antecipadamente; apresente-os somente quando aparecerem no caminho estudado.

### 4. Falhas e transações

Explique as duas transações separadamente:

* congelamento da estratégia anual;
* processamento de uma unidade externa.

Mostre:

* quando começam;
* quando terminam;
* por que são separadas;
* quando ocorre commit;
* quando ocorre rollback;
* como funciona escrita incerta;
* por que existe nova observação;
* por que há no máximo uma reconciliação adicional.

Dê atenção especial ao `FinalizadorProcessamentoUnidadeInterno`, mas explique-o conceitualmente em blocos, sem percorrer suas centenas de linhas sequencialmente.

### 5. Testes e preparação para Infrastructure

Use principalmente `FluxoConcretoApplicationTest` para mostrar como todo o grafo funciona hoje sem adapters concretos.

Depois explique:

* quais Ports ainda são doubles;
* qual futuro adapter deverá implementar cada Port;
* o que mudará e o que não deverá mudar quando iniciarmos a Fase 5.

Ao final, quero conseguir explicar o backend em aproximadamente cinco minutos.

## Dinâmica obrigatória

Execute somente uma etapa por vez.

Ao terminar cada etapa:

* faça um resumo curto;
* apresente um pequeno mapa mental do que estudamos;
* faça de 2 a 4 perguntas para verificar se eu realmente compreendi;
* aguarde minhas perguntas e minha validação antes de avançar.

Não avance automaticamente para a etapa seguinte.

Comece exclusivamente pela Etapa 1 — Visão geral do produto e das camadas.
