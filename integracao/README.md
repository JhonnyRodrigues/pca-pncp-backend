# Integração com o sistema hospedeiro

Esta pasta contém adaptações que pertencem ao sistema que hospeda o backend.
Atualmente, o hospedeiro é uma aplicação Blank do Scriptcase chamada
`PCA_PUBLICAR_B`.

## Fronteira com o backend

O arquivo versionado
[`scriptcase/PCA_PUBLICAR_B/onExecute.php`](scriptcase/PCA_PUBLICAR_B/onExecute.php)
é a fonte do evento `onExecute` da Blank. Ele deve apenas:

- distinguir navegação da interface e requisição JSON de publicação;
- carregar o `vendor/autoload.php` do pacote autocontido;
- chamar as fachadas públicas do backend;
- registrar os eventos técnicos exportados;
- transformar a saída segura em JSON ou interface para o operador.

Regras de negócio, acesso Oracle, transporte PNCP, credenciais e composição dos
objetos permanecem no backend ou na configuração externa. A Blank não deve
reimplementar essas responsabilidades.

## Publicação no Scriptcase

O arquivo deste repositório não é executado automaticamente pelo servidor. Uma
alteração precisa ser levada ao evento correspondente (Blank->evento onExecute), compilada e publicada
pelo processo do Scriptcase. O backend autocontido é implantado separadamente.

Em produção, a Blank carrega o backend instalado em `/var/www/html/PCA_PUBLICAR_B/pca-pncp-backend/`

Segredos e perfis permanecem fora do webroot, em `/var/www/configuracoes/pca-pncp/`. Não coloque credenciais, tokens ou conteúdo do perfil neste diretório.

Antes de publicar ou substituir a Blank, siga o
[guia de implantação e rollback](../docs/vigentes/operacional/guia_de_implantacao_e_rollback.md)
e valide os contratos de integração correspondentes. Abrir a interface não equivale a executar um teste de smoke funcional.
