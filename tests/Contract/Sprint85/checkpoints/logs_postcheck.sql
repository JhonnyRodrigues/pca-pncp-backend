-- Sprint 8.5 / Microciclo 6 / POSTCHECK do gate de logs no validaSGI
-- Execute somente depois da chamada única da etapa LOGS.
-- Consulta somente leitura; não corrija nem repita registros automaticamente.

-- Colunas: ACTION, TAMANHO_DESCRIPTION_ASCII, IDENTIFICADOR_ENCONTRADO,
--          ACENTOS_CODIFICADOS, MAPAS_VAZIOS_PRESERVADOS, JSON_VALIDO,
--          DESCRIPTION
-- Cardinalidade: exatamente 1.
-- DESCRIPTION deve ser o CLOB integral. O JSON contém somente ASCII porque
-- os acentos usam escapes Unicode; por isso o tamanho em caracteres também
-- representa seu tamanho em bytes e deve ser maior que 4.000.
SELECT l.ACTION AS ACTION,
       DBMS_LOB.GETLENGTH(l.DESCRIPTION) AS TAMANHO_DESCRIPTION_ASCII,
       CASE
           WHEN DBMS_LOB.INSTR(
               l.DESCRIPTION,
               'SPRINT85-LOGS-HML-20260921-JSILVA-02'
           ) > 0 THEN 'SIM'
           ELSE 'NAO'
       END AS IDENTIFICADOR_ENCONTRADO,
       CASE
           WHEN DBMS_LOB.INSTR(l.DESCRIPTION, '\u00e7') > 0
            AND DBMS_LOB.INSTR(l.DESCRIPTION, '\u00e3') > 0 THEN 'SIM'
           ELSE 'NAO'
       END AS ACENTOS_CODIFICADOS,
       CASE
           WHEN DBMS_LOB.INSTR(l.DESCRIPTION, '"error":{}') > 0
            AND DBMS_LOB.INSTR(l.DESCRIPTION, '"context":{}') > 0 THEN 'SIM'
           ELSE 'NAO'
       END AS MAPAS_VAZIOS_PRESERVADOS,
       CASE
           WHEN l.DESCRIPTION IS JSON THEN 'SIM'
           ELSE 'NAO'
       END AS JSON_VALIDO,
       l.DESCRIPTION AS DESCRIPTION
  FROM SGI.SEGURANCA_LOG l
 WHERE l.ACTION = 'execute'
   AND DBMS_LOB.INSTR(
       l.DESCRIPTION,
       'SPRINT85-LOGS-HML-20260921-JSILVA-02'
   ) > 0;
