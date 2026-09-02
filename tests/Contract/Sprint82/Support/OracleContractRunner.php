<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint82\Support;

use RuntimeException;
use Semae\Pca\Application\ValueObjects\CorrelacaoExecucao;
use Semae\Pca\Composition\ComposicaoOraclePublicacaoPca;
use Semae\Pca\Composition\CompositionRootPublicacaoPca;
use Semae\Pca\Composition\PerfilPublicacaoPca;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Semae\Pca\Infrastructure\Oracle\Auditoria\OracleRegistroAuditoriaAdapter;
use Semae\Pca\Infrastructure\Oracle\Contexto\OracleContextoUsuarioAdapter;
use Semae\Pca\Infrastructure\Oracle\EstadoOperacional\OracleEstadoOperacionalPublicacaoAdapter;
use Semae\Pca\Infrastructure\Oracle\Estrategia\OracleEstrategiaPublicacaoAdapter;
use Semae\Pca\Infrastructure\Oracle\Plano\OraclePlanoRepository;
use Semae\Pca\Infrastructure\Oracle\Transacao\OracleUnidadeDeTrabalhoAdapter;
use Tests\Contract\Support\BorrowedAdodbGuard;
use Tests\Contract\Support\OracleAdaptersExercise;
use Tests\Contract\Support\OracleAdaptersSet;

final class OracleContractRunner
{
    private const CORRELACAO_GATE = '00000000-0000-4000-8000-000000000082';

    private function __construct(
        private readonly OracleAdaptersExercise $exercise,
        private readonly bool $rootMountedWithoutIo
    ) {
    }

    /** @param array<string, mixed> $config */
    public static function fromConfig(
        object $db,
        string $login,
        string $serverName,
        array $config
    ): self {
        $rawProfile = Sprint82Config::loadHomologationProfileArray(
            $config,
            $serverName
        );
        $profile = new PerfilPublicacaoPca($rawProfile);
        $guard = new BorrowedAdodbGuard($db);

        (new CompositionRootPublicacaoPca())->montar(
            $guard,
            $login,
            $rawProfile,
            new CorrelacaoExecucao(self::CORRELACAO_GATE)
        );
        if ($guard->countRecordedCalls() !== 0) {
            throw new RuntimeException(
                'A montagem do Composition Root executou I/O inesperado.'
            );
        }

        $connection = new ConexaoAdodbEmprestada($guard);
        $oracle = new ComposicaoOraclePublicacaoPca($connection, $profile, $login);
        $exercise = new OracleAdaptersExercise(
            $guard,
            $connection,
            OracleAdaptersSet::fromProductionComposition($oracle),
            $profile->getHistoricos(),
            $config['massa'],
            'SPRINT_82_BIND'
        );

        return new self($exercise, true);
    }

    /** @return array<string, mixed> */
    public function run(string $mode): array
    {
        if (!in_array($mode, [
            'commit',
            'adapters',
            'lock_holder',
            'lock_expect_blocked',
            'lock_expect_available',
        ], true)) {
            throw new RuntimeException('Modo Oracle invalido.');
        }

        $checks = $this->exercise->run($mode);
        $checks['composition_root_montado_sem_io'] = $this->rootMountedWithoutIo;

        return [
            'sprint' => '8.2',
            'gate' => 'ORACLE_COMPOSITION_SCRIPTCASE',
            'modo' => $mode,
            'confirmado' => true,
            'verificacoes' => $checks,
            'adapters' => [
                OracleContextoUsuarioAdapter::class,
                OraclePlanoRepository::class,
                OracleEstrategiaPublicacaoAdapter::class,
                OracleUnidadeDeTrabalhoAdapter::class,
                OracleEstadoOperacionalPublicacaoAdapter::class,
                OracleRegistroAuditoriaAdapter::class,
            ],
            'detalhes_sensiveis_exibidos' => false,
        ];
    }
}
