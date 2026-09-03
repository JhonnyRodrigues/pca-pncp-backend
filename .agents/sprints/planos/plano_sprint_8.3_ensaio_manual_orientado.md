# Ensaio manual orientado — Sprint 8.3

## Situação confirmada

- Ambiente: exclusivamente `HOMOLOGACAO`.
- CNPJ: `50853555000154`.
- Massa configurada: ano `2026`, UASG `204`.
- IDs: publicação `999` e `888`; inclusão posterior `777`.
- `residuo_sequencial_autorizado`: `null`.
- Os PCAs 16, 17 e 18 pertencem às UASGs 210, 205 e 207. Portanto, o sequencial 18 não bloqueia a massa da UASG 204 e não será excluído como pré-requisito.
- A falha anterior do gate pode ter sido uma leitura paginada inconclusiva. O ensaio manual verificará exatamente `pagina=1, 2, 3...` com `tamanhoPagina=1`.

# ROTEIRO do Gate nominal no treinamento - Microciclo 4

## Ciclo interativo

1. Executar novamente o preflight após a alteração dos IDs.
2. Autenticar uma única vez pelo Swagger e reutilizar o mesmo Bearer.
3. Consultar todas as páginas de unidades e comprovar ausência da UASG 204.
4. Publicar o PCA com os itens 999 e 888.
5. Registrar o sequencial retornado no cabeçalho `Location`.
6. Consultar consolidado e itens, página por página, comprovando IDs e paginação.
7. Incluir o item 777 e repetir as consultas.
8. Excluir o item 888 e confirmar que restaram 777 e 999.
9. Excluir integralmente o PCA criado no ensaio.
10. Confirmar ausência pelo endpoint consolidado e pela listagem de unidades.
11. Revisar `sprint83.php`, manter o resíduo como `null` e executar o gate automatizado intacto.

Em cada etapa eu fornecerei somente o método, endpoint, parâmetros, corpo exato e resultado esperado. Você executará no Swagger e retornará status, cabeçalhos não sensíveis e campos relevantes.

## Payload e verificações

- O payload será derivado exatamente de `sprint83.php`.
- A publicação inicial conterá, em ordem numérica, os itens 888 e 999.
- O item 777 será enviado separadamente.
- Quantidade de cada item: `3.0000`.
- Valores:
  - item 999: total `10.0000`, unitário `3.3333`;
  - item 888: total `0.5000`, unitário `0.1667`;
  - item 777: total `7.2500`, unitário `2.4167`.
- Os lexemas serão conferidos no corpo bruto da requisição do Swagger/DevTools; a resposta de um GET pode normalizar zeros finais.
- Depois de cada escrita serão conferidos CNPJ, ano, UASG, sequencial e `numeroItem`.
- Se ocorrer falha depois da criação, não repetiremos o POST: localizaremos o efeito por leitura, conferiremos sua identidade, excluiremos o PCA atribuível ao ensaio e comprovaremos ausência.

## Primeira etapa — preflight

No Git Bash:

```bash
export PCA_SPRINT83_CONFIG_FILE='C:/Users/jsilva/repositorios-semae/config-pca/teste-contract-sprint83/sprint83.php'
export PCA_PNCP_PROFILE_REGISTRY_FILE='C:/Users/jsilva/repositorios-semae/config-pca/teste-contract-sprint83/registro.php'

composer contract:pncp83:preflight
```

Resultado esperado:

```json
{
  "sprint": "8.3",
  "preflight": "CONFIRMADA",
  "perfil_homologacao": true,
  "escritas_pncp_autorizadas": true,
  "oracle_acessado": false,
  "pncp_acessado": false,
  "segredos_exibidos": false
}
```

O preflight não acessa o PNCP e não modifica dados. O próximo passo será fornecido somente após a conferência desse resultado.

---

# ROTEIRO do Ciclo de escrita incerta — Microciclo 5

1. Executar novamente o preflight da Sprint 8.3.
2. Confirmar que o perfil apontava para o ambiente de treinamento.
3. Confirmar que as escritas estavam autorizadas e que não havia resíduo configurado.
4. Autenticar uma única vez no PNCP e reutilizar a mesma sessão Bearer.
5. Consultar todas as páginas de unidades e comprovar que não existia PCA para a UASG dedicada.
6. Configurar o transporte para descartar deliberadamente a próxima resposta de publicação.
7. Enviar uma única publicação real contendo os itens 999 e 888.
8. Receber a resposta HTTP do PNCP, mas descartá-la antes que o escritor pudesse utilizar o Location.
9. Classificar a publicação como RESULTADO_INCERTO, pois a requisição foi enviada, mas sua confirmação ficou indisponível.
10. Confirmar que o POST de publicação aconteceu exatamente uma vez e não foi repetido.
11. Consultar novamente as unidades e os itens, procurando o efeito da publicação somente por leitura.
12. Encontrar um único PCA compatível com a UASG, o ano e a massa do ensaio.
13. Recuperar o sequencial por meio dessa leitura, sem utilizar o Location descartado.
14. Confirmar que o PCA encontrado continha exatamente os identificadores 888 e 999.
15. Excluir integralmente somente o PCA atribuído à execução atual.
16. Consultar o consolidado conhecido e percorrer novamente a listagem de unidades.
17. Confirmar a ausência definitiva da UASG dedicada e, portanto, a inexistência de resíduo.
18. Validar que houve apenas uma autenticação, uma publicação e nenhuma repetição da escrita incerta.
19. Gerar o relatório sanitizado com 23 eventos e seu hash SHA-256.
20. Registrar a aprovação no plano da Sprint 8.3, no estado atual, no roadmap e nas evidências da DI-009.
21. Manter a DI-009 semanticamente inalterada, pois o gate não revelou incompatibilidade ou contrato novo relevante.
