<?php

declare(strict_types=1);

namespace Tests\Application\Commands;

use DateTimeImmutable;
use InvalidArgumentException;
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
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Domain\ValueObjects\Uasg;

final class ComandosEscritaPncpTest extends TestCase
{
    public function testPublicacaoDeveConterContextoEConjuntoInicialDeDemandas(): void
    {
        [$cnpj, $unidade, $demandas] = $this->criarContexto();

        $comando = new ComandoPublicarPca($cnpj, $unidade, $demandas);

        $this->assertSame($cnpj, $comando->getCnpj());
        $this->assertSame($unidade, $comando->getUnidadeExterna());
        $this->assertSame($demandas, $comando->getDemandas());
    }

    public function testPublicacaoDeveExigirAoMenosUmaDemanda(): void
    {
        [$cnpj, $unidade] = $this->criarContexto();

        $this->expectException(InvalidArgumentException::class);

        new ComandoPublicarPca($cnpj, $unidade, []);
    }

    public function testPublicacaoDeveRejeitarDemandasDuplicadas(): void
    {
        [$cnpj, $unidade, $demandas] = $this->criarContexto();

        $this->expectException(InvalidArgumentException::class);

        new ComandoPublicarPca($cnpj, $unidade, [$demandas[0], $demandas[0]]);
    }

    public function testPublicacaoDeveRejeitarValoresQueNaoSaoDemandas(): void
    {
        [$cnpj, $unidade] = $this->criarContexto();

        $this->expectException(InvalidArgumentException::class);

        new ComandoPublicarPca($cnpj, $unidade, ['1001']);
    }

    public function testInclusaoDeveConterPublicacaoConhecidaEDemandasNovas(): void
    {
        [$cnpj, $unidade, $demandas] = $this->criarContexto();
        $sequencial = new SequencialPca(55);

        $comando = new ComandoInserirItensPca($cnpj, $unidade, $sequencial, $demandas);

        $this->assertSame($cnpj, $comando->getCnpj());
        $this->assertSame($unidade, $comando->getUnidadeExterna());
        $this->assertSame($sequencial, $comando->getSequencialPca());
        $this->assertSame($demandas, $comando->getDemandas());
    }

    public function testInclusaoDeveExigirAoMenosUmaDemanda(): void
    {
        [$cnpj, $unidade] = $this->criarContexto();

        $this->expectException(InvalidArgumentException::class);

        new ComandoInserirItensPca($cnpj, $unidade, new SequencialPca(55), []);
    }

    public function testInclusaoDeveRejeitarDemandasDuplicadas(): void
    {
        [$cnpj, $unidade, $demandas] = $this->criarContexto();

        $this->expectException(InvalidArgumentException::class);

        new ComandoInserirItensPca(
            $cnpj,
            $unidade,
            new SequencialPca(55),
            [$demandas[0], $demandas[0]]
        );
    }

    public function testExclusaoDeItensDeveConterIdsUnicosEJustificativaNormalizada(): void
    {
        [$cnpj, $unidade] = $this->criarContexto();
        $sequencial = new SequencialPca(55);

        $comando = new ComandoExcluirItensPca(
            $cnpj,
            $unidade,
            $sequencial,
            [1001, 1002],
            ' Cancelamentos homologados no SGI. '
        );

        $this->assertSame($cnpj, $comando->getCnpj());
        $this->assertSame($unidade, $comando->getUnidadeExterna());
        $this->assertSame($sequencial, $comando->getSequencialPca());
        $this->assertSame([1001, 1002], $comando->getIdsDemandas());
        $this->assertSame('Cancelamentos homologados no SGI.', $comando->getJustificativa());
    }

    /** @param array<int, mixed> $ids */
    #[DataProvider('idsInvalidos')]
    public function testExclusaoDeItensDeveRejeitarIdsInvalidos(array $ids): void
    {
        [$cnpj, $unidade] = $this->criarContexto();

        $this->expectException(InvalidArgumentException::class);

        new ComandoExcluirItensPca(
            $cnpj,
            $unidade,
            new SequencialPca(55),
            $ids,
            'Cancelamentos homologados no SGI.'
        );
    }

    /** @return array<string, array{array<int, mixed>}> */
    public static function idsInvalidos(): array
    {
        return [
            'coleção vazia' => [[]],
            'id textual' => [['1001']],
            'ids duplicados' => [[1001, 1001]],
        ];
    }

    public function testExclusaoDeItensDeveExigirJustificativa(): void
    {
        [$cnpj, $unidade] = $this->criarContexto();

        $this->expectException(InvalidArgumentException::class);

        new ComandoExcluirItensPca(
            $cnpj,
            $unidade,
            new SequencialPca(55),
            [1001],
            '   '
        );
    }

    public function testExclusaoIntegralDeveConterPublicacaoEJustificativaNormalizada(): void
    {
        [$cnpj, $unidade] = $this->criarContexto();
        $sequencial = new SequencialPca(55);

        $comando = new ComandoExcluirPca(
            $cnpj,
            $unidade,
            $sequencial,
            ' Cancelamento da última Demanda ativa. '
        );

        $this->assertSame($cnpj, $comando->getCnpj());
        $this->assertSame($unidade, $comando->getUnidadeExterna());
        $this->assertSame($sequencial, $comando->getSequencialPca());
        $this->assertSame('Cancelamento da última Demanda ativa.', $comando->getJustificativa());
    }

    public function testExclusaoIntegralDeveExigirJustificativa(): void
    {
        [$cnpj, $unidade] = $this->criarContexto();

        $this->expectException(InvalidArgumentException::class);

        new ComandoExcluirPca($cnpj, $unidade, new SequencialPca(55), '');
    }

    /**
     * @return array{Cnpj, UnidadeExternaPublicacao, Demanda[]}
     */
    private function criarContexto(): array
    {
        $ano = new AnoExecucao((int) date('Y') + 1);
        $unidadeOrcamentaria = new UnidadeOrcamentaria(
            new IdentidadeUnidadeOrcamentaria('UO-01'),
            new Lotacao(10, 'Diretoria'),
            new Uasg('986789', 'UASG da UO'),
            'Unidade Orçamentária'
        );
        $unidadeExterna = UnidadeExternaPublicacao::individual(
            new IdentidadePlano($unidadeOrcamentaria, $ano)
        );

        return [
            new Cnpj('12.345.678/0001-95'),
            $unidadeExterna,
            [
                $this->criarDemanda(1001, $unidadeOrcamentaria, $ano),
                $this->criarDemanda(1002, $unidadeOrcamentaria, $ano),
            ],
        ];
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
            'ITEM ' . $id,
            'UNIDADE',
            1,
            100.00,
            100.00,
            2,
            (string) $id,
            '98',
            'INFORMÁTICA',
            'N'
        );
    }
}
