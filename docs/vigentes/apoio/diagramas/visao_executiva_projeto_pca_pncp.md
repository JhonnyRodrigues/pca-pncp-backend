# Visão executiva do projeto PCA/PNCP

**Classificação documental:** Apoio  
**Status:** Vigente  
**Escopo:** visão atemporal do problema, da solução e de seus principais controles

Este documento explica visualmente como o sistema mantém o Plano de
Contratações Anual do SGI alinhado ao PNCP. Ele não substitui as regras e
decisões das [fontes autoritativas](../../../README.md) nem a
[situação operacional](../../operacional/situacao_operacional.md), que
concentra a condição do produto, as restrições correntes e as ações seguras.

## O problema e a solução

```mermaid
flowchart LR
    SGI["SGI<br/>estado desejado"] --> CONVERGENCIA["Coordenação da<br/>convergência"]
    PNCP["PNCP<br/>estado observado"] --> CONVERGENCIA

    subgraph CONTROLES["Controles aplicados pelo sistema"]
        direction TB
        DECISAO["Decidir: publicar, retificar,<br/>excluir ou não alterar"]
        CONCORRENCIA["Conter concorrência<br/>por unidade"]
        INCERTEZA["Tratar escritas incertas<br/>por nova observação"]
        RASTREIO["Preservar correlação,<br/>auditoria e eventos técnicos"]
    end

    CONVERGENCIA --> DECISAO
    DECISAO --> CONCORRENCIA --> INCERTEZA --> RASTREIO
    RASTREIO --> RESULTADO["Estado convergente comprovado<br/>e confirmação segura no SGI"]
```

O sistema está construído, integrado à aplicação Blank do Scriptcase,
empacotado como release autocontida e instalado em produção. Mudanças de fase,
pendências externas e permissões para novas execuções são deliberadamente
registradas apenas no estado operacional, evitando que esta visão envelheça.

## É um processo de sincronização

```mermaid
flowchart LR
    A["Planos homologados<br/>estado desejado"] --> C["Comparação e<br/>decisão"]
    B["PCA no PNCP<br/>estado observado"] --> C
    C --> D["Escrita necessária"]
    D --> E["Nova observação"]
    E --> F["Confirmação local<br/>e auditoria"]
```
> A nova observação é importante porque uma resposta técnica de sucesso não prova, sozinha, que o estado externo ficou correto.
>
> O objetivo real é alcançar a convergência: estado observado no PNCP = estado desejado pelo sistema

## O fluxo geral

```mermaid
flowchart TD
    R["Request<br/>CNPJ + exercício"] --> U["Use Case"]
    U --> PG["Preparação global"]
    PG --> V["Varredura das<br/>unidades externas"]

    V --> PU["Processamento<br/>de uma unidade"]
    PU --> C["Carregar fotografia desejada<br/>e estado observado"]
    C --> D["Domain decide"]

    D -->|PUBLICAR| E["Escrita"]
    D -->|RETIFICAR| E
    D -->|EXCLUIR_PCA| E
    D -->|NENHUMA_ACAO| FS["Finalização sem escrita<br/>usa a observação inicial"]

    E --> O["Nova observação"]
    O --> RC["Reconciliação, se necessária"]
    RC --> PA["Persistência local<br/>projeção operacional e<br/>confirmação da versão vigente"]
    O --> PA
    FS --> PA

    PA -->|operação externa confirmada| A["Auditoria"]
    PA -->|NENHUMA_ACAO| V
    A --> V

    V --> RESP["Response global"]
```

### Cenário de escrita incerta

```mermaid
flowchart TD
    W["Escrita com resultado incerto"] --> O["Observar o PNCP"]
    O --> P["Recalcular com a política"]

    P -->|NENHUMA_ACAO| C["Convergência comprovada<br/>persistir e commit"]
    P -->|Ainda falta algo| R["Uma reconciliação controlada"]
    R --> O2["Observar novamente"]
    O2 --> P2["Recalcular com a política novamente"]

    P2 -->|NENHUMA_ACAO| C
    P2 -->|Ainda divergente| F["Falha reconciliável<br/>rollback local"]
```

## A prova de convergência

> Depois da operação, a política passa a dizer que nenhuma alteração é
> necessária.

```mermaid
flowchart LR
    A["Antes<br/>PUBLICAR"] --> B["Escrita"]
    B --> C["Nova observação"]
    C --> D{"Política decide"}
    D -->|NENHUMA_ACAO| E["Convergência comprovada"]
    D -->|outra decisão| F["Ainda existe divergência"]
```

## Controle de concorrência por lock

```mermaid
sequenceDiagram
    participant A as Requisição A
    participant L as Lock da UO 101
    participant B as Requisição B

    A->>L: adquirir exclusividade
    L-->>A: permitido

    B->>L: adquirir a mesma exclusividade
    L-->>B: bloqueado/em espera

    A->>A: processa e confirma
    A->>L: libera

    L-->>B: agora pode prosseguir
```

## Mapa completo da chamada

```mermaid
sequenceDiagram
    participant Entrada
    participant UC as PublicarPcaUseCase
    participant PG as PreparadorExecucao
    participant CV as CoordenadorVarredura
    participant PU as ProcessadorUnidade
    participant Prep as PreparadorUnidade
    participant Dec as DecisorUnidade
    participant Dom as Política do Domain
    participant Exec as ExecutorComandos
    participant PNCP as Ports PNCP
    participant Fin as Finalizador
    participant Local as Persistência local

    Entrada->>UC: Request + correlação
    UC->>PG: preparar(request)
    PG-->>UC: contexto + sessão + unidades

    UC->>CV: processar(unidades)
    CV->>PU: processar(unidade)

    PU->>Prep: preparar(unidade)
    Prep-->>PU: Planos + estado local sob exclusividade

    PU->>Dec: decidir(contexto)
    Dec->>PNCP: localizar candidatos
    PNCP-->>Dec: nenhum candidato
    Dec->>Dom: desejado + local + observado
    Dom-->>Dec: PUBLICAR [1001,1002]

    PU->>Exec: executar decisão
    Exec->>PNCP: publicar comando
    PNCP-->>Exec: ACEITA, sequencial 77

    Exec-->>Fin: resultado da escrita
    Fin->>PNCP: observar PCA 77
    PNCP-->>Fin: PCA 77 com [1001,1002]
    Fin->>Dom: recalcular decisão
    Dom-->>Fin: NENHUMA_ACAO

    Fin->>Local: estado + confirmação + auditoria
    Fin->>Local: commit
    Fin-->>CV: unidade CONCLUIDA
    CV-->>UC: varredura concluída
    UC-->>Entrada: PublicarPcaResponse CONCLUIDA
```
