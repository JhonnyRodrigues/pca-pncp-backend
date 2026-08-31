# Guia de leitura do código PCA/PNCP

**Classificação documental:** Apoio  
**Status:** Vigente  
**Autoridade/Escopo:** navegação e explicação do código sem criar regras novas

## 1. A ideia central

O sistema recebe um CNPJ e um exercício, descobre os Planos homologados, forma
uma ou mais unidades externas, compara a fotografia desejada com o PNCP e leva o
estado externo à convergência. O Domain decide **o que** fazer; a Application
coordena **quando** consultar, escrever, confirmar ou desfazer; a Infrastructure
concreta executa **como** falar com Oracle e PNCP sobre a conexão ADOdb
emprestada e o transporte cURL.

```mermaid
flowchart LR
    H[Scriptcase / futura Presentation] --> U[PublicarPcaUseCase]
    U --> A[Application]
    A --> D[Domain]
    A --> P[Ports]
    I[Infrastructure] --> P
    I --> O[(Oracle)]
    I --> N[PNCP]
```

Os adapters concretos das Sprints 5.2 a 5.8 implementam todas as Ports
vigentes. A Sprint 5.9 comprovou os contratos Oracle e PNCP reais, a Sprint
5.10 encerrou a Fase 5, e as Sprints 6.1 e 6.2 montaram e exercitaram o núcleo
com adapters concretos sobre fronteiras controladas.

## 2. O fluxo completo

```mermaid
flowchart TD
    R[PublicarPcaRequest] --> U[PublicarPcaUseCase]
    U --> PG[PreparadorExecucaoPublicacaoPca]
    PG --> CU[Resolver contexto do usuário]
    PG --> DP[Descobrir identidades de Plano]
    PG --> EA[Adotar ou conferir estratégia anual]
    PG --> AU[Autenticar uma vez no PNCP]
    PG --> CV[CoordenadorVarreduraUnidades]
    CV --> PU[ProcessadorUnidadeInterno]
    PU --> PE[PreparadorUnidadeSobExclusividade]
    PE --> DU[DecisorUnidadeSobExclusividade]
    DU --> PD[PoliticaProcessamentoPlano]
    PD --> DEC{Decisão}
    DEC -->|PUBLICAR| EX[ExecutorComandosUnidade]
    DEC -->|RETIFICAR| EX
    DEC -->|EXCLUIR_PCA| EX
    DEC -->|NENHUMA_ACAO| FI[Finalizador]
    EX --> FI[FinalizadorProcessamentoUnidadeInterno]
    FI --> OF[Observar estado final]
    OF --> RC[Recalcular e reconciliar no máximo uma vez]
    RC --> PL[Persistir estado, confirmação e auditoria]
    PL --> RU[Resultado da unidade]
    RU --> CV
    CV --> RESP[PublicarPcaResponse]
```

### 2.1 Preparação global

`PreparadorExecucaoPublicacaoPca` resolve o usuário, lista somente identidades,
abre uma transação curta para congelar a estratégia anual, forma unidades
individuais ou uma unidade consolidada e autentica uma única vez. Nenhuma
fotografia completa é reconstruída nessa etapa.

### 2.2 Processamento de cada unidade

`PreparadorUnidadeSobExclusividade` abre a transação da unidade, obtém o lock,
relê identidades, reconstrói Planos e consulta o estado operacional. O
`DecisorUnidadeSobExclusividade` observa o PNCP e entrega os fatos à política
pura do Domain. `ExecutorComandosUnidade` apenas traduz a decisão para comandos
tipados. O finalizador comprova o resultado antes de confirmar dados locais.

## 3. As quatro decisões

| Decisão | Quando ocorre | Escrita externa |
|---|---|---|
| `PUBLICAR` | Existe fotografia desejada e não existe PCA ativo conhecido. | Cria o PCA com a fotografia completa. |
| `RETIFICAR` | O PCA ativo difere da fotografia desejada. | Exclui itens, inclui itens e observa, nessa ordem. |
| `EXCLUIR_PCA` | A fotografia ficou vazia e existe PCA ativo. | Exclui integralmente com a justificativa institucional fixa. |
| `NENHUMA_ACAO` | O estado já converge ou ambos os lados estão vazios. | Nenhuma; apenas conclui a unidade localmente. |

O limite de 1000 protege a fotografia desejada. Uma retificação de 1000 para
1000 é válida porque exclui antes de incluir; uma fotografia resultante com
1001 itens é bloqueada antes de qualquer escrita.

## 4. As duas transações

```mermaid
sequenceDiagram
    participant A as Application
    participant U as UnidadeDeTrabalhoPort
    participant P as PNCP
    A->>U: iniciar estratégia anual
    A->>A: conferir/adotar estratégia
    A->>U: confirmar estratégia
    A->>P: autenticar uma vez
    loop para cada unidade externa
        A->>U: iniciar unidade e obter exclusividade
        A->>P: observar estado inicial
        A->>P: escrever, se necessário
        A->>P: observar estado final
        A->>A: persistir confirmação e auditoria
        A->>U: confirmar ou desfazer
    end
```

A transação anual termina antes do PNCP. A transação de uma unidade permanece
aberta durante leitura e escrita externas para preservar exclusividade local.
Qualquer caminho iniciado deve terminar em commit ou rollback.

## 5. Como entender Ports, Results e Enums

- **Port** é uma capacidade que a Application precisa, sem escolher tecnologia.
- **Command** é uma ordem tipada enviada a uma Port de escrita.
- **Result** representa todos os desfechos conhecidos de uma colaboração.
- **Enum** nomeia os estados válidos usados por esses Results.
- **Contexto** transporta fatos já comprovados entre estágios do fluxo.

Não memorize cada classe isoladamente. Leia os pares por capacidade: Port,
Result e Enum de autenticação; depois os de observação; depois escrita; depois
persistência.

## 6. Ports e adapters

| Port | Capacidade | Implementação concreta atual |
|---|---|---|
| `ContextoUsuarioPort` | Resolver Representante e Lotação. | `OracleContextoUsuarioAdapter`. |
| `PlanoRepository` | Descobrir e reconstruir o Aggregate Plano. | `OraclePlanoRepository`. |
| `EstrategiaPublicacaoPort` | Consultar e congelar a estratégia anual. | `OracleEstrategiaPublicacaoAdapter`. |
| `UnidadeDeTrabalhoPort` | Transação e exclusividade. | `OracleUnidadeDeTrabalhoAdapter` sobre ADOdb emprestado. |
| `AutenticadorPncpPort` | Obter sessão opaca. | `AutenticadorPncpAdapter`. |
| `LeitorPncpPort` | Observar PCA e localizar candidatos. | `LeitorPncpAdapter`. |
| `EscritorPncpPort` | Publicar, inserir e excluir. | `EscritorPncpAdapter`. |
| `EstadoOperacionalPublicacaoPort` | Ler e persistir projeção operacional. | `OracleEstadoOperacionalPublicacaoAdapter`. |
| `RegistroAuditoriaPort` | Persistir fato confirmado. | `OracleRegistroAuditoriaAdapter`. |
| `LoggerInterface` | Diagnóstico técnico. | `AcumuladorEventosTecnicos` em memória. |

`PlanoRepository` é uma Port especializada no Aggregate `Plano`.
`OraclePlanoRepository` é sua implementação concreta e, portanto, também
exerce o papel arquitetural de Adapter sem precisar acumular esse sufixo.
Nesse adapter, `MapeamentoPlanosOracle` declara e valida tabela e colunas
físicas usadas na SQL, enquanto `MapeadorDadosPlanoOracle` traduz as linhas já
projetadas por aliases canônicos para objetos do Domain.

## 7. Ordem prática de leitura

1. `MontadorNucleoPublicacaoPca`, que explicita a topologia do núcleo.
2. `PublicarPcaUseCase` e este diagrama.
3. `PreparadorExecucaoPublicacaoPca` e `CoordenadorVarreduraUnidades`.
4. `ProcessadorUnidadeInterno`, que mostra a cadeia de três estágios.
5. `PreparadorUnidadeSobExclusividade`.
6. `DecisorUnidadeSobExclusividade` e `PoliticaProcessamentoPlano`.
7. `ExecutorComandosUnidade`.
8. `FinalizadorProcessamentoUnidadeInterno`, lido como quatro blocos: observar,
   recalcular, persistir e concluir.
9. Results e Enums somente quando aparecerem no caminho estudado.
10. `FluxoConcretoApplicationTest`, que executa o grafo montado sem Infrastructure.
11. `MontadorNucleoPublicacaoPcaIntegrationTest`, que executa o mesmo ponto de
    montagem com os nove adapters concretos sobre ADOdb, cURL e tempo
    controlados.

O [catálogo de arquivos](catalogo_de_arquivos.md) responde onde cada conceito
está e evita procurar pelo repositório inteiro.

## 8. Explicação em cinco minutos

> O PCA homologado no SGI é a fonte da fotografia desejada. A Application
> congela a estratégia anual, autentica no PNCP e processa cada unidade sob um
> lock local. O PNCP é observado, a política do Domain escolhe publicar,
> retificar, excluir ou não agir, e a Application executa essa intenção. Uma
> resposta HTTP nunca basta: o estado externo é observado novamente. Somente a
> convergência permite confirmar as versões locais, registrar auditoria e dar
> commit. Falhas conhecidas viram resultados seguros; falhas sistêmicas
> interrompem sem fabricar resultados para unidades não visitadas.

## 9. O que ainda não existe

Não existem Composition Root final, Controller, Presenter nem fachada
Scriptcase. `MontadorNucleoPublicacaoPca` é o wiring intermediário da Sprint
6.1: recebe Ports e logger já construídos, sem selecionar perfil, instanciar
recursos externos ou antecipar a fronteira pública. O entrypoint da Sprint 5.9
em `tests/Contract/Sprint59/scriptcase_probe.php` pertence somente à suíte
contratual e não antecipa nenhuma dessas fronteiras de produção.

Todas as Ports vigentes possuem adapter concreto e as Fases 5 e 6 estão
concluídas. O gate da Sprint 6.3 comprovou o núcleo executável sem Presentation.
A Sprint 7.1 — Controller e entrada tipada é a próxima etapa na ordem e depende
de planejamento e autorização próprios; nenhum componente da Fase 7 foi
antecipado.
