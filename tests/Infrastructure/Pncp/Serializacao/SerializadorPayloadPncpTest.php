<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Pncp\Serializacao;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\Commands\ComandoInserirItensPca;
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

final class SerializadorPayloadPncpTest extends TestCase
{
    public function testDeveSerializarItensIntegralmenteEmOrdemNumericaSemRenumerar(): void
    {
        [$unidade, $ano] = $this->criarUnidadeEAno();
        $servico = $this->criarDemanda(
            30,
            $unidade,
            $ano,
            [
                'dataDesejada' => new DateTimeImmutable($ano->getValor() . '-07-10 23:45:00-03:00'),
                'categoria' => 2,
                'catalogo' => 1,
                'descricao' => 'Pintura industrial',
                'unidadeMedida' => 'UNIDADE',
                'quantidade' => 2,
                'valorTotal' => '1.00005',
                'valorOrcamentoExercicio' => '0.33335',
                'codigoItem' => '1090',
                'grupo' => '547',
                'descricaoGrupo' => 'Serviço de acabamento',
            ]
        );
        $material = $this->criarDemanda(
            7,
            $unidade,
            $ano,
            [
                'dataDesejada' => new DateTimeImmutable($ano->getValor() . '-05-15 00:30:00+14:00'),
                'categoria' => 1,
                'catalogo' => 2,
                'descricao' => 'Apontador Lápis',
                'unidadeMedida' => 'Caixa 100 unidades',
                'quantidade' => 6,
                'valorTotal' => '1',
                'valorOrcamentoExercicio' => '1',
                'codigoItem' => '468205',
                'grupo' => '7510',
                'descricaoGrupo' => 'Artigos para escritório',
            ]
        );

        $json = (new SerializadorPayloadPncp())->serializarInclusaoItens(
            $this->criarComando([$servico, $material], $unidade, $ano)
        );

        $esperado = '[{'
            . '"numeroItem":7,'
            . '"categoriaItemPca":1,'
            . '"catalogo":2,'
            . '"classificacaoCatalogo":"1",'
            . '"classificacaoSuperiorCodigo":"7510",'
            . '"classificacaoSuperiorNome":"Artigos para escritório",'
            . '"codigoItem":"468205",'
            . '"descricao":"Apontador Lápis",'
            . '"unidadeFornecimento":"Caixa 100 unidades",'
            . '"quantidade":6.0000,'
            . '"valorUnitario":0.1667,'
            . '"valorTotal":1.0000,'
            . '"valorOrcamentoExercicio":1.0000,'
            . '"dataDesejada":"' . $ano->getValor() . '-05-15"'
            . '},{'
            . '"numeroItem":30,'
            . '"categoriaItemPca":2,'
            . '"catalogo":1,'
            . '"classificacaoCatalogo":"2",'
            . '"classificacaoSuperiorCodigo":"547",'
            . '"classificacaoSuperiorNome":"Serviço de acabamento",'
            . '"codigoItem":"1090",'
            . '"descricao":"Pintura industrial",'
            . '"unidadeFornecimento":"UNIDADE",'
            . '"quantidade":2.0000,'
            . '"valorUnitario":0.5000,'
            . '"valorTotal":1.0001,'
            . '"valorOrcamentoExercicio":0.3334,'
            . '"dataDesejada":"' . $ano->getValor() . '-07-10"'
            . '}]';

        $this->assertSame($esperado, $json);
        $this->assertStringNotContainsString('pdm', $json);
        $this->assertStringNotContainsString('unidadeRequisitante', $json);
        $this->assertStringNotContainsString('grupoContratacao', $json);
        $this->assertStringNotContainsString('renovacaoContrato', $json);
        $this->assertStringNotContainsString('origem', $json);
    }

    /** @return iterable<string, array{string, int}> */
    public static function limitesTextuais(): iterable
    {
        yield 'codigo do item' => ['codigoItem', 100];
        yield 'codigo da classificacao superior' => ['grupo', 100];
        yield 'nome da classificacao superior' => ['descricaoGrupo', 255];
        yield 'unidade de fornecimento' => ['unidadeMedida', 255];
        yield 'descricao' => ['descricao', 2048];
    }

    #[DataProvider('limitesTextuais')]
    public function testDeveRejeitarTextoAcimaDoLimiteSemTruncar(
        string $campo,
        int $limite
    ): void {
        [$unidade, $ano] = $this->criarUnidadeEAno();
        $demanda = $this->criarDemanda(
            1,
            $unidade,
            $ano,
            [$campo => str_repeat('á', $limite + 1)]
        );

        $this->esperarFalhaSegura();

        (new SerializadorPayloadPncp())->serializarInclusaoItens(
            $this->criarComando([$demanda], $unidade, $ano)
        );
    }

    public function testDeveAceitarTodosOsTextosExatamenteNoLimiteEmCaracteresUnicode(): void
    {
        [$unidade, $ano] = $this->criarUnidadeEAno();
        $demanda = $this->criarDemanda(
            1,
            $unidade,
            $ano,
            [
                'codigoItem' => str_repeat('á', 100),
                'grupo' => str_repeat('ê', 100),
                'descricaoGrupo' => str_repeat('ç', 255),
                'unidadeMedida' => str_repeat('ã', 255),
                'descricao' => str_repeat('ó', 2048),
            ]
        );

        $json = (new SerializadorPayloadPncp())->serializarInclusaoItens(
            $this->criarComando([$demanda], $unidade, $ano)
        );

        $this->assertStringContainsString(str_repeat('á', 100), $json);
        $this->assertStringContainsString(str_repeat('ê', 100), $json);
        $this->assertStringContainsString(str_repeat('ç', 255), $json);
        $this->assertStringContainsString(str_repeat('ã', 255), $json);
        $this->assertStringContainsString(str_repeat('ó', 2048), $json);
    }

    public function testDeveRejeitarUtf8InvalidoEmCampoTextual(): void
    {
        [$unidade, $ano] = $this->criarUnidadeEAno();
        $demanda = $this->criarDemanda(
            1,
            $unidade,
            $ano,
            ['descricao' => "\xB1\x31"]
        );

        $this->esperarFalhaSegura();

        (new SerializadorPayloadPncp())->serializarInclusaoItens(
            $this->criarComando([$demanda], $unidade, $ano)
        );
    }

    /** @return iterable<string, array{int}> */
    public static function numerosItemInvalidos(): iterable
    {
        yield 'zero' => [0];
        yield 'negativo' => [-1];
        yield 'acima de int32' => [2_147_483_648];
    }

    #[DataProvider('numerosItemInvalidos')]
    public function testDeveRejeitarNumeroItemForaDoContratoInt32(int $numeroItem): void
    {
        [$unidade, $ano] = $this->criarUnidadeEAno();
        $demanda = $this->criarDemanda($numeroItem, $unidade, $ano);

        $this->esperarFalhaSegura();

        (new SerializadorPayloadPncp())->serializarInclusaoItens(
            $this->criarComando([$demanda], $unidade, $ano)
        );
    }

    public function testDeveAceitarLimiteInt32ComoNumeroItemSemRenumerar(): void
    {
        [$unidade, $ano] = $this->criarUnidadeEAno();
        $demanda = $this->criarDemanda(2_147_483_647, $unidade, $ano);

        $json = (new SerializadorPayloadPncp())->serializarInclusaoItens(
            $this->criarComando([$demanda], $unidade, $ano)
        );

        $this->assertStringStartsWith('[{"numeroItem":2147483647,', $json);
    }

    /** @return array{UnidadeOrcamentaria, AnoExecucao} */
    private function criarUnidadeEAno(): array
    {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $unidade = new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria('UO-01'),
            new Lotacao(10, 'Diretoria'),
            new Uasg('986789', 'UASG da UO'),
            'Unidade Orçamentária'
        );

        return [$unidade, $ano];
    }

    /**
     * @param Demanda[] $demandas
     */
    private function criarComando(
        array $demandas,
        UnidadeOrcamentaria $unidade,
        AnoExecucao $ano
    ): ComandoInserirItensPca {
        return new ComandoInserirItensPca(
            new Cnpj('12.345.678/0001-95'),
            UnidadeExternaPublicacao::individual(new IdentidadePlano($unidade, $ano)),
            new SequencialPca(55),
            $demandas
        );
    }

    /**
     * @param array{
     *     dataDesejada?: DateTimeImmutable,
     *     categoria?: int,
     *     descricao?: string,
     *     unidadeMedida?: string,
     *     quantidade?: int,
     *     valorTotal?: string,
     *     valorOrcamentoExercicio?: string,
     *     catalogo?: int,
     *     codigoItem?: string,
     *     grupo?: string,
     *     descricaoGrupo?: string
     * } $dados
     */
    private function criarDemanda(
        int $id,
        UnidadeOrcamentaria $unidade,
        AnoExecucao $ano,
        array $dados = []
    ): Demanda {
        return new Demanda(
            $id,
            SituacaoDemanda::HOMOLOGADA,
            159,
            $unidade,
            $ano,
            $dados['dataDesejada'] ?? new DateTimeImmutable($ano->getValor() . '-03-15'),
            $dados['categoria'] ?? 1,
            $dados['descricao'] ?? 'Item de teste',
            $dados['unidadeMedida'] ?? 'UNIDADE',
            $dados['quantidade'] ?? 1,
            new DecimalExato($dados['valorTotal'] ?? '100'),
            new DecimalExato($dados['valorOrcamentoExercicio'] ?? '100'),
            $dados['catalogo'] ?? 2,
            $dados['codigoItem'] ?? 'ITEM-1',
            $dados['grupo'] ?? 'GRUPO-1',
            $dados['descricaoGrupo'] ?? 'Grupo de teste',
            'N'
        );
    }

    private function esperarFalhaSegura(): void
    {
        $this->expectException(FalhaSerializacaoPncp::class);
        $this->expectExceptionMessage('Não foi possível serializar o payload PNCP.');
    }
}
