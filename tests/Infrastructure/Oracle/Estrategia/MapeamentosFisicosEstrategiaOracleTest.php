<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Oracle\Estrategia;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Infrastructure\Oracle\Estrategia\MapeamentoConfiguracaoEstrategiaOracle;
use Semae\Pca\Infrastructure\Oracle\Estrategia\MapeamentoPlanosEstrategiaOracle;

final class MapeamentosFisicosEstrategiaOracleTest extends TestCase
{
    public function testDeveDeclararConfiguracaoSemDefaultsImplicitos(): void
    {
        $mapeamento = new MapeamentoConfiguracaoEstrategiaOracle(
            'sgi',
            'pac_configuracoes',
            'chave',
            'valor',
            'descricao',
            'ESTRATEGIA_PUBLICACAO_PLANOS',
            'UASG_CONSOLIDADORA',
            'INDIVIDUAL',
            'CONSOLIDADO'
        );

        $this->assertSame('SGI.PAC_CONFIGURACOES', $mapeamento->getTabelaQualificada());
        $this->assertSame('CHAVE', $mapeamento->getColunaChave());
        $this->assertSame('VALOR', $mapeamento->getColunaValor());
        $this->assertSame('DESCRICAO', $mapeamento->getColunaDescricao());
        $this->assertSame('ESTRATEGIA_PUBLICACAO_PLANOS', $mapeamento->getChaveEstrategia());
        $this->assertSame('UASG_CONSOLIDADORA', $mapeamento->getChaveUasgConsolidadora());
        $this->assertSame('INDIVIDUAL', $mapeamento->getLiteralEstrategiaIndividual());
        $this->assertSame('CONSOLIDADO', $mapeamento->getLiteralEstrategiaConsolidada());
    }

    public function testDeveDeclararProjecaoDaEstrategiaNosPlanos(): void
    {
        $mapeamento = new MapeamentoPlanosEstrategiaOracle(
            'sgi',
            'pac_planos',
            'ano_exercicio',
            'fk_unidade_orcamentaria',
            'estrategia_adotada_publicacao',
            'uasg_adotada',
            'INDIVIDUAL',
            'CONSOLIDADO'
        );

        $this->assertSame('SGI.PAC_PLANOS', $mapeamento->getTabelaQualificada());
        $this->assertSame('ANO_EXERCICIO', $mapeamento->getColunaAnoExecucao());
        $this->assertSame('FK_UNIDADE_ORCAMENTARIA', $mapeamento->getColunaUnidadeOrcamentaria());
        $this->assertSame('ESTRATEGIA_ADOTADA_PUBLICACAO', $mapeamento->getColunaEstrategiaAdotada());
        $this->assertSame('UASG_ADOTADA', $mapeamento->getColunaUasgAdotada());
        $this->assertSame('INDIVIDUAL', $mapeamento->getLiteralEstrategiaIndividual());
        $this->assertSame('CONSOLIDADO', $mapeamento->getLiteralEstrategiaConsolidada());
    }

    /** @return array<string, array{class-string, array<int, string>}> */
    public static function identificadoresInvalidos(): array
    {
        return [
            'configuracao' => [
                MapeamentoConfiguracaoEstrategiaOracle::class,
                ['SGI', 'PAC CONFIG', 'CHAVE', 'VALOR', 'DESCRICAO', 'ESTRATEGIA', 'UASG', 'I', 'C'],
            ],
            'planos' => [
                MapeamentoPlanosEstrategiaOracle::class,
                ['SGI', 'PAC_PLANOS', 'ANO;DROP', 'UO', 'ESTRATEGIA', 'UASG', 'I', 'C'],
            ],
        ];
    }

    /** @param class-string $classe */
    #[DataProvider('identificadoresInvalidos')]
    public function testDeveRejeitarIdentificadorForaDaAllowlist(string $classe, array $argumentos): void
    {
        $this->expectException(InvalidArgumentException::class);

        new $classe(...$argumentos);
    }

    public function testDeveRejeitarChavesOuEstrategiasFisicasIndistinguiveis(): void
    {
        foreach (
            [
                fn () => new MapeamentoConfiguracaoEstrategiaOracle(
                    'SGI', 'PAC_CONFIG', 'CHAVE', 'VALOR', 'DESCRICAO',
                    'ESTRATEGIA', 'ESTRATEGIA', 'INDIVIDUAL', 'CONSOLIDADO'
                ),
                fn () => new MapeamentoPlanosEstrategiaOracle(
                    'SGI', 'PAC_PLANOS', 'ANO', 'UO', 'ESTRATEGIA', 'UASG', 'X', 'X'
                ),
            ] as $criar
        ) {
            try {
                $criar();
                $this->fail('Era esperada rejeicao de valores fisicos indistinguiveis.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
