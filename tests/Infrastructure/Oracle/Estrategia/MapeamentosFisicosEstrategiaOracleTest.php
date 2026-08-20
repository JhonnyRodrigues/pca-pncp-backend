<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Oracle\Estrategia;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Infrastructure\Oracle\Estrategia\MapeamentoCadastroUasgOracle;
use Semae\Pca\Infrastructure\Oracle\Estrategia\MapeamentoConfiguracaoEstrategiaOracle;
use Semae\Pca\Infrastructure\Oracle\Estrategia\MapeamentoPlanosEstrategiaOracle;

final class MapeamentosFisicosEstrategiaOracleTest extends TestCase
{
    public function testDeveDeclararConfiguracaoSemDefaultsImplicitos(): void
    {
        $mapeamento = new MapeamentoConfiguracaoEstrategiaOracle(
            'app',
            'pac_configuracoes',
            'chave',
            'valor',
            'PUBLICAR_EM_UNICA_UASG',
            'UASG_CONSOLIDADORA',
            'NAO',
            'SIM'
        );

        $this->assertSame('APP.PAC_CONFIGURACOES', $mapeamento->getTabelaQualificada());
        $this->assertSame('CHAVE', $mapeamento->getColunaChave());
        $this->assertSame('VALOR', $mapeamento->getColunaValor());
        $this->assertSame('PUBLICAR_EM_UNICA_UASG', $mapeamento->getChaveModo());
        $this->assertSame('UASG_CONSOLIDADORA', $mapeamento->getChaveUasgConsolidadora());
        $this->assertSame('NAO', $mapeamento->getLiteralModoIndividual());
        $this->assertSame('SIM', $mapeamento->getLiteralModoConsolidado());
    }

    public function testDeveDeclararProjecaoDaEstrategiaNosPlanos(): void
    {
        $mapeamento = new MapeamentoPlanosEstrategiaOracle(
            'app',
            'pac_planos',
            'ano_execucao',
            'fk_unidade_orcamentaria',
            'modo_publicacao',
            'uasg_publicacao',
            'INDIVIDUAL',
            'CONSOLIDADO'
        );

        $this->assertSame('APP.PAC_PLANOS', $mapeamento->getTabelaQualificada());
        $this->assertSame('ANO_EXECUCAO', $mapeamento->getColunaAnoExecucao());
        $this->assertSame(
            'FK_UNIDADE_ORCAMENTARIA',
            $mapeamento->getColunaUnidadeOrcamentaria()
        );
        $this->assertSame('MODO_PUBLICACAO', $mapeamento->getColunaModoAdotado());
        $this->assertSame('UASG_PUBLICACAO', $mapeamento->getColunaUasgAdotada());
        $this->assertSame('INDIVIDUAL', $mapeamento->getLiteralModoIndividual());
        $this->assertSame('CONSOLIDADO', $mapeamento->getLiteralModoConsolidado());
    }

    public function testDeveDeclararLookupAtivoEUniquenessDaUasgConsolidadora(): void
    {
        $mapeamento = new MapeamentoCadastroUasgOracle(
            'app',
            'pac_unidades_orcamentarias',
            'id_unidade_orcamentaria',
            'uasg',
            'descricao',
            'ativa',
            'S'
        );

        $this->assertSame(
            'APP.PAC_UNIDADES_ORCAMENTARIAS',
            $mapeamento->getTabelaQualificada()
        );
        $this->assertSame(
            'ID_UNIDADE_ORCAMENTARIA',
            $mapeamento->getColunaUnidadeOrcamentaria()
        );
        $this->assertSame('UASG', $mapeamento->getColunaCodigo());
        $this->assertSame('DESCRICAO', $mapeamento->getColunaDescricao());
        $this->assertSame('ATIVA', $mapeamento->getColunaAtivo());
        $this->assertSame('S', $mapeamento->getValorAtivo());
        $this->assertSame(1, $mapeamento->getCardinalidadeExataDoLookup());
    }

    /** @return array<string, array{class-string, array<int, string>}> */
    public static function identificadoresInvalidos(): array
    {
        return [
            'configuração' => [
                MapeamentoConfiguracaoEstrategiaOracle::class,
                ['APP', 'PAC CONFIG', 'CHAVE', 'VALOR', 'MODO', 'UASG', 'N', 'S'],
            ],
            'planos' => [
                MapeamentoPlanosEstrategiaOracle::class,
                ['APP', 'PAC_PLANOS', 'ANO;DROP', 'UO', 'MODO', 'UASG', 'I', 'C'],
            ],
            'cadastro UASG' => [
                MapeamentoCadastroUasgOracle::class,
                ['APP', 'PAC_UO', 'ID', 'UASG', 'DESC', 'ATIVA = S', 'S'],
            ],
        ];
    }

    /** @param class-string $classe */
    #[DataProvider('identificadoresInvalidos')]
    public function testDeveRejeitarIdentificadorForaDaAllowlist(
        string $classe,
        array $argumentos
    ): void {
        $this->expectException(InvalidArgumentException::class);

        new $classe(...$argumentos);
    }

    public function testDeveRejeitarLiteralObrigatorioVazio(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new MapeamentoCadastroUasgOracle(
            'APP',
            'PAC_UO',
            'ID',
            'UASG',
            'DESCRICAO',
            'ATIVA',
            ' '
        );
    }

    public function testDeveRejeitarChavesOuModosFisicosIndistinguiveis(): void
    {
        foreach (
            [
                fn () => new MapeamentoConfiguracaoEstrategiaOracle(
                    'APP', 'PAC_CONFIG', 'CHAVE', 'VALOR', 'MODO', 'MODO', 'N', 'S'
                ),
                fn () => new MapeamentoPlanosEstrategiaOracle(
                    'APP', 'PAC_PLANOS', 'ANO', 'UO', 'MODO', 'UASG', 'X', 'X'
                ),
            ] as $criar
        ) {
            try {
                $criar();
                $this->fail('Era esperada rejeição de literais físicos indistinguíveis.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
