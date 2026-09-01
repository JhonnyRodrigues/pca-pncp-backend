<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;
use Semae\Pca\Presentation\ViewModels\PublicarPcaViewModel;
use Semae\Pca\PublicadorPcaScriptcase;

final class PublicadorPcaScriptcaseTest extends TestCase
{
    private const VARIAVEL_REGISTRO = 'PCA_PNCP_PROFILE_REGISTRY_FILE';
    private const SERVIDOR = 'pca.exato.test';
    private const REQUEST_ID = '550E8400-E29B-41D4-A716-446655440000';

    private string|false $registroAnterior;
    private string $diretorioTemporario;

    /** @var string[] */
    private array $arquivosTemporarios = [];

    protected function setUp(): void
    {
        $this->registroAnterior = getenv(self::VARIAVEL_REGISTRO);
        $base = tempnam(sys_get_temp_dir(), 'pca-s81-');

        if ($base === false || !unlink($base) || !mkdir($base)) {
            throw new RuntimeException('Não foi possível preparar o diretório temporário.');
        }

        $this->diretorioTemporario = $base;
    }

    protected function tearDown(): void
    {
        if ($this->registroAnterior === false) {
            putenv(self::VARIAVEL_REGISTRO);
        } else {
            putenv(self::VARIAVEL_REGISTRO . '=' . $this->registroAnterior);
        }

        foreach (array_reverse($this->arquivosTemporarios) as $arquivo) {
            if (is_file($arquivo)) {
                unlink($arquivo);
            }
        }

        if (is_dir($this->diretorioTemporario)) {
            rmdir($this->diretorioTemporario);
        }
    }

    public function testApiPublicaDeveReceberSomenteDadosNaturaisDaBlank(): void
    {
        $metodo = new ReflectionMethod(PublicadorPcaScriptcase::class, 'executar');

        $this->assertSame([
            'conexaoAdodb',
            'loginAutenticado',
            'serverName',
            'anoExecucao',
            'requestId',
        ], array_map(
            static fn ($parametro): string => $parametro->getName(),
            $metodo->getParameters()
        ));
        $this->assertSame(PublicarPcaViewModel::class, (string) $metodo->getReturnType());
        $this->assertNull((new \ReflectionClass(PublicadorPcaScriptcase::class))->getConstructor());
    }

    #[DataProvider('requestIdsInvalidos')]
    public function testRequestIdInvalidoDeveFalharAntesDeLerORegistro(?string $requestId): void
    {
        $marcador = $this->caminhoTemporario('registro-lido.txt');
        $registro = $this->escreverPhpBruto(
            'registro-marcador.php',
            "file_put_contents(" . var_export($marcador, true) . ", 'lido');\nreturn [];"
        );
        putenv(self::VARIAVEL_REGISTRO . '=' . $registro);

        $viewModel = $this->executar($requestId);

        $this->assertInterrupcaoRequestIdInvalido($viewModel);
        $this->assertFileDoesNotExist($marcador);
    }

    /** @return array<string, array{?string}> */
    public static function requestIdsInvalidos(): array
    {
        return [
            'ausente' => [null],
            'vazio' => [''],
            'espaços' => ['   '],
            'UUID v1' => ['550e8400-e29b-11d4-a716-446655440000'],
            'variante inválida' => ['550e8400-e29b-41d4-7716-446655440000'],
            'prefixo com espaço' => [' 550e8400-e29b-41d4-a716-446655440000'],
            'sufixo com quebra' => ["550e8400-e29b-41d4-a716-446655440000\n"],
            'chaves externas' => ['{550e8400-e29b-41d4-a716-446655440000}'],
        ];
    }

    public function testRequestIdValidoDeveSerPreservadoQuandoRegistroEstaAusente(): void
    {
        putenv(self::VARIAVEL_REGISTRO);

        $viewModel = $this->executar(self::REQUEST_ID);

        $this->assertInterrupcaoInesperada($viewModel, self::REQUEST_ID);
    }

    public function testLocalizadorERegistroInvalidosDevemFalharFechado(): void
    {
        $perfil = $this->escreverRetornoPhp('perfil-minimo.php', [
            'cnpj' => '12.345.678/0001-95',
        ]);
        $casos = [
            false,
            '',
            '   ',
            'registro-relativo.php',
            $this->caminhoTemporario('registro-ausente.php'),
            $this->diretorioTemporario,
            __FILE__,
            $this->escreverRetornoPhp('registro-nao-array.php', 'invalido'),
            $this->escreverRetornoPhp('registro-chave-numerica.php', [$perfil]),
            $this->escreverRetornoPhp('registro-valor-array.php', [
                self::SERVIDOR => ['perfil' => $perfil],
            ]),
            $this->escreverRetornoPhp('registro-perfil-relativo.php', [
                self::SERVIDOR => 'perfil.php',
            ]),
            $this->escreverRetornoPhp('registro-perfil-ausente.php', [
                self::SERVIDOR => $this->caminhoTemporario('perfil-ausente.php'),
            ]),
            $this->escreverRetornoPhp('registro-perfil-diretorio.php', [
                self::SERVIDOR => $this->diretorioTemporario,
            ]),
            $this->escreverRetornoPhp('registro-perfil-no-repositorio.php', [
                self::SERVIDOR => __FILE__,
            ]),
        ];

        foreach ($casos as $localizador) {
            if ($localizador === false) {
                putenv(self::VARIAVEL_REGISTRO);
            } else {
                putenv(self::VARIAVEL_REGISTRO . '=' . $localizador);
            }

            $this->assertInterrupcaoInesperada(
                $this->executar(self::REQUEST_ID),
                self::REQUEST_ID
            );
        }
    }

    public function testRegistroInteiroDeveSerValidoAntesDaSelecao(): void
    {
        $marcador = $this->caminhoTemporario('perfil-lido.txt');
        $perfil = $this->escreverPhpBruto(
            'perfil-marcador.php',
            "file_put_contents(" . var_export($marcador, true) . ", 'lido');\n"
                . "return ['cnpj' => '12.345.678/0001-95'];"
        );
        $registro = $this->escreverRetornoPhp('registro-inteiro-invalido.php', [
            self::SERVIDOR => $perfil,
            'outro.servidor.test' => $this->caminhoTemporario('outro-ausente.php'),
        ]);
        putenv(self::VARIAVEL_REGISTRO . '=' . $registro);

        $viewModel = $this->executar(self::REQUEST_ID);

        $this->assertInterrupcaoInesperada($viewModel, self::REQUEST_ID);
        $this->assertFileDoesNotExist($marcador);
    }

    public function testSelecaoDeServidorDeveSerLiteralSemNormalizacaoOuInferencia(): void
    {
        $marcador = $this->caminhoTemporario('perfil-selecionado.txt');
        $perfil = $this->escreverPhpBruto(
            'perfil-selecionado.php',
            "file_put_contents(" . var_export($marcador, true) . ", 'lido');\n"
                . "return ['cnpj' => '12.345.678/0001-95'];"
        );
        $registro = $this->escreverRetornoPhp('registro-exato.php', [
            self::SERVIDOR => $perfil,
        ]);
        putenv(self::VARIAVEL_REGISTRO . '=' . $registro);

        foreach (['PCA.EXATO.TEST', ' pca.exato.test', 'pca.exato.test '] as $servidor) {
            $this->assertInterrupcaoInesperada(
                $this->executar(self::REQUEST_ID, $servidor),
                self::REQUEST_ID
            );
            $this->assertFileDoesNotExist($marcador);
        }

        $this->assertInterrupcaoInesperada(
            $this->executar(self::REQUEST_ID, self::SERVIDOR),
            self::REQUEST_ID
        );
        $this->assertFileExists($marcador);
    }

    public function testPerfilECnpjInvalidosDevemFalharAntesDaExecucaoExterna(): void
    {
        $perfis = [
            $this->escreverRetornoPhp('perfil-nao-array.php', 'invalido'),
            $this->escreverRetornoPhp('perfil-sem-cnpj.php', ['ambiente' => 'HOMOLOGACAO']),
            $this->escreverRetornoPhp('perfil-cnpj-tipo-invalido.php', ['cnpj' => 123]),
            $this->escreverRetornoPhp('perfil-cnpj-invalido.php', ['cnpj' => 'invalido']),
            $this->escreverRetornoPhp('perfil-incoerente.php', [
                'cnpj' => '12.345.678/0001-95',
            ]),
        ];

        foreach ($perfis as $indice => $perfil) {
            $registro = $this->escreverRetornoPhp("registro-perfil-{$indice}.php", [
                self::SERVIDOR => $perfil,
            ]);
            putenv(self::VARIAVEL_REGISTRO . '=' . $registro);

            $this->assertInterrupcaoInesperada(
                $this->executar(self::REQUEST_ID),
                self::REQUEST_ID
            );
        }
    }

    private function executar(
        ?string $requestId,
        string $serverName = self::SERVIDOR
    ): PublicarPcaViewModel {
        return (new PublicadorPcaScriptcase())->executar(
            new \stdClass(),
            'usuario.teste',
            $serverName,
            (int) date('Y'),
            $requestId
        );
    }

    private function assertInterrupcaoRequestIdInvalido(PublicarPcaViewModel $viewModel): void
    {
        $this->assertSame('INTERROMPIDA', $viewModel->getSituacao());
        $this->assertSame('REQUEST_ID_INVALIDO', $viewModel->getCodigoFalha());
        $this->assertSame(
            'Identificador da requisição ausente ou inválido.',
            $viewModel->getMensagemFalha()
        );
        $this->assertSame([], $viewModel->getResultadosUnitarios());
        $this->assertSame(0, $viewModel->getQuantidadePlanejadas());
        $this->assertSame(0, $viewModel->getQuantidadeVisitadas());
        $this->assertSame(0, $viewModel->getQuantidadeNaoProcessadas());
        $this->assertNull($viewModel->getCorrelacao());
    }

    private function assertInterrupcaoInesperada(
        PublicarPcaViewModel $viewModel,
        string $correlacao
    ): void {
        $this->assertSame('INTERROMPIDA', $viewModel->getSituacao());
        $this->assertSame('FALHA_INESPERADA', $viewModel->getCodigoFalha());
        $this->assertSame(
            'Não foi possível concluir a publicação do PCA.',
            $viewModel->getMensagemFalha()
        );
        $this->assertSame([], $viewModel->getResultadosUnitarios());
        $this->assertSame(0, $viewModel->getQuantidadePlanejadas());
        $this->assertSame(0, $viewModel->getQuantidadeVisitadas());
        $this->assertSame(0, $viewModel->getQuantidadeNaoProcessadas());
        $this->assertSame($correlacao, $viewModel->getCorrelacao());
    }

    /** @param mixed $retorno */
    private function escreverRetornoPhp(string $nome, mixed $retorno): string
    {
        return $this->escreverPhpBruto(
            $nome,
            'return ' . var_export($retorno, true) . ';'
        );
    }

    private function escreverPhpBruto(string $nome, string $codigo): string
    {
        $caminho = $this->caminhoTemporario($nome);
        $escrito = file_put_contents($caminho, "<?php\n\n{$codigo}\n");

        if ($escrito === false) {
            throw new RuntimeException('Não foi possível escrever arquivo temporário.');
        }

        return $caminho;
    }

    private function caminhoTemporario(string $nome): string
    {
        $caminho = $this->diretorioTemporario . DIRECTORY_SEPARATOR . $nome;

        if (!in_array($caminho, $this->arquivosTemporarios, true)) {
            $this->arquivosTemporarios[] = $caminho;
        }

        return $caminho;
    }
}
