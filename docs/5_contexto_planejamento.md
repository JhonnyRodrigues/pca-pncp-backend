# Exportação de Contexto: Projeto PCA_PUBLICAR_B Backend

## Resumo Executivo
Projeto de backend em PHP para publicação e retificação de planos PCA no PNCP.
Iniciou com código legado em Scriptcase e precisa ser reconstruido para Clean Architecture em camadas (Domínio, Aplicação, Infraestrutura, Apresentação).

## Estrutura do Projeto

### Localização
- Workspace: `~/Desktop/backend`
- Linguagem: PHP 8.1
- Ferramentas: Composer com autoloader, PHPUnit para testes

### Arquitetura Ideal
\`\`\`
src/
  Application/UseCases/PublicarPca/
    PublicarPcaRequest.php
    PublicarPcaResponse.php
    PublicarPcaValidator.php
    PublicarPcaPresenter.php
    PublicarPcaUseCase.php
  Domain/
    Entities/
      Plano.php
      Demanda.php
      Procedimento.php
    ValueObjects
      Cnpj.php
      AnoExecucao.php
      UnidadeOrcamentaria.php
    Ports/
      PlanoRepository.php
      DemandaRepository.php
      ProcedimentoRepository.php
    Exceptions
  Infrastructure/
    Client/
      PncpClient.php
      PncpPayload.php
    Database/
      OracleConnection.php
    Repository/
      OraclePlanoRepository.php
      OracleDemandaRepository.php
      OracleProcedimentoRepository.php
    Mapping/
      UnidadePncpMapper.php
    Logger/
  Presentation/Http/Controllers/
    PcaController.php
  Repository/
    OraclePcaRepository.php (implementação para SGBD oracle versao 19)
  DTO/
    PcaDTO.php
tests/
  PlanoTest.php
  DemandaTest.php
  PublicarPcaRequestTest.php
  PublicarPcaPresenterTest.php
  PncpServiceTest.php
  PlanoPayloadTest.php
\`\`\`

## Princípios para aplicar

1. **Dependência para dentro**: Domínio → Aplicação → Infraestrutura ← Apresentação
2. **Entidades de domínio**: Plano e Demanda representam conceitos de negócio
3. **Casos de uso**: PublicarPcaUseCase orquestra o fluxo
4. **Portas**: PcaRepository define contrato, não implementação
5. **Presenter**: Response passa por presenter antes de JSON
6. **DTOs**: Request/Response separam fronteiras

## Testes necessários

Cobertura:
- Plano: regra de publicação ou retificação
- Demanda: mapeamento para payload
- Request: transformação de entrada
- Serviço: fluxo principal com dependências injetadas
- Response: transformação de saída
- Presenter: formatação para HTTP

Comando para rodar todos:
\`\`\`bash
cd ~/Desktop/backend
php phpunit-10.phar --bootstrap autoload.php
\`\`\`

## Comando Padrão de Teste
<!-- php phpunit-10.phar --bootstrap autoload.php tests/<NomeDoTeste>.php -->
\`\`\`bash
php.exe vendor/bin/phpunit
\`\`\`

## Informações Técnicas

### Ambiente
- PHP: 8.1
- PHPUnit: 10.5.63 (compatível com PHP 8.1, não 8.2+)
- Banco: Oracle (credenciais via .env)
- API Externa: PNCP

### Autoload
- Arquivo: autoload.php
- Padrão: PSR-4 customizado
- Namespace base: Semae\Pca\
- chama primeiro o autoloader do Composer

## Regras de Ouro para Continuação

1. **Ordem clara**: Nunca implemente infraestrutura antes do domínio
2. **Teste primeiro**: TDD em cada etapa
3. **Testes pequenos**: Um conceito por teste
4. **Sem arrays internos**: Usar entidades de domínio e ou DTO
5. **Dependências injetadas**: Facilita testes

## Plano de Implementação do Zero (Resumo)

**Fase 1 - Domínio:**
1. Plano.php (teste: PlanoTest.php)
2. Demanda.php (teste: DemandaTest.php)
3. PcaRepository.php (interface)

**Fase 2 - Aplicação:**
4. PublicarPcaRequest.php
5. PublicarPcaResponse.php
6. PublicarPcaValidator.php
7. PublicarPcaPresenter.php
8. PublicarPcaUseCase.php

**Fase 3 - Infraestrutura:**
9. OracleConnection.php
10. OraclePcaRepository.php
11. PncpClient.php
12. PncpPayload.php

**Fase 4 - Apresentação:**
13. PcaController.php (em Presentation/Http/Controllers/)

**Fase 5 - Integração:**
14. Testes de fluxo completo

### Responsabilidade de cada classe, de forma bem objetiva
Domínio
    • Plano: representar o plano e suas regras
    • Demanda: representar a demanda e seu formato de saída
    • PcaRepository: contrato para persistência
Aplicação
    • PublicarPcaRequest: entrada do caso de uso
    • PublicarPcaResponse: saída do caso de uso
    • PublicarPcaValidator: validar dados
    • PublicarPcaPresenter: preparar resposta para a apresentação
    • PublicarPcaUseCase: orquestrar o fluxo
Infraestrutura
    • OracleConnection: conexão com banco
    • OraclePcaRepository: implementação do repositório
    • PncpClient: comunicação com o PNCP
    • PncpPayload: composição do corpo da requisição
    • EnvLoader: leitura de ambiente
Apresentação
    • PcaController: adaptador HTTP

## Contato com Usuário

Conversas devem estabelecer:
- Objetivo: Pretensão de aprender Domain-Driven Design (DDD) junto com Clean Architecture enquanto implementa.
- Abordagem: incremental, com testes a cada passo
- Foco: entender o "porquê" de cada camada, não só o "como"
