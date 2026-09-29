<?php
/*
* Essa aplicação Blank é uma aplicação hospedeira e ponto de entrada da execução. Ela carrega o autoloader, chama a fachada pública do backend e apresenta o ViewModel, no mesmo processo PHP.
*
* Regras de publicação e configuração permanecem no backend e nos perfis externos provisionados pelo servidor.
*/

function pca_publicar_serializar_resultado_unidade(object $resultado): array
{
    return [
        'idUnidadeOrcamentaria' => $resultado->getIdUnidadeOrcamentaria(),
        'modo' => $resultado->getModo(),
        'anoExecucao' => $resultado->getAnoExecucao(),
        'codigoUasg' => $resultado->getCodigoUasg(),
        'descricaoUasg' => $resultado->getDescricaoUasg(),
        'situacao' => $resultado->getSituacao(),
        'decisao' => $resultado->getDecisao(),
        'sequencialPca' => $resultado->getSequencialPca(),
        'quantidadeInclusoes' => $resultado->getQuantidadeInclusoes(),
        'quantidadeExclusoes' => $resultado->getQuantidadeExclusoes(),
        'numerosItensIncluidos' => $resultado->getNumerosItensIncluidos(),
        'numerosItensExcluidos' => $resultado->getNumerosItensExcluidos(),
        'codigoFalha' => $resultado->getCodigoFalha(),
        'mensagemFalha' => $resultado->getMensagemFalha(),
    ];
}

function pca_publicar_serializar_resultado(object $resultado): array
{
    $resultadosUnitarios = [];

    foreach ($resultado->getResultadosUnitarios() as $resultadoUnitario) {
        $resultadosUnitarios[] = pca_publicar_serializar_resultado_unidade($resultadoUnitario);
    }

    return [
        'situacao' => $resultado->getSituacao(),
        'resultadosUnitarios' => $resultadosUnitarios,
        'codigoFalha' => $resultado->getCodigoFalha(),
        'mensagemFalha' => $resultado->getMensagemFalha(),
        'quantidadePlanejadas' => $resultado->getQuantidadePlanejadas(),
        'quantidadeVisitadas' => $resultado->getQuantidadeVisitadas(),
        'quantidadeNaoProcessadas' => $resultado->getQuantidadeNaoProcessadas(),
        'correlacao' => $resultado->getCorrelacao(),
    ];
}

function pca_publicar_resultado_seguro(
    string $situacao,
    string $codigo,
    string $mensagem,
    ?string $correlacao = null
): array {
    return [
        'situacao' => $situacao,
        'resultadosUnitarios' => [],
        'codigoFalha' => $codigo,
        'mensagemFalha' => $mensagem,
        'quantidadePlanejadas' => 0,
        'quantidadeVisitadas' => 0,
        'quantidadeNaoProcessadas' => 0,
        'correlacao' => $correlacao,
    ];
}

function pca_publicar_correlacao_segura(?string $correlacao): ?string
{
    if (
        $correlacao === null
        || preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/Di',
            $correlacao
        ) !== 1
    ) {
        return null;
    }

    return $correlacao;
}

function pca_publicar_texto_utf8(string $textoComEntidades): string
{
    return html_entity_decode($textoComEntidades, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function pca_publicar_responder_json(int $statusHttp, array $conteudo): void
{
    http_response_code($statusHttp);
    header('Content-Type: application/json; charset=UTF-8');

    $json = json_encode(
        $conteudo,
        JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
    );

    if (!is_string($json)) {
        $json = '{"resultado":{"situacao":"INTERROMPIDA",'
            . '"resultadosUnitarios":[],"codigoFalha":"FALHA_INESPERADA",'
            . '"mensagemFalha":"N\u00e3o foi poss\u00edvel concluir a solicita\u00e7\u00e3o.",'
            . '"quantidadePlanejadas":0,"quantidadeVisitadas":0,'
            . '"quantidadeNaoProcessadas":0,"correlacao":null},'
            . '"avisoRegistroTecnico":null}';
    }

    echo $json;
}

$anoAtual = (int) date('Y');
$proximoAno = $anoAtual + 1;
$cnpjConsultaPublica = '50853555000154';
$urlConsultaPublicaAnoAtual = 'https://pncp.gov.br/app/pca/' . $cnpjConsultaPublica . '/' . $anoAtual;
$urlConsultaPublicaProximoAno = 'https://pncp.gov.br/app/pca/' . $cnpjConsultaPublica . '/' . $proximoAno;

$metodoHttp = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$contentTypeRecebido = $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');
$contentType = strtolower(trim((string) $contentTypeRecebido));
$requisicaoPublicacao = $metodoHttp === 'POST' && str_starts_with($contentType, 'application/json');

if ($requisicaoPublicacao) {
    try {
        $entrada = json_decode(
            (string) file_get_contents('php://input'),
            true,
            32,
            JSON_THROW_ON_ERROR
        );

        if (
            !is_array($entrada)
            || !array_key_exists('ano', $entrada)
            || !is_int($entrada['ano'])
        ) {
            throw new \InvalidArgumentException('Entrada HTTP inválida.');
        }

        $autoload = $this->Ini->path_aplicacao
            . '/pca-pncp-backend/vendor/autoload.php';

        if (!is_file($autoload)) {
            throw new \RuntimeException('Bootstrap indisponível.');
        }

        require_once $autoload;

        $loginAutenticado = trim((string) [usr_login]);
        $serverName = isset($_SERVER['SERVER_NAME']) && is_string($_SERVER['SERVER_NAME'])
			? $_SERVER['SERVER_NAME']
			: '';
        $requestId = isset($_SERVER['HTTP_X_REQUEST_ID']) && is_string($_SERVER['HTTP_X_REQUEST_ID'])
			? $_SERVER['HTTP_X_REQUEST_ID']
			: null;

        $saida = (new \Semae\Pca\PublicadorPcaScriptcase())->executarComEventos(
            $this->Db,
            $loginAutenticado,
            $serverName,
            $entrada['ano'],
            $requestId
        );

        $resultadoSerializado = pca_publicar_serializar_resultado(
            $saida->getResultado()
        );
        $avisoRegistroTecnico = $saida->getAvisoRegistroTecnico();

        if ($avisoRegistroTecnico === null) {
            try {
                $avisoRegistroTecnico = (
                    new \Semae\Pca\RegistradorEventosTecnicosScriptcase()
                )->registrar(
                    $this->Db,
                    $loginAutenticado,
                    $serverName,
                    'PCA_PUBLICAR_B',
                    $saida->getEventos()
                );
            } catch (\Throwable) {
                $avisoRegistroTecnico = pca_publicar_texto_utf8(
                    'O resultado foi preservado, mas os eventos t&eacute;cnicos '
                        . 'n&atilde;o puderam ser registrados.'
                );
            }
        }

        $respostaHttp = [
            'resultado' => $resultadoSerializado,
            'avisoRegistroTecnico' => $avisoRegistroTecnico,
        ];

        pca_publicar_responder_json(200, $respostaHttp);
    } catch (\JsonException | \InvalidArgumentException) {
        $respostaHttp = [
            'resultado' => pca_publicar_resultado_seguro(
                'SOLICITACAO_INVALIDA',
                'ENTRADA_HTTP_INVALIDA',
                pca_publicar_texto_utf8(
                    'A solicita&ccedil;&atilde;o enviada &eacute; inv&aacute;lida.'
                )
            ),
            'avisoRegistroTecnico' => null,
        ];

        pca_publicar_responder_json(400, $respostaHttp);
    } catch (\Throwable) {
        $respostaHttp = [
            'resultado' => pca_publicar_resultado_seguro(
                'INTERROMPIDA',
                'FALHA_INESPERADA',
                pca_publicar_texto_utf8(
                    'N&atilde;o foi poss&iacute;vel concluir a solicita&ccedil;&atilde;o.'
                ),
                pca_publicar_correlacao_segura($requestId ?? null)
            ),
            'avisoRegistroTecnico' => null,
        ];

        pca_publicar_responder_json(500, $respostaHttp);
    }

    return;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Publicação do PCA no PNCP</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            margin: 0;
            padding: 5rem 1rem;
            background: #f7f8fa;
            color: #243447;
            font-family: Arial, sans-serif;
            text-align: center;
        }

        main {
            width: min(46rem, 100%);
            margin: 0 auto;
        }

        .acoes-principais {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: center;
        }

        button {
            padding: 0.7rem 1.5rem;
            border: 0;
            border-radius: 0.5rem;
            background: #2176ae;
            color: #fff;
            cursor: pointer;
            font-size: 1rem;
        }

        button:hover:not(:disabled) {
            background: #185a85;
        }

        button:disabled {
            background: #9aa5b1;
            cursor: not-allowed;
        }

        #btnAjuda {
            background: #52606d;
        }

        #btnAjuda:hover:not(:disabled) {
            background: #3d4852;
        }

        .resultado-pca {
            max-height: min(65vh, 40rem);
            overflow-y: auto;
            text-align: left;
        }

        .resultado-pca section {
            margin: 0.75rem 0;
            padding: 0.75rem;
            border: 1px solid #d8dee6;
            border-radius: 0.4rem;
        }

        .resultado-pca p {
            margin: 0.35rem 0;
            overflow-wrap: anywhere;
        }

        .aviso-tecnico {
            color: #7a4b00;
            background: #fff4d6;
        }

        .tempo-publicacao {
            margin-bottom: 0;
            font-weight: bold;
            font-variant-numeric: tabular-nums;
        }

        .rodape-resultado {
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid #d8dee6;
            color: #52606d;
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .rodape-resultado p {
            margin: 0;
        }

        .ajuda-operador {
            max-height: min(65vh, 40rem);
            overflow-y: auto;
            padding-right: 0.5rem;
            text-align: left;
        }

        .ajuda-operador section {
            margin-bottom: 1rem;
        }

        .ajuda-operador h3 {
            margin: 0 0 0.3rem;
            color: #243447;
            font-size: 1rem;
        }

        .ajuda-operador p {
            margin: 0;
            line-height: 1.45;
        }

        @media (max-width: 600px) {
            body {
                padding-top: 2rem;
            }

            .ajuda-operador {
                max-height: 60vh;
            }
        }
    </style>
</head>
<body>
<main>
    <h1>Publicação do PCA no PNCP</h1>
    <p>
        Selecione o exercício correspondente à execução das compras e contratações que deseja sincronizar.
    </p>
    <div class="acoes-principais">
        <button id="btnPublicar" type="button">Publicar</button>
        <button id="btnAjuda" type="button">Ajuda</button>
    </div>
    <p>
        <small><a href="<?= htmlspecialchars($urlConsultaPublicaAnoAtual, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" >Consulta pública no PNCP - exercício <?= $anoAtual ?></a></small>
    </p>
    <p>
        <small><a href="<?= htmlspecialchars($urlConsultaPublicaProximoAno, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" >Consulta pública no PNCP - exercício <?= $proximoAno ?></a></small>
    </p>
</main>

<script>
const anoAtual = <?= json_encode($anoAtual) ?>;
const proximoAno = <?= json_encode($proximoAno) ?>;
const botaoPublicar = document.getElementById('btnPublicar');
const botaoAjuda = document.getElementById('btnAjuda');
let publicacaoEmAndamento = false;

function definirEstadoBotao(processando, texto) {
    botaoPublicar.disabled = processando;
    botaoAjuda.disabled = processando;
    botaoPublicar.textContent = texto;
}

function textoSeguro(valor) {
    return valor === null || valor === undefined || valor === ''
        ? '-'
        : String(valor);
}

function formatarDuracao(duracaoMs) {
    const totalSegundos = Math.floor(Math.max(0, duracaoMs) / 1000);
    const minutos = String(Math.floor(totalSegundos / 60)).padStart(2, '0');
    const segundos = String(totalSegundos % 60).padStart(2, '0');

    return minutos + ':' + segundos;
}

function formatarTempoDecorrido(inicio) {
    return formatarDuracao(Date.now() - inicio);
}

function criarConteudoProcessamento(ano) {
    const container = document.createElement('div');
    const descricao = document.createElement('p');
    const tempo = document.createElement('p');

    descricao.textContent = 'Sincronizando o PCA do exercício ' + ano + ' com o PNCP.';
    tempo.id = 'tempoPublicacao';
    tempo.className = 'tempo-publicacao';
    tempo.textContent = 'Tempo decorrido: 00:00';
    container.appendChild(descricao);
    container.appendChild(tempo);

    return container;
}

function atualizarTempoPublicacao(inicio) {
    const tempo = document.getElementById('tempoPublicacao');

    if (tempo === null) {
        return;
    }

    tempo.textContent = 'Tempo decorrido: ' + formatarTempoDecorrido(inicio);
}

function adicionarLinha(container, rotulo, valor) {
    const linha = document.createElement('p');
    const destaque = document.createElement('strong');
    destaque.textContent = rotulo + ': ';
    linha.appendChild(destaque);
    linha.appendChild(document.createTextNode(textoSeguro(valor)));
    container.appendChild(linha);
}

function adicionarListaItens(container, rotulo, numerosItens) {
    const titulo = document.createElement('p');
    const destaque = document.createElement('strong');
    destaque.textContent = rotulo + ':';
    titulo.appendChild(destaque);
    container.appendChild(titulo);

    const lista = document.createElement('ul');
    if (numerosItens.length === 0) {
        const item = document.createElement('li');
        item.textContent = 'Nenhum';
        lista.appendChild(item);
    }
    numerosItens.forEach((numeroItem) => {
        const item = document.createElement('li');
        item.appendChild(document.createTextNode(String(numeroItem)));
        lista.appendChild(item);
    });
    container.appendChild(lista);
}

function criarConteudoResultado(resposta, duracaoProcessamento) {
    const resultado = resposta.resultado;
    const container = document.createElement('div');
    container.className = 'resultado-pca';

    const resumo = document.createElement('section');
    adicionarLinha(resumo, 'Situação', resultado.situacao);
    adicionarLinha(resumo, 'Unidades planejadas', resultado.quantidadePlanejadas);
    adicionarLinha(resumo, 'Unidades visitadas', resultado.quantidadeVisitadas);
    adicionarLinha(resumo, 'Unidades não processadas', resultado.quantidadeNaoProcessadas);
    adicionarLinha(resumo, 'Código', resultado.codigoFalha);
    adicionarLinha(resumo, 'Mensagem', resultado.mensagemFalha);
    adicionarLinha(resumo, 'Identificador da execução', resultado.correlacao);
    container.appendChild(resumo);

    resultado.resultadosUnitarios.forEach((unidade, indice) => {
        const secao = document.createElement('section');
        const titulo = document.createElement('h3');
        titulo.textContent = 'Resultado da unidade ' + (indice + 1);
        secao.appendChild(titulo);
        adicionarLinha(secao, 'Ação', unidade.decisao);
        adicionarLinha(secao, 'Situação', unidade.situacao);
        adicionarLinha(secao, 'Exercício', unidade.anoExecucao);
        adicionarLinha(secao, 'Descrição da UASG', unidade.descricaoUasg);
        adicionarLinha(secao, 'UASG', unidade.codigoUasg);
        adicionarLinha(secao, 'Unidade orçamentária', unidade.idUnidadeOrcamentaria);
        adicionarLinha(secao, 'Sequencial do PCA', unidade.sequencialPca);
        adicionarLinha(secao, 'Modo de publicação', unidade.modo);
        adicionarLinha(secao, 'Inclusões', unidade.quantidadeInclusoes);
        adicionarLinha(secao, 'Exclusões', unidade.quantidadeExclusoes);
        if (unidade.situacao === 'CONCLUIDO' && unidade.decisao === 'RETIFICAR') {
            adicionarListaItens(secao, 'Itens incluídos', unidade.numerosItensIncluidos);
            adicionarListaItens(secao, 'Itens excluídos', unidade.numerosItensExcluidos);
        }
        adicionarLinha(secao, 'Código', unidade.codigoFalha);
        adicionarLinha(secao, 'Mensagem', unidade.mensagemFalha);
        container.appendChild(secao);
    });

    if (resposta.avisoRegistroTecnico) {
        const aviso = document.createElement('section');
        aviso.className = 'aviso-tecnico';
        adicionarLinha(aviso, 'Aviso de registro técnico', resposta.avisoRegistroTecnico);
        container.appendChild(aviso);
    }

    const rodape = document.createElement('footer');
    rodape.className = 'rodape-resultado';
    adicionarLinha(
        rodape,
        'Tempo de processamento',
        formatarDuracao(duracaoProcessamento)
    );
    container.appendChild(rodape);

    return container;
}

function respostaValida(resposta) {
    return resposta !== null
        && typeof resposta === 'object'
        && resposta.resultado !== null
        && typeof resposta.resultado === 'object'
        && typeof resposta.resultado.situacao === 'string'
        && Array.isArray(resposta.resultado.resultadosUnitarios)
        && Number.isInteger(resposta.resultado.quantidadePlanejadas)
        && Number.isInteger(resposta.resultado.quantidadeVisitadas)
        && Number.isInteger(resposta.resultado.quantidadeNaoProcessadas);
}

function configuracaoModal(situacao) {
    switch (situacao) {
        case 'CONCLUIDA':
            return { icon: 'success', title: 'Publicação concluída' };
        case 'CONCLUIDA_COM_PENDENCIAS':
            return { icon: 'warning', title: 'Publicação concluída com pendências' };
        case 'SOLICITACAO_INVALIDA':
            return { icon: 'error', title: 'Solicitação inválida' };
        default:
            return { icon: 'error', title: 'Publicação interrompida' };
    }
}

function adicionarSecaoAjuda(container, titulo, texto) {
    const secao = document.createElement('section');
    const cabecalho = document.createElement('h3');
    const paragrafo = document.createElement('p');

    cabecalho.textContent = titulo;
    paragrafo.textContent = texto;
    secao.appendChild(cabecalho);
    secao.appendChild(paragrafo);
    container.appendChild(secao);
}

function criarConteudoAjuda() {
    const container = document.createElement('div');
    container.className = 'ajuda-operador';

    adicionarSecaoAjuda(
        container,
        'Finalidade',
        'Esta aplicação sincroniza o Plano de Contratações Anual do SGI com o PNCP.'
    );
    adicionarSecaoAjuda(
        container,
        'Exercício',
        'Escolha o ano atual ou o seguinte, correspondente à execução das compras e contratações.'
    );
    adicionarSecaoAjuda(
        container,
        'Demandas consideradas',
        'Demandas homologadas, compradas e publicadas participam. Demandas planejadas e canceladas não participam.'
    );
    adicionarSecaoAjuda(
        container,
        'Ação automática',
        'Conforme a comparação entre SGI e PNCP, o sistema pode publicar, retificar, excluir ou informar que não há alterações. A versão ajuda a identificar o plano vigente no SGI, mas não define sozinha a ação. O sistema escolhe a ação comparando as demandas atuais do SGI com a situação encontrada no PNCP.'
    );
    adicionarSecaoAjuda(
        container,
        'Modos de configuração',
        'No modo `consolidado`, as Unidades Orçamentárias compõem um único PCA. No `individual`, cada unidade recebe um resultado próprio. Uma unidade orçamentária vazia não exclui o plano consolidado enquanto outras unidades tiverem demandas.'
    );
    adicionarSecaoAjuda(
        container,
        'Exclusão e nova publicação',
        'Um PCA ativo pode ser excluído quando seu conjunto ficar vazio. Se surgirem novas demandas depois da exclusão, pode ser necessária outra publicação.'
    );
    adicionarSecaoAjuda(
        container,
        'Resultados',
        '`Concluída` indica processamento completo. `Concluída com pendências` preserva os resultados conhecidos e aponta o que exige atenção. `Solicitação inválida` indica que a entrada não foi aceita. `Interrompida` indica que não foi possível continuar com segurança. `Resultados já concluídos` não são automaticamente desfeitos por uma falha posterior.'
    );
    adicionarSecaoAjuda(
        container,
        'Procedimento',
        'Prepare as alterações pelo fluxo normal do SGI, aguarde o processamento e confira os resultados. Diante de resposta incerta, informe a CTI com o identificador da execução antes de repetir.'
    );
    adicionarSecaoAjuda(
        container,
        'Aviso de logs',
        'Uma falha no registro técnico não altera, por si só, o resultado apresentado.'
    );

    return container;
}

function abrirAjuda() {
    if (publicacaoEmAndamento) {
        return;
    }

    Swal.fire({
        title: 'Como funciona a publicação do PCA',
        html: criarConteudoAjuda(),
        width: 760,
        confirmButtonText: 'Entendi',
        allowEscapeKey: true,
        focusConfirm: true
    }).then(() => {
        botaoAjuda.focus();
    });
}

function executarPublicacao(ano) {
    definirEstadoBotao(true, 'Publicando...');
    const inicioPublicacao = Date.now();
    let intervaloTemporizador = null;
    const pararTemporizadorPublicacao = () => {
        if (intervaloTemporizador !== null) {
            window.clearInterval(intervaloTemporizador);
            intervaloTemporizador = null;
        }
    };

    Swal.fire({
        title: 'Publicando...',
        html: criarConteudoProcessamento(ano),
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
            atualizarTempoPublicacao(inicioPublicacao);
            intervaloTemporizador = window.setInterval(
                () => atualizarTempoPublicacao(inicioPublicacao),
                1000
            );
        },
        willClose: pararTemporizadorPublicacao
    });

    fetch(window.location.pathname, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ano: ano })
    })
    .then((respostaHttp) => {
        return respostaHttp.json();
    })
    .then((resposta) => {
        const duracaoProcessamento = Date.now() - inicioPublicacao;
        pararTemporizadorPublicacao();

        if (!respostaValida(resposta)) {
            throw new Error('RESPOSTA_INVALIDA');
        }

        const modal = configuracaoModal(resposta.resultado.situacao);
        return Swal.fire({
            icon: modal.icon,
            title: modal.title,
            html: criarConteudoResultado(resposta, duracaoProcessamento),
            width: 760,
            confirmButtonText: 'Fechar'
        });
    })
    .catch(() => {
        pararTemporizadorPublicacao();

        return Swal.fire({
            icon: 'error',
            title: 'Falha na comunicação',
            text: 'Não foi possível obter uma resposta segura do servidor.'
        });
    })
    .finally(() => {
        pararTemporizadorPublicacao();
        publicacaoEmAndamento = false;
        definirEstadoBotao(false, 'Publicar');
    });
}

botaoAjuda.addEventListener('click', abrirAjuda);

botaoPublicar.addEventListener('click', () => {
    if (publicacaoEmAndamento) {
        return;
    }

    publicacaoEmAndamento = true;
    definirEstadoBotao(true, 'Selecionando exercício...');

    Swal.fire({
        title: 'Selecione o ano de execução',
        text: 'Qual é o exercício da compra ou contratação que será sincronizada?',
        icon: 'question',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: 'Ano seguinte (' + proximoAno + ')',
        denyButtonText: 'Ano atual (' + anoAtual + ')',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#237a3b',
        denyButtonColor: '#9a6700'
    }).then((resultado) => {
        if (resultado.isConfirmed) {
            executarPublicacao(proximoAno);
        } else if (resultado.isDenied) {
            executarPublicacao(anoAtual);
        } else {
            publicacaoEmAndamento = false;
            definirEstadoBotao(false, 'Publicar');
        }
    }).catch(() => {
        publicacaoEmAndamento = false;
        definirEstadoBotao(false, 'Publicar');
    });
});
</script>
</body>
</html>
<?php
