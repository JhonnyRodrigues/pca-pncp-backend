<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint59\Support;

use DateTimeZone;
use RuntimeException;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Semae\Pca\Infrastructure\Oracle\Auditoria\MapeamentoHistoricosOracle;
use Semae\Pca\Infrastructure\Oracle\Auditoria\OracleRegistroAuditoriaAdapter;
use Semae\Pca\Infrastructure\Oracle\Contexto\MapeamentoLotacoesUsuarioOracle;
use Semae\Pca\Infrastructure\Oracle\Contexto\MapeamentoRepresentantesOracle;
use Semae\Pca\Infrastructure\Oracle\Contexto\OracleContextoUsuarioAdapter;
use Semae\Pca\Infrastructure\Oracle\EstadoOperacional\MapeamentoEstadoOperacionalOracle;
use Semae\Pca\Infrastructure\Oracle\EstadoOperacional\OracleEstadoOperacionalPublicacaoAdapter;
use Semae\Pca\Infrastructure\Oracle\Estrategia\MapeamentoConfiguracaoEstrategiaOracle;
use Semae\Pca\Infrastructure\Oracle\Estrategia\MapeamentoPlanosEstrategiaOracle;
use Semae\Pca\Infrastructure\Oracle\Estrategia\OracleEstrategiaPublicacaoAdapter;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoDemandasOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoLotacoesOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoPlanosOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoUnidadesOrcamentariasOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\OraclePlanoRepository;
use Semae\Pca\Infrastructure\Oracle\Transacao\MapeamentoControlesConcorrenciaOracle;
use Semae\Pca\Infrastructure\Oracle\Transacao\OracleUnidadeDeTrabalhoAdapter;
use Tests\Contract\Support\OracleAdaptersExercise;
use Tests\Contract\Support\OracleAdaptersSet;
use Tests\Contract\Support\OracleContractFailure;

final class OracleAdaptersContractRunner
{
    private readonly OracleAdaptersExercise $exercise;

    /** @param array<string, mixed> $config */
    public function __construct(object $db, string $login, private readonly array $config)
    {
        $mapping = $config['mappings'];
        $guard = new BorrowedAdodbGuard($db);
        $connection = new ConexaoAdodbEmprestada($guard);
        $unitsMapping = new MapeamentoUnidadesOrcamentariasOracle(
            ...$mapping['unidades_orcamentarias']
        );
        $historyMapping = new MapeamentoHistoricosOracle(...$mapping['historicos']);
        $adapters = new OracleAdaptersSet(
            new OracleContextoUsuarioAdapter(
                $connection,
                new MapeamentoRepresentantesOracle(...$mapping['representantes']),
                new MapeamentoLotacoesUsuarioOracle(...$mapping['lotacoes_usuario']),
                $login
            ),
            new OraclePlanoRepository(
                $connection,
                new MapeamentoPlanosOracle(...$mapping['planos']),
                new MapeamentoDemandasOracle(...$mapping['demandas']),
                $unitsMapping,
                new MapeamentoLotacoesOracle(...$mapping['lotacoes']),
                new DateTimeZone($config['timezone'])
            ),
            new OracleEstrategiaPublicacaoAdapter(
                $connection,
                new MapeamentoConfiguracaoEstrategiaOracle(
                    ...$mapping['configuracao_estrategia']
                ),
                new MapeamentoPlanosEstrategiaOracle(...$mapping['planos_estrategia']),
                $unitsMapping
            ),
            new OracleUnidadeDeTrabalhoAdapter(
                $connection,
                new MapeamentoControlesConcorrenciaOracle(
                    ...$mapping['controles_concorrencia']
                )
            ),
            new OracleEstadoOperacionalPublicacaoAdapter(
                $connection,
                new MapeamentoEstadoOperacionalOracle(...$mapping['estado_operacional'])
            ),
            new OracleRegistroAuditoriaAdapter($connection, $historyMapping)
        );
        $this->exercise = new OracleAdaptersExercise(
            $guard,
            $connection,
            $adapters,
            $historyMapping,
            $config,
            'SPRINT_59_BIND'
        );
    }

    /** @param array<string, mixed> $config */
    public static function fromConfig(object $db, string $login, array $config): self
    {
        return new self($db, $login, Sprint59Config::oracle($config));
    }

    /** @return array<string, mixed> */
    public function run(string $mode): array
    {
        if (!in_array($mode, [
            'adapters',
            'lock_holder',
            'lock_expect_blocked',
            'lock_expect_available',
        ], true)) {
            throw new RuntimeException('Modo Oracle invalido.');
        }

        try {
            $checks = $this->exercise->run($mode);
        } catch (OracleContractFailure $failure) {
            if ($mode === 'adapters') {
                throw new OracleAdaptersContractFailure(
                    $failure->getStage(),
                    $failure->getCause()
                );
            }

            throw $failure->getCause();
        }

        return [
            'sprint' => '5.9',
            'gate' => 'ORACLE_SCRIPTCASE',
            'modo' => $mode,
            'confirmado' => true,
            'verificacoes' => $checks,
            'adapters' => [
                OracleUnidadeDeTrabalhoAdapter::class,
                OracleEstrategiaPublicacaoAdapter::class,
                OraclePlanoRepository::class,
                OracleEstadoOperacionalPublicacaoAdapter::class,
                OracleContextoUsuarioAdapter::class,
                OracleRegistroAuditoriaAdapter::class,
            ],
            'detalhes_sensiveis_exibidos' => false,
        ];
    }

    /** @param array<int, array<int|string, mixed>> $rows */
    private function firstScalar(array $rows, string $expectedAlias): mixed
    {
        if (count($rows) !== 1) {
            throw new RuntimeException(
                'Consulta contratual Oracle nao retornou linha unica.'
            );
        }

        foreach ($rows[0] as $alias => $value) {
            if (is_string($alias) && strcasecmp($alias, $expectedAlias) === 0) {
                return $value;
            }
        }

        throw new RuntimeException(
            'Consulta contratual Oracle nao retornou o alias esperado.'
        );
    }
}
