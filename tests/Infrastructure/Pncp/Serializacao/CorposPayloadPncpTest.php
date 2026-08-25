<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Pncp\Serializacao;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Commands\ComandoExcluirItensPca;
use Semae\Pca\Application\Commands\ComandoExcluirPca;
use Semae\Pca\Application\Commands\ComandoInserirItensPca;
use Semae\Pca\Application\Commands\ComandoPublicarPca;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\Demanda;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\Enums\SituacaoDemanda;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\DecimalExato;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Domain\ValueObjects\Uasg;
use Semae\Pca\Infrastructure\Pncp\Serializacao\FalhaSerializacaoPncp;
use Semae\Pca\Infrastructure\Pncp\Serializacao\SerializadorPayloadPncp;

final class CorposPayloadPncpTest extends TestCase
{
    public function testDeveSerializarIntegralmenteOsQuatroCorpos(): void
    {
        [$cnpj, $unidadeExterna, $unidade, $ano] = $this->criarContexto();
        $demanda = $this->criarDemanda(7, $unidade, $ano);
        $sequencial = new SequencialPca(55);
        $serializador = new SerializadorPayloadPncp();

        $item = '{'
            . '"numeroItem":7,'
            . '"categoriaItemPca":1,'
            . '"catalogo":2,'
            . '"classificacaoCatalogo":"1",'
            . '"classificacaoSuperiorCodigo":"GRUPO-1",'
            . '"classificacaoSuperiorNome":"Grupo de teste",'
            . '"codigoItem":"ITEM-1",'
            . '"descricao":"Item de teste",'
            . '"unidadeFornecimento":"UNIDADE",'
            . '"quantidade":6.0000,'
            . '"valorUnitario":0.1667,'
            . '"valorTotal":1.0000,'
            . '"valorOrcamentoExercicio":0.5000,'
            . '"dataDesejada":"' . $ano->getValor() . '-03-15"'
            . '}';
        $itens = '[' . $item . ']';

        $this->assertSame(
            '{"codigoUnidade":"986789","anoPca":' . $ano->getValor()
                . ',"itensPlano":' . $itens . '}',
            $serializador->serializarPublicacao(
                new ComandoPublicarPca($cnpj, $unidadeExterna, [$demanda])
            )
        );
        $this->assertSame(
            $itens,
            $serializador->serializarInclusaoItens(
                new ComandoInserirItensPca($cnpj, $unidadeExterna, $sequencial, [$demanda])
            )
        );
        $this->assertSame(
            '{"justificativa":"Retificação aprovada.","listaNumerosItens":[7,30]}',
            $serializador->serializarExclusaoItens(
                new ComandoExcluirItensPca(
                    $cnpj,
                    $unidadeExterna,
                    $sequencial,
                    [30, 7],
                    'Retificação aprovada.'
                )
            )
        );
        $this->assertSame(
            '{"justificativa":"Cancelamento da última demanda."}',
            $serializador->serializarExclusaoPca(
                new ComandoExcluirPca(
                    $cnpj,
                    $unidadeExterna,
                    $sequencial,
                    'Cancelamento da última demanda.'
                )
            )
        );
    }

    public function testDeveAceitarCodigoUnidadeExatamenteNoLimiteUnicode(): void
    {
        $codigoUnidade = str_repeat('á', 20);
        [$cnpj, $unidadeExterna, $unidade, $ano] = $this->criarContexto($codigoUnidade);
        $demanda = $this->criarDemanda(1, $unidade, $ano);

        $json = (new SerializadorPayloadPncp())->serializarPublicacao(
            new ComandoPublicarPca($cnpj, $unidadeExterna, [$demanda])
        );

        $this->assertStringStartsWith(
            '{"codigoUnidade":"' . $codigoUnidade . '","anoPca":',
            $json
        );
    }

    public function testDeveRejeitarCodigoUnidadeAcimaDoLimiteSemTruncar(): void
    {
        [$cnpj, $unidadeExterna, $unidade, $ano] = $this->criarContexto(
            str_repeat('á', 21)
        );
        $demanda = $this->criarDemanda(1, $unidade, $ano);
        $this->esperarFalhaSegura();

        (new SerializadorPayloadPncp())->serializarPublicacao(
            new ComandoPublicarPca($cnpj, $unidadeExterna, [$demanda])
        );
    }

    /** @return iterable<string, array{string}> */
    public static function tiposExclusao(): iterable
    {
        yield 'itens' => ['itens'];
        yield 'PCA' => ['pca'];
    }

    #[DataProvider('tiposExclusao')]
    public function testDeveAceitarJustificativaExatamenteNoLimiteUnicode(string $tipo): void
    {
        $justificativa = str_repeat('ç', 255);

        $this->assertStringContainsString(
            '"justificativa":"' . $justificativa . '"',
            $this->serializarExclusao($tipo, $justificativa)
        );
    }

    #[DataProvider('tiposExclusao')]
    public function testDeveRejeitarJustificativaAcimaDoLimiteSemTruncar(string $tipo): void
    {
        $this->esperarFalhaSegura();

        $this->serializarExclusao($tipo, str_repeat('ç', 256));
    }

    /** @return iterable<string, array{int}> */
    public static function idsExclusaoInvalidos(): iterable
    {
        yield 'zero' => [0];
        yield 'negativo' => [-1];
        yield 'acima de int32' => [2_147_483_648];
    }

    #[DataProvider('idsExclusaoInvalidos')]
    public function testDeveRejeitarNumeroItemInvalidoNaExclusao(int $id): void
    {
        [$cnpj, $unidadeExterna] = $this->criarContexto();
        $comando = new ComandoExcluirItensPca(
            $cnpj,
            $unidadeExterna,
            new SequencialPca(55),
            [$id],
            'Retificação aprovada.'
        );
        $this->esperarFalhaSegura();

        (new SerializadorPayloadPncp())->serializarExclusaoItens($comando);
    }

    public function testNaoDeveRedefinirLimiteOperacionalDaApplication(): void
    {
        [$cnpj, $unidadeExterna, $unidade, $ano] = $this->criarContexto();
        $demandas = [];

        for ($id = 1; $id <= 1001; $id++) {
            $demandas[] = $this->criarDemanda($id, $unidade, $ano);
        }

        $json = (new SerializadorPayloadPncp())->serializarInclusaoItens(
            new ComandoInserirItensPca(
                $cnpj,
                $unidadeExterna,
                new SequencialPca(55),
                $demandas
            )
        );

        $this->assertSame(1001, substr_count($json, '"numeroItem":'));
    }

    private function serializarExclusao(string $tipo, string $justificativa): string
    {
        [$cnpj, $unidadeExterna] = $this->criarContexto();
        $sequencial = new SequencialPca(55);
        $serializador = new SerializadorPayloadPncp();

        if ($tipo === 'itens') {
            return $serializador->serializarExclusaoItens(
                new ComandoExcluirItensPca(
                    $cnpj,
                    $unidadeExterna,
                    $sequencial,
                    [1],
                    $justificativa
                )
            );
        }

        return $serializador->serializarExclusaoPca(
            new ComandoExcluirPca($cnpj, $unidadeExterna, $sequencial, $justificativa)
        );
    }

    /**
     * @return array{Cnpj, UnidadeExternaPublicacao, UnidadeOrcamentaria, AnoExecucao}
     */
    private function criarContexto(string $codigoUnidade = '986789'): array
    {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $unidade = new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria('UO-01'),
            new Lotacao(10, 'Diretoria'),
            new Uasg($codigoUnidade, 'UASG da UO'),
            'Unidade Orçamentária'
        );
        $unidadeExterna = UnidadeExternaPublicacao::individual(
            new IdentidadePlano($unidade, $ano)
        );

        return [new Cnpj('12.345.678/0001-95'), $unidadeExterna, $unidade, $ano];
    }

    private function criarDemanda(
        int $id,
        UnidadeOrcamentaria $unidade,
        AnoExecucao $ano
    ): Demanda {
        return new Demanda(
            $id,
            SituacaoDemanda::HOMOLOGADA,
            159,
            $unidade,
            $ano,
            new DateTimeImmutable($ano->getValor() . '-03-15'),
            1,
            'Item de teste',
            'UNIDADE',
            6,
            new DecimalExato('1'),
            new DecimalExato('0.5'),
            2,
            'ITEM-1',
            'GRUPO-1',
            'Grupo de teste',
            'N'
        );
    }

    private function esperarFalhaSegura(): void
    {
        $this->expectException(FalhaSerializacaoPncp::class);
        $this->expectExceptionMessage('Não foi possível serializar o payload PNCP.');
    }
}
