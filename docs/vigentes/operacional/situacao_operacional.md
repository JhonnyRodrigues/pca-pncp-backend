# Situação operacional do PCA/PNCP

**Classificação documental:** Operacional

**Autoridade/Escopo:** condição do produto instalado, restrições correntes e
ações seguras para manutenção e suporte

**Data de referência das evidências:** 28/09/2026

## 1. Finalidade

Este documento permite que mantenedores e suporte entendam rapidamente a
situação da integração PCA/PNCP antes de qualquer intervenção. Ele informa o
que está instalado, qual restrição permanece aberta e quais ações são seguras.

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

Antes de uma atualização, confira a integridade do pacote e siga o
[guia de implantação e rollback](guia_de_implantacao_e_rollback.md).
Configuração e permissões devem seguir o
[guia de configuração de produção](guia_de_configuracao_de_producao.md).

## 3. Restrição corrente de 2026

O precheck posterior à migration reconstruiu 478 pares distintos de
`UASG + numeroItem` e confirmou somente inclusões pendentes: 71 itens para a
UASG 205, 3 para a UASG 207 e 38 para a UASG 210. Nenhuma exclusão era esperada.

As execuções posteriores foram interrompidas no primeiro `POST` de inclusão com
HTTP 403 e código `PNCP_INCLUSAO_ITENS_NAO_AUTORIZADA`. O PNCP informou que
somente o usuário responsável pela publicação poderia editar o PCA. Os eventos
confirmaram a identidade configurada, nenhum `DELETE` foi enviado e nenhuma
inclusão foi comprovada.

A autoria reconhecida pelo PNCP e a correção funcional permanecem pendentes de
esclarecimento externo. Essa restrição não autoriza nova tentativa, troca de
credencial, alteração manual no Oracle ou reconstrução do PCA por inferência.

## 4. Conduta segura

Enquanto a restrição permanecer aberta:

- não acione novamente a publicação de 2026;
- não repita `POST` ou `DELETE` para testar hipótese de permissão;
- não trate itens planejados como itens efetivamente publicados;
- não altere vínculos, projeções ou auditoria Oracle para simular convergência;
- preserve a resposta, a correlação e o horário de qualquer ocorrência;
- consulte os eventos técnicos pela mesma correlação antes de concluir a causa;
- confirme Oracle e PNCP antes de considerar qualquer repetição mutante;
- aguarde evidência externa e autorização específica para o próximo passo.

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
