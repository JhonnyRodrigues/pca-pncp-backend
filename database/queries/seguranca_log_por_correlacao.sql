-- Consulta operacional somente leitura dos eventos de uma execução da Blank.
-- No DBeaver, informe apenas o UUID retornado em "correlacao" quando o cliente
-- solicitar o bind :correlacao. Nunca substitua o bind por credencial ou JWT.
-- STATUS_HTTP nulo significa que não houve uma resposta HTTP comprovada.

SELECT L.ID,
       L.INSERTED_DATE,
       JSON_VALUE(L.DESCRIPTION, '$.level' NULL ON ERROR) AS NIVEL,
       JSON_VALUE(L.DESCRIPTION, '$.action' NULL ON ERROR) AS ACAO,
       JSON_VALUE(L.DESCRIPTION, '$.message' NULL ON ERROR) AS MENSAGEM,
       JSON_VALUE(L.DESCRIPTION, '$.error.code' NULL ON ERROR) AS CODIGO,
       COALESCE(
           JSON_VALUE(L.DESCRIPTION, '$.metadata.status_http' NULL ON ERROR),
           JSON_VALUE(L.DESCRIPTION, '$.metadata.statusHttp' NULL ON ERROR)
       ) AS STATUS_HTTP,
       JSON_VALUE(L.DESCRIPTION, '$.metadata.tentativa' NULL ON ERROR) AS TENTATIVA,
       JSON_VALUE(L.DESCRIPTION, '$.metadata.pagina' NULL ON ERROR) AS PAGINA,
       JSON_VALUE(
           L.DESCRIPTION,
           '$.metadata.executionTimeMs' NULL ON ERROR
       ) AS DURACAO_EXECUCAO_MS,
       JSON_VALUE(L.DESCRIPTION, '$.context.anoExecucao' NULL ON ERROR) AS ANO,
       JSON_VALUE(L.DESCRIPTION, '$.context.uasg' NULL ON ERROR) AS UASG,
       JSON_VALUE(L.DESCRIPTION, '$.context.decisao' NULL ON ERROR) AS DECISAO
  FROM SGI.SEGURANCA_LOG L
 WHERE DBMS_LOB.INSTR(L.DESCRIPTION, :correlacao) > 0
 ORDER BY L.ID;
