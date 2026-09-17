# Visão do projeto PCA-PNCP-BACKEND

**Classificação documental:** Apoio  
**Status:** Vigente  
**Referência temporal:** estado operacional atualizado em 02/09/2026

Este diagrama resume o estado corrente sem substituir o [estado operacional](../vigentes/operacional/estado_atual.md) nem a ordem de
construção definida pelo [Roadmap](../vigentes/autoritativos/roadmap.md).

```mermaid
flowchart TB
    subgraph COMPLEXIDADE["POR QUE O PROJETO É COMPLEXO"]
        direction LR
        NEGOCIO["Regras do PCA<br/>publicar, retificar, excluir<br/>ou não alterar"]
        ORACLE["Sistema interno<br/>Banco de dados, transações,<br/>bloqueios e auditoria"]
        PNCP["Serviço nacional<br/>autenticação, rede,<br/>paginação e limites"]
        CONSISTENCIA["Consistência entre dois sistemas:<br/>um efeito no SGI não é desfeito<br/>automaticamente no PNCP"]

        NEGOCIO --> CONSISTENCIA
        ORACLE --> CONSISTENCIA
        PNCP --> CONSISTENCIA
    end

    CONSISTENCIA --> CARGA["ALTA CARGA COGNITIVA<br/>combinar regras de negócio, concorrência,<br/>falhas parciais, segurança e rastreabilidade"]

    subgraph CONSTRUIDO["NÚCLEO JÁ CONSTRUÍDO"]
        direction LR
        MODELO["Regras e modelo<br/>de negócio"]
        APLICACAO["Núcleo de decisão,<br/>orquestração do fluxo de comunicação dos componentes e<br/> conferência após falhas"]
        INTEGRACOES["Conectores construídos<br/>para Oracle e PNCP"]
        FRONTEIRA["API segura,<br/>apresentação de resultados tratados e composição"]

        MODELO --> APLICACAO --> INTEGRACOES --> FRONTEIRA
    end

    CARGA --> MODELO
    FRONTEIRA --> SEM_TELA["A tela é apenas a ponta visível.<br/>O backend que a sustenta já foi construído;<br/>o pacote final ainda não foi implantado em produção."]

    SEM_TELA --> AGORA["FASE ATUAL — TESTES E INTEGRAÇÃO"]

    subgraph PROVAS["CENÁRIOS QUE AINDA PRECISAM SER COMPROVADOS NO FLUXO COMPLETO"]
        direction LR
        NORMAIS["Normais:<br/>publicar, retificar, excluir<br/>e nenhuma ação<br/>Estratégias: publicação individual de planos ou consolidado"]
        CONCORRENTES["Concorrentes<br/>execuções simultâneas,<br/>bloqueio e liberação"]
        FALHAS["Falhas e incertezas<br/>tempo esgotado, efeito parcial,<br/>conferência e interrupção"]
        SEGURANCA["Segurança e operação<br/>segredos protegidos,<br/>logs seguros, auditoria de procedimentos e pacote íntegro"]
    end

    AGORA --> NORMAIS
    AGORA --> CONCORRENTES
    AGORA --> FALHAS
    AGORA --> SEGURANCA

    NORMAIS --> GATE{"Todos os comportamentos<br/>foram comprovados?"}
    CONCORRENTES --> GATE
    FALHAS --> GATE
    SEGURANCA --> GATE

    GATE -- "Não" --> AJUSTAR["Corrigir, conferir o estado<br/>e repetir os testes"]
    AJUSTAR --> AGORA
    GATE -- "Sim" --> HOMOLOGACAO["PRÓXIMO ESTÁGIO<br/>implantação controlada para homologação<br/>e teste de funcionamento"]

    HOMOLOGACAO --> APROVACAO{"Testes e homologação<br/>foram aprovados?"}
    APROVACAO -- "Não" --> AJUSTAR
    APROVACAO -- "Sim" --> OPERACAO["Preparação para produção — Fase 9<br/>configuração, observabilidade,<br/>release, recuperação e suporte"]
    OPERACAO --> PRODUCAO["PRODUÇÃO<br/>somente após as aprovações<br/>e os controles operacionais"]

    classDef complexidade fill:#fff3cd,stroke:#b58105,color:#3d2b00,stroke-width:2px;
    classDef construido fill:#d9ead3,stroke:#38761d,color:#17330d,stroke-width:2px;
    classDef atual fill:#cfe2f3,stroke:#0b5394,color:#073763,stroke-width:3px;
    classDef prova fill:#eaf2f8,stroke:#3d85c6,color:#17365d,stroke-width:1.5px;
    classDef gate fill:#fce5cd,stroke:#e69138,color:#783f04,stroke-width:2px;
    classDef futuro fill:#eadcf8,stroke:#674ea7,color:#351c75,stroke-width:2px;
    classDef producao fill:#d9ead3,stroke:#274e13,color:#17330d,stroke-width:3px;

    class NEGOCIO,ORACLE,PNCP,CONSISTENCIA,CARGA complexidade;
    class MODELO,APLICACAO,INTEGRACOES,FRONTEIRA,SEM_TELA construido;
    class AGORA atual;
    class NORMAIS,CONCORRENTES,FALHAS,SEGURANCA prova;
    class GATE,APROVACAO gate;
    class AJUSTAR,HOMOLOGACAO,OPERACAO futuro;
    class PRODUCAO producao;
```

> Foi empregado tempo considerável na construção e na proteção do núcleo que coordena dois sistemas com comportamentos diferentes. O projeto está comprovando, de forma controlada, que o núcleo já implementado é seguro para avançar até homologação e, somente depois das aprovações e da preparação operacional, até produção.

# É um processo de sincronização:
```mermaid
flowchart LR
    A["Planos homologados<br/>estado desejado"] --> C["Comparação e<br/>decisão"]
    B["PCA no PNCP<br/>estado observado"] --> C
    C --> D["Escrita necessária"]
    D --> E["Nova observação"]
    E --> F["Confirmação local<br/>e auditoria"]
```
> A nova observação é importante porque uma resposta técnica de sucesso não prova, sozinha, que o estado externo ficou correto. O objetivo real é alcançar convergência: 
> estado observado no PNCP = estado desejado pelo sistema

# O fluxo geral

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

## Cenário de escrita incerta

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

# A prova de convergência

>Depois da operação, a política passa a dizer que nenhuma alteração é necessária.

```mermaid
flowchart LR
    A["Antes<br/>PUBLICAR"] --> B["Escrita"]
    B --> C["Nova observação"]
    C --> D{"Política decide"}
    D -->|NENHUMA_ACAO| E["Convergência comprovada"]
    D -->|outra decisão| F["Ainda existe divergência"]
```

# A solução para lidar com o problema da concorrência: lock

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

# 1. O mapa completo da chamada

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