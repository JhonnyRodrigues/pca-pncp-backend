Síntese proposta para a DI-004:

    1. Configuração e autenticação ocorrem antes das unidades; cada unidade lógica é serializada por uma chave exclusiva.
    2. Por unidade, a transação Oracle começa antes da releitura local e permanece aberta durante consulta, escrita, confirmação no PNCP e persistência local.
    3. Essa transação garante atomicidade apenas no Oracle; não é uma transação distribuída com o PNCP.
    4. Publicação inicial com até 1000 itens usa uma única escrita POST /pca.
    5. Estados acima de 1000 itens são limitação conhecida da primeira versão e bloqueiam antes de qualquer escrita externa; o volume será monitorado.
    6. Sincronizações posteriores podem exigir múltiplas requisições em lote.
    7. Falha específica e conhecida em um lote não impede operações independentes; falha sistêmica ou impossibilidade de observar o resultado interrompe a unidade.
    8. Não há compensação automática. A consulta final ao PNCP, e não apenas os códigos HTTP intermediários, determina convergência.
    9. Se o PNCP convergir com o estado desejado, os metadados locais são persistidos e ocorre commit, mesmo que tenha existido erro intermediário.
    10. Se restarem apenas mudanças homologadas pendentes, ocorre rollback e o resultado é falha reconciliável.
    11. Diferença inexplicada gera rollback e conflito operacional bloqueante.
    12. Escrita de resultado incerto nunca é repetida às cegas: o sistema consulta, recalcula e permite uma única reconciliação controlada na mesma execução.
    13. Se a confirmação externa ocorrer e a persistência ou o commit Oracle falhar, o risco aceito se materializa: rollback local, diagnóstico e reconciliação manual.
    14. No modo NÃO, falha específica de uma UO não impede as demais; falha sistêmica encerra toda a execução. No modo SIM, o consolidado é uma única unidade e qualquer falha pertence ao conjunto.
    15. A estratégia mantém locks Oracle durante chamadas HTTP; esse custo foi aceito considerando baixo volume e baixa concorrência, devendo ser revisto se essas premissas mudarem.