| Conceito     | O que significa no projeto                                                                                   |
| ------------ | ------------------------------------------------------------------------------------------------------------ |
| Entity       | Objeto que representa um conceito do negócio e possui identidade (`Plano`, `Demanda`, `Procedimento`).       |
| Value Object | Objeto imutável que representa um valor com regras próprias (`Cnpj`, `AnoExecucao`, `CodigoUnidade`).        |
| Port         | Interface que define um contrato entre o domínio e o mundo externo (`PlanoRepository`, `DemandaRepository`). |
| Repository   | Implementação de uma Port que acessa o banco de dados (`OraclePlanoRepository`).                             |
| Use Case     | Orquestra o fluxo da aplicação (`PublicarPcaUseCase`).                                                       |
| Presenter    | Converte a resposta do caso de uso para o formato esperado pelo consumidor (Scriptcase).                     |
| Controller   | Recebe a chamada do Scriptcase, cria o Request, executa o Use Case e devolve o resultado.                    |
