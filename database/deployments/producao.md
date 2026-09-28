# Implantações do banco de produção

| Data | Versão | SHA-256 | Executor | Precheck | Situação | DML | Post-check |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 2026-09-24 | `V202609241700` | `3A796126F37619B76585147B9DFD700B4F26A6F792C2F7474F7E85E39EF33593` | `jsilva` | 2 linhas `AUSENTE`; sem divergências | `APLICADA` | Sim: 2 inserções | 2 linhas `CONFORME` |
| 2026-09-24 | `V202609241701` | `728A8DA0FADEC7697DE104643E0340AA97BA09BE13055A70742BEF539B0848FB` | `jsilva` | 1 linha `MIGRACAO_NECESSARIA`; sem divergências | `APLICADA` | Sim: 1 atualização | 1 linha `CONFORME` |
| 2026-09-25 | `V202609251823` | `47309C11DEA3E017B7AC129ACBC13CFFFBC9E53CAE2A3E245428B5BE153F2F9F` | `jsilva` | Guardas internas aprovadas durante a execução | `APLICADA` | Sim: vínculo de 366 IDs legados | Dados conferidos no banco e declarados conformes pelo executor |

Os postchecks de 24/09 foram confirmados pelo mantenedor. Nenhuma chamada à
Blank ou ao PNCP foi feita durante aquelas duas implantações.

O executor confirmou que a versão `V202609251823` foi aplicada em produção a
partir do arquivo exatamente igual ao repositório. Como a migration só confirma
a transação quando atualiza todos os 366 IDs ou quando todos já estavam no
plano-alvo, e esta foi a primeira aplicação após a constatação dos vínculos
nulos, o DML corresponde aos 366 vínculos. O arquivo aplicado não deve ser
editado; eventual correção exige nova migration.
