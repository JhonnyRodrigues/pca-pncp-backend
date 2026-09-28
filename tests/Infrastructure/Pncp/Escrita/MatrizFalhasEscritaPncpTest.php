<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Pncp\Escrita;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Semae\Pca\Application\Commands\ComandoExcluirItensPca;
use Semae\Pca\Application\Commands\ComandoExcluirPca;
use Semae\Pca\Application\Commands\ComandoInserirItensPca;
use Semae\Pca\Application\Commands\ComandoPublicarPca;
use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Application\Enums\SituacaoEscritaPncp;
use Semae\Pca\Application\Results\ResultadoEscritaPncp;
use Semae\Pca\Application\Results\ResultadoPublicacaoPncp;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\Demanda;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\Enums\SituacaoDemanda;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\DecimalExato;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Domain\ValueObjects\Uasg;
use Semae\Pca\Infrastructure\Pncp\Autenticacao\SessaoPncpAutenticada;
use Semae\Pca\Infrastructure\Pncp\Escrita\EscritorPncpAdapter;
use Semae\Pca\Infrastructure\Pncp\Http\AmbientePncp;
use Semae\Pca\Infrastructure\Pncp\Http\ConfiguracaoTransportePncp;
use Semae\Pca\Infrastructure\Pncp\Http\FalhaTransportePncpException;
use Semae\Pca\Infrastructure\Pncp\Http\RequisicaoHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\RespostaHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Serializacao\SerializadorPayloadPncp;
use Semae\Pca\Infrastructure\Observabilidade\AcumuladorEventosTecnicos;
use Semae\Pca\Infrastructure\Observabilidade\RelogioEventosTecnicos;
use Stringable;
use Throwable;

final class MatrizFalhasEscritaPncpTest extends TestCase
{
    #[DataProvider('matrizStatusHttp')]
    public function testDeveClassificarStatusHttpSemRetry(
        string $operacao,
        int $status,
        SituacaoEscritaPncp $situacao,
        string $codigo,
        string $mensagem
    ): void {
        $transporte = new TransporteMatrizEscritaPncpFake(
            new RespostaHttpPncp($status, [], '{"erro":"corpo externo"}')
        );

        $resultado = $this->executar($operacao, $this->adapter($transporte));

        $this->assertSame($situacao, $resultado->getSituacao());
        $this->assertSame($codigo, $resultado->getFalha()?->getCodigo());
        $this->assertSame($mensagem, $resultado->getFalha()?->getMensagem());
        $this->assertCount(1, $transporte->requisicoes);
    }

    /**
     * @return iterable<string, array{string, int, SituacaoEscritaPncp, string, string}>
     */
    public static function matrizStatusHttp(): iterable
    {
        foreach (self::operacoes() as $operacao => $dados) {
            foreach ([400, 422] as $status) {
                yield $operacao . ' rejeitada ' . $status => [
                    $operacao,
                    $status,
                    SituacaoEscritaPncp::REJEITADA,
                    'PNCP_' . $dados['codigo'] . '_REJEITADA',
                    'O PNCP rejeitou a ' . $dados['descricao'] . '.',
                ];
            }

            foreach ([401, 403] as $status) {
                yield $operacao . ' sistêmica ' . $status => [
                    $operacao,
                    $status,
                    SituacaoEscritaPncp::FALHA_SISTEMICA,
                    'PNCP_' . $dados['codigo'] . '_NAO_AUTORIZADA',
                    'A ' . $dados['descricao'] . ' no PNCP não foi autorizada.',
                ];
            }

            foreach ([202, 204, 300, 404, 408, 429, 500, 503, 599] as $status) {
                yield $operacao . ' incerta ' . $status => [
                    $operacao,
                    $status,
                    SituacaoEscritaPncp::RESULTADO_INCERTO,
                    'PNCP_' . $dados['codigo'] . '_RESULTADO_INCERTO',
                    'Não foi possível determinar o resultado da '
                        . $dados['descricao'] . ' no PNCP.',
                ];
            }

            if (in_array($operacao, ['excluirItens', 'excluirPca'], true)) {
                yield $operacao . ' incerta 201' => [
                    $operacao,
                    201,
                    SituacaoEscritaPncp::RESULTADO_INCERTO,
                    'PNCP_' . $dados['codigo'] . '_RESULTADO_INCERTO',
                    'Não foi possível determinar o resultado da '
                        . $dados['descricao'] . ' no PNCP.',
                ];
            }
        }
    }

    #[DataProvider('operacoesProvider')]
    public function testDeveAcrescentarMensagemDoPncpNaFalhaExibidaAoOperador(
        string $operacao
    ): void {
        $detalhe = 'Operação não autorizada. Somente o usuário que publicou a informação pode editá-la.';
        $transporte = new TransporteMatrizEscritaPncpFake(new RespostaHttpPncp(
            403,
            ['Content-Type' => ['application/json']],
            json_encode(['message' => $detalhe], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
        ));

        $resultado = $this->executar($operacao, $this->adapter($transporte));

        $mensagemBase = 'A ' . self::operacoes()[$operacao]['descricao']
            . ' no PNCP não foi autorizada.';
        $this->assertSame(
            $mensagemBase . ' O PNCP informou: ' . $detalhe,
            $resultado->getFalha()?->getMensagem()
        );
        $this->assertSame(
            'PNCP_' . self::operacoes()[$operacao]['codigo'] . '_NAO_AUTORIZADA',
            $resultado->getFalha()?->getCodigo()
        );
    }

    #[DataProvider('falhasTransporte')]
    public function testDeveClassificarFalhaDeTransporteSemRetry(
        string $operacao,
        Throwable $falha,
        SituacaoEscritaPncp $situacao,
        string $sufixoCodigo,
        string $mensagem
    ): void {
        $transporte = new TransporteMatrizEscritaPncpFake($falha);

        $resultado = $this->executar($operacao, $this->adapter($transporte));

        $this->assertSame($situacao, $resultado->getSituacao());
        $this->assertSame(
            'PNCP_' . self::operacoes()[$operacao]['codigo'] . '_' . $sufixoCodigo,
            $resultado->getFalha()?->getCodigo()
        );
        $this->assertSame($mensagem, $resultado->getFalha()?->getMensagem());
        $this->assertCount(1, $transporte->requisicoes);
    }

    /**
     * @return iterable<string, array{
     *     string,
     *     Throwable,
     *     SituacaoEscritaPncp,
     *     string,
     *     string
     * }>
     */
    public static function falhasTransporte(): iterable
    {
        $sistemicas = [
            'cURL indisponível' => FalhaTransportePncpException::extensaoCurlIndisponivel(),
            'inicialização' => FalhaTransportePncpException::inicializacaoCurlFalhou(),
            'configuração' => FalhaTransportePncpException::configuracaoCurlFalhou(),
            'limite de payload' => FalhaTransportePncpException::limitePayloadExcedido(),
            'TLS não confirmado' => FalhaTransportePncpException::tlsNaoConfirmado(),
        ];
        $incertas = [
            'timeout' => FalhaTransportePncpException::execucaoCurlFalhou(28),
            'execução interrompida' => FalhaTransportePncpException::execucaoCurlFalhou(42),
            'limite de resposta' => FalhaTransportePncpException::limiteRespostaExcedido(),
            'resposta HTTP inválida' => FalhaTransportePncpException::respostaHttpInvalida(),
            'falha inesperada' => new \RuntimeException(
                'token-ultrassecreto em mensagem externa'
            ),
        ];

        foreach (array_keys(self::operacoes()) as $operacao) {
            foreach ($sistemicas as $cenario => $falha) {
                yield $operacao . ' sistêmica por ' . $cenario => [
                    $operacao,
                    $falha,
                    SituacaoEscritaPncp::FALHA_SISTEMICA,
                    'TRANSPORTE_FALHOU',
                    'Não foi possível executar a '
                        . self::operacoes()[$operacao]['descricao'] . ' no PNCP.',
                ];
            }

            foreach ($incertas as $cenario => $falha) {
                yield $operacao . ' incerta por ' . $cenario => [
                    $operacao,
                    $falha,
                    SituacaoEscritaPncp::RESULTADO_INCERTO,
                    'RESULTADO_INCERTO',
                    'Não foi possível determinar o resultado da '
                        . self::operacoes()[$operacao]['descricao'] . ' no PNCP.',
                ];
            }
        }
    }

    #[DataProvider('operacoesProvider')]
    public function testSessaoIncompativelDeveFalharAntesDoDespacho(string $operacao): void
    {
        $transporte = new TransporteMatrizEscritaPncpFake(
            new RespostaHttpPncp(200, [], '')
        );

        $resultado = $this->executar(
            $operacao,
            $this->adapter($transporte),
            new SessaoMatrizEscritaPncpFake()
        );

        $this->assertSame(SituacaoEscritaPncp::FALHA_SISTEMICA, $resultado->getSituacao());
        $this->assertSame(
            'PNCP_' . self::operacoes()[$operacao]['codigo'] . '_SESSAO_INVALIDA',
            $resultado->getFalha()?->getCodigo()
        );
        $this->assertSame(
            'A sessão PNCP não é compatível com a '
                . self::operacoes()[$operacao]['descricao'] . '.',
            $resultado->getFalha()?->getMensagem()
        );
        $this->assertCount(0, $transporte->requisicoes);
    }

    #[DataProvider('operacoesProvider')]
    public function testFalhaDeSerializacaoDeveOcorrerAntesDoDespacho(string $operacao): void
    {
        $transporte = new TransporteMatrizEscritaPncpFake(
            new RespostaHttpPncp(200, [], '')
        );

        $resultado = $this->executar(
            $operacao,
            $this->adapter($transporte),
            new SessaoPncpAutenticada('token-ultrassecreto'),
            true
        );

        $this->assertSame(SituacaoEscritaPncp::FALHA_SISTEMICA, $resultado->getSituacao());
        $this->assertSame(
            'PNCP_' . self::operacoes()[$operacao]['codigo'] . '_PREPARACAO_FALHOU',
            $resultado->getFalha()?->getCodigo()
        );
        $this->assertSame(
            'Não foi possível preparar a '
                . self::operacoes()[$operacao]['descricao'] . ' no PNCP.',
            $resultado->getFalha()?->getMensagem()
        );
        $this->assertCount(0, $transporte->requisicoes);
    }

    #[DataProvider('escritasSemIdentificadorProvider')]
    public function testCorpoEHeadersDaRespostaNaoDevemControlarEscritaAceita(
        string $operacao,
        int $status
    ): void {
        $transporte = new TransporteMatrizEscritaPncpFake(new RespostaHttpPncp(
            $status,
            ['Location' => ['valor divergente', 'duplicado']],
            '{json inválido e sem identificador'
        ));

        $resultado = $this->executar($operacao, $this->adapter($transporte));

        $this->assertSame(SituacaoEscritaPncp::ACEITA, $resultado->getSituacao());
        $this->assertNull($resultado->getFalha());
        $this->assertCount(1, $transporte->requisicoes);
    }

    /** @return iterable<string, array{string}> */
    public static function operacoesProvider(): iterable
    {
        foreach (array_keys(self::operacoes()) as $operacao) {
            yield $operacao => [$operacao];
        }
    }

    /** @return iterable<string, array{string, int}> */
    public static function escritasSemIdentificadorProvider(): iterable
    {
        yield 'inclusão 200' => ['inserir', 200];
        yield 'inclusão 201' => ['inserir', 201];
        yield 'exclusão de itens' => ['excluirItens', 200];
        yield 'exclusão integral' => ['excluirPca', 200];
    }

    #[DataProvider('operacoesProvider')]
    public function testEventoDeFalhaDevePreservarErroSeguroSemDadosSensiveis(
        string $operacao
    ): void
    {
        $logger = new AcumuladorEventosTecnicos(
            '550e8400-e29b-41d4-a716-446655440000',
            'treinamento',
            'publicacao-pca',
            null,
            new RelogioMatrizEscritaPncpFake()
        );
        $transporte = new TransporteMatrizEscritaPncpFake(new RespostaHttpPncp(
            422,
            ['Authorization' => ['Bearer token-ultrassecreto']],
            '{"cnpj":"12345678000195","uasg":"110001","id":1001}'
        ));

        $resultado = $this->executar($operacao, $this->adapter($transporte, $logger));

        $this->assertSame(SituacaoEscritaPncp::REJEITADA, $resultado->getSituacao());
        $this->assertCount(1, $logger->getEventos());
        $envelope = $logger->getEventos()[0]->paraEnvelope();
        $codigo = 'PNCP_' . self::operacoes()[$operacao]['codigo'] . '_REJEITADA';
        $mensagem = 'O PNCP rejeitou a ' . self::operacoes()[$operacao]['descricao'] . '.';
        $this->assertSame(self::operacoes()[$operacao]['acao'], $envelope['action']);
        $this->assertSame($mensagem, $envelope['message']);
        $this->assertSame([
            'code' => $codigo,
            'message' => $mensagem,
        ], $envelope['error']);
        $this->assertSame([], $envelope['context']);
        $this->assertSame(
            [
                'situacao' => SituacaoEscritaPncp::REJEITADA->value,
                'statusHttp' => 422,
                'quantidade' => self::operacoes()[$operacao]['quantidade'],
            ],
            $envelope['metadata']
        );

        $serializado = json_encode(
            [$resultado->getFalha(), $envelope],
            JSON_THROW_ON_ERROR
        );
        $this->assertStringNotContainsString('token-ultrassecreto', $serializado);
        $this->assertStringNotContainsString('Authorization', $serializado);
        $this->assertStringNotContainsString('12345678000195', $serializado);
        $this->assertStringNotContainsString('110001', $serializado);
        $this->assertStringNotContainsString('/v1/orgaos/', $serializado);
        $this->assertStringNotContainsString('1001', $serializado);
    }

    private function executar(
        string $operacao,
        EscritorPncpAdapter $adapter,
        ?SessaoPncp $sessao = null,
        bool $serializacaoInvalida = false
    ): ResultadoPublicacaoPncp|ResultadoEscritaPncp {
        $sessao ??= new SessaoPncpAutenticada('token-ultrassecreto');

        return match ($operacao) {
            'publicar' => $adapter->publicar(
                $sessao,
                $this->comandoPublicar($serializacaoInvalida)
            ),
            'inserir' => $adapter->inserirItens(
                $sessao,
                $this->comandoInserir($serializacaoInvalida)
            ),
            'excluirItens' => $adapter->excluirItens(
                $sessao,
                $this->comandoExcluirItens($serializacaoInvalida)
            ),
            'excluirPca' => $adapter->excluirPca(
                $sessao,
                $this->comandoExcluirPca($serializacaoInvalida)
            ),
            default => throw new \LogicException('Operação de teste desconhecida.'),
        };
    }

    private function comandoPublicar(bool $invalido): ComandoPublicarPca
    {
        return new ComandoPublicarPca(
            $this->cnpj(),
            $this->unidadeExterna(),
            [$this->demanda($invalido)]
        );
    }

    private function comandoInserir(bool $invalido): ComandoInserirItensPca
    {
        return new ComandoInserirItensPca(
            $this->cnpj(),
            $this->unidadeExterna(),
            new SequencialPca(77),
            [$this->demanda($invalido)]
        );
    }

    private function comandoExcluirItens(bool $invalido): ComandoExcluirItensPca
    {
        return new ComandoExcluirItensPca(
            $this->cnpj(),
            $this->unidadeExterna(),
            new SequencialPca(77),
            [1001, 1003],
            $invalido ? str_repeat('x', 256) : 'Exclusão homologada.'
        );
    }

    private function comandoExcluirPca(bool $invalido): ComandoExcluirPca
    {
        return new ComandoExcluirPca(
            $this->cnpj(),
            $this->unidadeExterna(),
            new SequencialPca(77),
            $invalido ? str_repeat('x', 256) : 'Exclusão integral homologada.'
        );
    }

    private function demanda(bool $invalida): Demanda
    {
        $ano = new AnoExecucao(2026);
        $unidade = new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria('UO-10'),
            new Lotacao(20, 'Lotação de teste'),
            new Uasg('110001', 'UASG de teste'),
            'Unidade de teste'
        );

        return new Demanda(
            1001,
            SituacaoDemanda::HOMOLOGADA,
            159,
            $unidade,
            $ano,
            new DateTimeImmutable('2026-03-15'),
            1,
            $invalida ? str_repeat('x', 2049) : 'Item de teste',
            'UNIDADE',
            2,
            new DecimalExato('100'),
            new DecimalExato('100'),
            2,
            1,
            'ITEM-1',
            'GRUPO-1',
            'Grupo de teste',
            'N'
        );
    }

    private function cnpj(): Cnpj
    {
        return new Cnpj('12.345.678/0001-95');
    }

    private function unidadeExterna(): UnidadeExternaPublicacao
    {
        return UnidadeExternaPublicacao::consolidada(
            new AnoExecucao(2026),
            new Uasg('110001', 'UASG de teste')
        );
    }

    private function adapter(
        TransporteHttpPncp $transporte,
        ?LoggerInterface $logger = null
    ): EscritorPncpAdapter {
        return new EscritorPncpAdapter(
            $transporte,
            new ConfiguracaoTransportePncp(
                AmbientePncp::HOMOLOGACAO,
                1_000_000,
                1_000_000
            ),
            new SerializadorPayloadPncp(),
            $logger ?? new LoggerMatrizEscritaPncpFake()
        );
    }

    /**
     * @return array<string, array{
     *     codigo: string,
     *     descricao: string,
     *     acao: string,
     *     quantidade: int
     * }>
     */
    private static function operacoes(): array
    {
        return [
            'publicar' => [
                'codigo' => 'PUBLICACAO',
                'descricao' => 'publicação',
                'acao' => 'PUBLICAR_PCA_PNCP',
                'quantidade' => 1,
            ],
            'inserir' => [
                'codigo' => 'INCLUSAO_ITENS',
                'descricao' => 'inclusão de itens',
                'acao' => 'INSERIR_ITENS_PCA_PNCP',
                'quantidade' => 1,
            ],
            'excluirItens' => [
                'codigo' => 'EXCLUSAO_ITENS',
                'descricao' => 'exclusão de itens',
                'acao' => 'EXCLUIR_ITENS_PCA_PNCP',
                'quantidade' => 2,
            ],
            'excluirPca' => [
                'codigo' => 'EXCLUSAO_PCA',
                'descricao' => 'exclusão integral',
                'acao' => 'EXCLUIR_PCA_PNCP',
                'quantidade' => 1,
            ],
        ];
    }
}

final class TransporteMatrizEscritaPncpFake implements TransporteHttpPncp
{
    /** @var RequisicaoHttpPncp[] */
    public array $requisicoes = [];

    public function __construct(private readonly RespostaHttpPncp|Throwable $resultado) {}

    public function enviar(RequisicaoHttpPncp $requisicao): RespostaHttpPncp
    {
        $this->requisicoes[] = $requisicao;

        if ($this->resultado instanceof Throwable) {
            throw $this->resultado;
        }

        return $this->resultado;
    }
}

final class SessaoMatrizEscritaPncpFake implements SessaoPncp {}

final class LoggerMatrizEscritaPncpFake extends AbstractLogger
{
    /** @var array<int, array{level: mixed, message: string, context: array<string, mixed>}> */
    public array $registros = [];

    /** @param array<string, mixed> $context */
    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $this->registros[] = [
            'level' => $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }
}

final class RelogioMatrizEscritaPncpFake implements RelogioEventosTecnicos
{
    public function agora(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-08-26T12:00:00+00:00');
    }

    public function tempoMonotonicoMs(): int
    {
        return 1000;
    }
}
