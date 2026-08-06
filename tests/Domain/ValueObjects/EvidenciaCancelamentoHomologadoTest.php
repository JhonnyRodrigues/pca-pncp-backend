<?php

declare(strict_types=1);

namespace Tests\Domain\ValueObjects;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\EvidenciaCancelamentoHomologado;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Uasg;

final class EvidenciaCancelamentoHomologadoTest extends TestCase
{
    /** @regra RN-PUB-003 */
    public function testDevePreservarIdentidadeDemandaEJustificativaNormalizada(): void
    {
        $identidade = $this->criarIdentidade('uo-101');

        $evidencia = new EvidenciaCancelamentoHomologado(
            $identidade,
            731,
            '  Cancelamento homologado pela autoridade competente.  '
        );

        $this->assertSame($identidade, $evidencia->getIdentidadePlano());
        $this->assertSame(731, $evidencia->getIdDemanda());
        $this->assertSame(
            'Cancelamento homologado pela autoridade competente.',
            $evidencia->getJustificativa()
        );
    }

    /** @regra RN-PUB-003 */
    #[DataProvider('idsInvalidos')]
    public function testDeveRejeitarIdentificadorDeDemandaNaoPositivo(int $idDemanda): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identificador da Demanda deve ser positivo.');

        new EvidenciaCancelamentoHomologado(
            $this->criarIdentidade('uo-101'),
            $idDemanda,
            'Cancelamento homologado.'
        );
    }

    /** @regra RN-PUB-003 */
    public function testDeveRejeitarJustificativaVazia(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Justificativa do cancelamento é obrigatória.');

        new EvidenciaCancelamentoHomologado(
            $this->criarIdentidade('uo-101'),
            731,
            " \t\n "
        );
    }

    /** @regra RN-PUB-003 */
    public function testDeveCompararTodosOsComponentesDoValor(): void
    {
        $evidencia = new EvidenciaCancelamentoHomologado(
            $this->criarIdentidade('uo-101'),
            731,
            'Cancelamento homologado.'
        );

        $this->assertTrue($evidencia->equals(new EvidenciaCancelamentoHomologado(
            $this->criarIdentidade('uo-101'),
            731,
            ' Cancelamento homologado. '
        )));
        $this->assertFalse($evidencia->equals(new EvidenciaCancelamentoHomologado(
            $this->criarIdentidade('uo-102'),
            731,
            'Cancelamento homologado.'
        )));
        $this->assertFalse($evidencia->equals(new EvidenciaCancelamentoHomologado(
            $this->criarIdentidade('uo-101'),
            732,
            'Cancelamento homologado.'
        )));
        $this->assertFalse($evidencia->equals(new EvidenciaCancelamentoHomologado(
            $this->criarIdentidade('uo-101'),
            731,
            'Outra justificativa homologada.'
        )));
    }

    /** @return array<string, array{int}> */
    public static function idsInvalidos(): array
    {
        return [
            'zero' => [0],
            'negativo' => [-1],
        ];
    }

    private function criarIdentidade(string $idUnidade): IdentidadePlano
    {
        return new IdentidadePlano(
            new UnidadeOrcamentaria(
                new IdentidadeUnidadeOrcamentaria($idUnidade),
                new Lotacao(101, 'Diretoria Administrativa'),
                new Uasg('928344', 'SEMAE Piracicaba'),
                'Diretoria Administrativa'
            ),
            new AnoExecucao((int) date('Y') + 1)
        );
    }
}
