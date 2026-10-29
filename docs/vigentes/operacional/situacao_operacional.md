# Situação operacional do PCA/PNCP

**Classificação documental:** Operacional

**Autoridade/Escopo:** condição do produto instalado, restrições correntes e
ações seguras para manutenção e suporte

**Data de referência das evidências:** 29/10/2026

## 1. Finalidade

Este documento permite que mantenedores e suporte entendam rapidamente a
situação da integração PCA/PNCP antes de qualquer intervenção. Ele informa o
que está instalado, o encerramento da restrição externa conhecida e quais
ações são seguras.

Ele não registra a evolução completa das sprints, não substitui regras de
negócio ou decisões de integração e não autoriza, isoladamente, uma nova
execução. Planos, gates e memória detalhada da implementação pertencem aos
artefatos de agentes e ao histórico do projeto.

## 2. Produto instalado

- O backend está construído, empacotado como release autocontida e instalado
  em produção.
- A aplicação Blank `PCA_PUBLICAR_B` carrega o autoloader do pacote instalado
  em `/var/www/html/PCA_PUBLICAR_B/pca-pncp-backend/`.
- Registro, perfil de produção e CA bundle permanecem externos à release e ao
  webroot, em `/var/www/configuracoes/pca-pncp/`.
- A estratégia anual vigente é `INDIVIDUAL`.
- Os procedimentos de auditoria 8 e 9 e os vínculos das 366 demandas normais
  legadas de 2026 foram provisionados por migrations verificadas em produção.
- A execução de 2027 convergiu para `NENHUMA_ACAO` nas três unidades, sem nova
  escrita no PNCP.
- A retificação de 2026 foi aceita em produção nas três unidades em 26/10/2026,
  depois da atualização de autorização comunicada pelo PNCP.

Antes de uma atualização, confira a integridade do pacote e siga o
[guia de implantação e rollback](guia_de_implantacao_e_rollback.md).
Configuração e permissões devem seguir o
[guia de configuração de produção](guia_de_configuracao_de_producao.md).

## 3. Incidente de autorização de 2026 encerrado

O chamado 60352388 confirmou que a plataforma SGI, identificada no PNCP pelo ID
397, estava habilitada para o CNPJ do SEMAE, mas que a manutenção do PCA ainda
era limitada à plataforma que o publicara. O chamado 62224923 informou em
27/10/2026 que a atualização foi implantada em produção: plataformas
autorizadas para o mesmo órgão passaram a poder manter publicações de outras
plataformas autorizadas, exceto as origens federais explicitamente protegidas
pelo PNCP.

A comprovação funcional ocorreu em produção em 26/10/2026, na correlação
`d00fc51e-fdce-4d3d-a7c7-e05b1b61a5af`. A Blank retornou `CONCLUIDA`, visitou
as três unidades e concluiu três decisões `RETIFICAR`:

| UASG / sequencial | Inclusões | Exclusões | Itens observados após a escrita |
| --- | ---: | ---: | ---: |
| 205 / 4 | 73 | 0 | 281 |
| 207 / 2 | 3 | 0 | 8 |
| 210 / 3 | 38 | 0 | 191 |

Os dois itens adicionais da UASG 205, `919` e `937`, já haviam sido
identificados depois do precheck de setembro, que previa 71 inclusões. Os 62
eventos fornecidos são todos `INFO`. Eles comprovam três `POST` de inclusão com
HTTP 201, nenhuma chamada `DELETE`, nenhuma exclusão planejada ou confirmada,
igualdade entre as listas planejadas e confirmadas e persistência da
convergência nas três unidades. Não existe restrição externa conhecida que
justifique nova intervenção no PCA apenas para testar autorização.

A resposta integral e os eventos foram preservados localmente em `build/`, com
SHA-256 `253197E4DF8A17FCD671FEC8CCBDF296722C0192BB40B8952E74A1FFED59CAA8`
e `6FC4D74BDA7E98798F308B147072E3F5EA2148668C69CC5944A3BDEC1B19FA7F`,
respectivamente.

O post-check Oracle fornecido pelo mantenedor contém as três auditorias de
procedimento 8 esperadas para 2026, uma por UASG, com `RETIFICAR`, inclusões
73/3/38 e zero exclusões. A amostra de `PAC_PLANOS` comprova a projeção
`INDIVIDUAL/PUBLICADO/205/4`, a confirmação da versão vigente 242/v73 e a
preservação da versão anterior 241/v72 sem nova confirmação. O material não é
uma exportação integral das 112 versões, nem contém as linhas de planos das
UASGs 207 e 210. O mantenedor aceitou explicitamente essa cobertura reduzida,
em conjunto com os eventos de convergência, como evidência final suficiente.
O arquivo local possui SHA-256
`80CE928A4E789FA5EF6BAC2ABD41524437E47E4F94812E3AB3AF0DC8C4E4F9EF`.

A Sprint 9.5, a Fase 9 e a implementação planejada do projeto estão
encerradas. Não existe execução corretiva pendente.

## 4. Conduta segura

- Não repita a retificação de 2026 apenas para reconfirmar o sucesso ou testar
  autorização.
- Em uma publicação futura, preserve resposta, correlação e horário e consulte
  os eventos técnicos pela mesma correlação antes de classificar uma falha.
- Não trate itens apenas planejados como efetivamente publicados; exija aceite
  externo e convergência confirmada.
- Não altere vínculos, projeções ou auditoria Oracle para simular convergência.
- Reabra o incidente se reaparecer a mensagem de autoria, ocorrer 401/403, uma
  mutação produzir resultado incerto ou as fotografias PNCP e Oracle
  divergirem.

Para interpretar a resposta segura da aplicação, consulte o
[guia operacional de diagnóstico](guia_operacional_de_diagnostico_da_publicacao_pca.md).
A consulta dos eventos
por correlação está em
[`database/queries/seguranca_log_por_correlacao.sql`](../../../database/queries/seguranca_log_por_correlacao.sql).

## 5. Quando atualizar este documento

Atualize esta situação quando ocorrer pelo menos um destes fatos:

- instalação, promoção, rollback ou substituição da release;
- mudança comprovada da restrição externa;
- nova execução real autorizada;
- alteração da conduta segura para manutenção ou suporte;
- encerramento do incidente de 2026.

Não registre aqui cronologia de sprints, contagens extensas de testes, detalhes
de microciclos ou evidências históricas. Essas informações não ajudam o suporte
a decidir a próxima ação operacional e tornam a situação corrente difícil de
localizar.
