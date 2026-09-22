<?php

declare(strict_types=1);

namespace Tests\Integration\Scriptcase;

use PHPUnit\Framework\TestCase;

final class PcaPublicarBlankTest extends TestCase
{
    private string $codigo;

    protected function setUp(): void
    {
        $caminho = dirname(__DIR__, 3)
            . '/integracao/scriptcase/PCA_PUBLICAR_B/onExecute.php';
        $codigo = file_get_contents($caminho);

        $this->assertIsString($codigo);
        $this->codigo = $codigo;
    }

    public function testDeveChamarSomenteAFachadaPublicaComDadosDoHospedeiro(): void
    {
        $this->assertStringContainsString(
            '/pca-pncp-backend/vendor/autoload.php',
            $this->codigo
        );
        $this->assertStringContainsString('new \Semae\Pca\PublicadorPcaScriptcase()', $this->codigo);
        $this->assertStringContainsString('->executarComEventos(', $this->codigo);
        $this->assertStringContainsString('$this->Db', $this->codigo);
        $this->assertStringContainsString('[usr_login]', $this->codigo);
        $this->assertStringContainsString("\$_SERVER['SERVER_NAME']", $this->codigo);
        $this->assertStringContainsString("\$_SERVER['HTTP_X_REQUEST_ID']", $this->codigo);
        $this->assertMatchesRegularExpression(
            '/->executarComEventos\(\s*\$this->Db,\s*\$loginAutenticado,\s*'
                . '\$serverName,\s*\$entrada\[\'ano\'\],\s*\$requestId\s*\)/s',
            $this->codigo
        );
        $this->assertSame(2, substr_count($this->codigo, '$cnpjConsultaPublica'));

        $this->assertStringNotContainsString('PcaController', $this->codigo);
        $this->assertStringNotContainsString('getCredenciais', $this->codigo);
        $this->assertStringNotContainsString('isAmbienteProducao', $this->codigo);
    }

    public function testDeveValidarJsonEAnoInteiroSemValidarExercicioNaBlank(): void
    {
        $this->assertStringContainsString(
            "\$requisicaoPublicacao = \$metodoHttp === 'POST'",
            $this->codigo
        );
        $this->assertStringContainsString("\$_SERVER['CONTENT_TYPE']", $this->codigo);
        $this->assertStringContainsString("\$_SERVER['HTTP_CONTENT_TYPE']", $this->codigo);
        $this->assertStringContainsString("str_starts_with(\$contentType, 'application/json')", $this->codigo);
        $this->assertStringContainsString('if ($requisicaoPublicacao)', $this->codigo);
        $this->assertStringContainsString("file_get_contents('php://input')", $this->codigo);
        $this->assertStringContainsString('JSON_THROW_ON_ERROR', $this->codigo);
        $this->assertStringContainsString("array_key_exists('ano', \$entrada)", $this->codigo);
        $this->assertStringContainsString("is_int(\$entrada['ano'])", $this->codigo);
        $this->assertStringNotContainsString('ANO_EXECUCAO_INVALIDO', $this->codigo);
        $this->assertStringNotContainsString('getFullYear()', $this->codigo);
    }

    public function testPostDeNavegacaoDoMenuNaoDeveSerTratadoComoPublicacao(): void
    {
        $this->assertMatchesRegularExpression(
            '/\$metodoHttp === \'POST\'\s*&&\s*'
                . 'str_starts_with\(\$contentType, \'application\/json\'\)/',
            $this->codigo
        );
        $this->assertStringContainsString('pca_publicar_texto_utf8(', $this->codigo);
        $this->assertStringContainsString('html_entity_decode(', $this->codigo);
    }

    public function testFalhaExternaDevePreservarCorrelacaoValida(): void
    {
        $this->assertStringContainsString(
            'function pca_publicar_correlacao_segura(?string $correlacao): ?string',
            $this->codigo
        );
        $this->assertStringContainsString(
            'pca_publicar_correlacao_segura($requestId ?? null)',
            $this->codigo
        );
    }

    public function testDeveSerializarExplicitamenteViewModelsSemEnviarEventos(): void
    {
        foreach ([
            'situacao',
            'resultadosUnitarios',
            'codigoFalha',
            'mensagemFalha',
            'quantidadePlanejadas',
            'quantidadeVisitadas',
            'quantidadeNaoProcessadas',
            'correlacao',
            'idUnidadeOrcamentaria',
            'modo',
            'anoExecucao',
            'codigoUasg',
            'descricaoUasg',
            'decisao',
            'sequencialPca',
            'quantidadeInclusoes',
            'quantidadeExclusoes',
        ] as $campo) {
            $this->assertStringContainsString("'{$campo}' =>", $this->codigo);
        }

        $this->assertMatchesRegularExpression(
            '/\$respostaHttp\s*=\s*\[\s*\'resultado\'\s*=>.*?'
                . '\'avisoRegistroTecnico\'\s*=>.*?\];/s',
            $this->codigo
        );
        $this->assertStringNotContainsString("'eventos' =>", $this->codigo);
    }

    public function testDeveRegistrarEventosIntegraisEInterromperAposFalha(): void
    {
        $this->assertStringContainsString(
            'new \Semae\Pca\RegistradorEventosTecnicosScriptcase()',
            $this->codigo
        );
        $this->assertStringContainsString('->registrar(', $this->codigo);
        $this->assertStringContainsString("'PCA_PUBLICAR_B'", $this->codigo);
        $this->assertStringNotContainsString('sc_log_add(', $this->codigo);
        $this->assertStringNotContainsString('pca_publicar_serializar_evento(', $this->codigo);
        $this->assertStringNotContainsString('convertToISO88591', $this->codigo);
        $this->assertStringNotContainsString('substr(', $this->codigo);
        $this->assertStringNotContainsString('3900', $this->codigo);
    }

    public function testInterfaceDeveUsarAnoDoServidorEImpedirSubmissoesSimultaneas(): void
    {
        $this->assertStringContainsString('const anoAtual = ', $this->codigo);
        $this->assertStringContainsString('const proximoAno = ', $this->codigo);
        $this->assertMatchesRegularExpression(
            "/isConfirmed\).*?executarPublicacao\(proximoAno\)/s",
            $this->codigo
        );
        $this->assertMatchesRegularExpression(
            "/isDenied\).*?executarPublicacao\(anoAtual\)/s",
            $this->codigo
        );
        $this->assertStringContainsString('if (publicacaoEmAndamento)', $this->codigo);
        $this->assertStringContainsString('publicacaoEmAndamento = true;', $this->codigo);
        $this->assertStringContainsString('.finally(() =>', $this->codigo);
        $this->assertStringContainsString('publicacaoEmAndamento = false;', $this->codigo);
        $this->assertStringNotContainsString('setTimeout(', $this->codigo);
    }

    public function testAjudaDeveOrientarOperadorSemExecutarPublicacao(): void
    {
        $this->assertStringContainsString(
            '<button id="btnAjuda" type="button">Ajuda</button>',
            $this->codigo
        );
        $this->assertStringContainsString("const botaoAjuda = document.getElementById('btnAjuda');", $this->codigo);
        $this->assertStringContainsString('botaoAjuda.disabled = processando;', $this->codigo);
        $this->assertStringContainsString('Como funciona a publicação do PCA', $this->codigo);
        $this->assertStringContainsString("confirmButtonText: 'Entendi'", $this->codigo);
        $this->assertStringContainsString('allowEscapeKey: true', $this->codigo);
        $this->assertStringContainsString('botaoAjuda.focus()', $this->codigo);
        $this->assertStringContainsString('.ajuda-operador', $this->codigo);
        $this->assertStringContainsString('@media (max-width: 600px)', $this->codigo);
        $this->assertStringContainsString(
            'A versão ajuda a identificar o plano vigente no SGI, mas não define sozinha a ação.',
            $this->codigo
        );
        $this->assertStringContainsString(
            'O sistema escolhe a ação comparando as demandas atuais do SGI com a situação encontrada no PNCP.',
            $this->codigo
        );

        foreach ([
            'Finalidade',
            'Exercício',
            'Demandas consideradas',
            'Ação automática',
            'Modos de configuração',
            'Exclusão e nova publicação',
            'Resultados',
            'Procedimento',
            'Aviso de logs',
        ] as $secao) {
            $this->assertStringContainsString("'{$secao}'", $this->codigo);
        }

        $inicio = strpos($this->codigo, 'function abrirAjuda()');
        $fim = strpos($this->codigo, 'function executarPublicacao(ano)');
        $this->assertIsInt($inicio);
        $this->assertIsInt($fim);
        $this->assertGreaterThan($inicio, $fim);
        $trechoAjuda = substr($this->codigo, $inicio, $fim - $inicio);

        $this->assertStringNotContainsString('fetch(', $trechoAjuda);
        $this->assertStringNotContainsString('executarPublicacao(', $trechoAjuda);
        $this->assertStringNotContainsString('registrar(', $trechoAjuda);
        $this->assertStringNotContainsString('SQL', $trechoAjuda);
        $this->assertStringNotContainsString('credencial', strtolower($trechoAjuda));
    }

    public function testDeveRenderizarComTextoEscapadoESemExporRespostaBruta(): void
    {
        $this->assertStringContainsString('.textContent =', $this->codigo);
        $this->assertStringContainsString('quantidadePlanejadas', $this->codigo);
        $this->assertStringContainsString('quantidadeVisitadas', $this->codigo);
        $this->assertStringContainsString('quantidadeNaoProcessadas', $this->codigo);
        $this->assertStringContainsString('resultadosUnitarios', $this->codigo);
        $this->assertStringContainsString('correlacao', $this->codigo);
        $this->assertStringContainsString('Consulta pública no PNCP', $this->codigo);
        $this->assertStringContainsString('Não foi possível obter uma resposta segura do servidor.', $this->codigo);
        $this->assertStringContainsString('return respostaHttp.json();', $this->codigo);
        $this->assertStringNotContainsString('if (!respostaHttp.ok)', $this->codigo);
        $this->assertStringNotContainsString('resp.text()', $this->codigo);
        $this->assertStringNotContainsString('err.message', $this->codigo);
        $this->assertStringNotContainsString('Resposta bruta', $this->codigo);
        $this->assertStringNotContainsString('retry', strtolower($this->codigo));
    }
}
