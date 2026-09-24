<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint94\Support;

use Psr\Log\NullLogger;
use Semae\Pca\Application\Enums\SituacaoAutenticacaoPncp;
use Semae\Pca\Composition\PerfilPublicacaoPca;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Infrastructure\Pncp\Autenticacao\AutenticadorPncpAdapter;
use Semae\Pca\Infrastructure\Pncp\Autenticacao\SessaoPncpAutenticada;
use Semae\Pca\Infrastructure\Pncp\Http\ExecutorCurlNativoPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteCurlPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;
use Throwable;

final class Sprint94InventoryGate
{
    /** @param array<string, mixed> $configuration */
    private function __construct(
        private readonly object $adodb,
        private readonly array $configuration,
        private readonly PerfilPublicacaoPca $profile,
        private readonly string $organizationDocument,
        private readonly ?TransporteHttpPncp $transport
    ) {}

    public static function fromEnvironment(
        object $adodb,
        string $login,
        string $serverName,
        ?TransporteHttpPncp $transport = null
    ): self {
        $configuration = Sprint94Config::loadFromEnvironment();
        $loaded = Sprint94Config::loadProductionProfile($configuration, $login, $serverName);

        return new self(
            $adodb,
            $configuration,
            $loaded['profile'],
            $loaded['cnpj'],
            $transport
        );
    }

    /** @return array<string, mixed> */
    public function precheck(): array
    {
        $this->requireStage('PRECHECK');
        $methods = ['BeginTrans', 'CommitTrans', 'RollbackTrans', 'Execute', 'GetAll', 'Param', 'ErrorNo', 'ErrorMsg'];
        $missing = array_values(array_filter(
            $methods,
            fn (string $method): bool => !method_exists($this->adodb, $method)
        ));

        $dependencies = [
            'adodb_metodos_disponiveis' => $missing === [],
            'curl_disponivel' => extension_loaded('curl'),
            'json_disponivel' => extension_loaded('json'),
            'php_8_1_ou_superior' => PHP_VERSION_ID >= 80100,
        ];

        return Sprint94Report::create([
            'anos' => $this->configuration['anos'],
            'ambiente' => $this->profile->getAmbiente()->value,
            'dependencias' => $dependencies,
            'escritas' => ['logs' => false, 'oracle' => false, 'pncp' => false],
            'etapa' => 'PRECHECK',
            'resultado' => !in_array(false, $dependencies, true)
                ? 'PRECHECK_APROVADO'
                : 'PRECHECK_RECUSADO',
            'sprint' => '9.4',
        ]);
    }

    /** @return array<string, mixed> */
    public function inventory(): array
    {
        $this->requireStage('INVENTARIO');
        $oracle = (new Sprint94OracleInventory())->collect(
            $this->adodb,
            $this->profile,
            $this->configuration['anos']
        );
        $expectedUasgs = [];
        $scope = new Sprint94OracleScope();
        foreach ($this->configuration['anos'] as $year) {
            $expectedUasgs[(string) $year] = $this->inventoryStep(
                "ORACLE_ESCOPO_{$year}_INCONCLUSIVO",
                fn (): array => $scope->expectedUasgs($oracle, $this->profile, $year)
            );
        }

        $transport = new Sprint94PncpReadOnlyTransport(
            $this->transport ?? new TransporteCurlPncp(
                $this->profile->getConfiguracaoTransporte(),
                new ExecutorCurlNativoPncp()
            )
        );
        $authentication = $this->inventoryStep(
            'PNCP_AUTENTICACAO_INCONCLUSIVA',
            fn () => (new AutenticadorPncpAdapter(
                $transport,
                $this->profile->getConfiguracaoTransporte(),
                new NullLogger(),
                $this->profile->getLoginPncp(),
                $this->profile->getSenhaPncp()
            ))->autenticar()
        );
        $session = $authentication->getSessao();
        if (
            $authentication->getSituacao() !== SituacaoAutenticacaoPncp::AUTENTICADA
            || !$session instanceof SessaoPncpAutenticada
        ) {
            throw new Sprint94InventoryException(
                'PNCP_AUTENTICACAO_INCONCLUSIVA',
                'A autenticacao PNCP nao foi confirmada para o inventario.'
            );
        }

        $reader = new Sprint94PncpInventoryReader(
            $transport,
            $this->profile->getConfiguracaoTransporte(),
            $this->profile->getConfiguracaoLeitura()
        );
        $cnpj = new Cnpj($this->organizationDocument);
        $pncp = [];
        foreach ($this->configuration['anos'] as $year) {
            $candidates = $this->inventoryStep(
                "PNCP_INVENTARIO_{$year}_INCONCLUSIVO",
                fn (): array => $reader->inventory(
                    $session,
                    $cnpj,
                    $year,
                    $expectedUasgs[(string) $year]
                )
            );
            $pncp[(string) $year] = [
                'candidatos' => $this->inventoryStep(
                    "CLASSIFICACAO_{$year}_INCONCLUSIVA",
                    fn (): array => $this->classify($candidates, $oracle, $year)
                ),
                'uasgs_esperadas_oracle' => $expectedUasgs[(string) $year],
            ];
        }

        return Sprint94Report::create([
            'anos' => $this->configuration['anos'],
            'ambiente' => $this->profile->getAmbiente()->value,
            'classificacao' => 'PRELIMINAR_REQUER_CONFIRMACAO_MANUAL',
            'escritas' => ['logs' => false, 'oracle' => false, 'pncp' => false],
            'etapa' => 'INVENTARIO',
            'identidade_orgao_sha256' => hash('sha256', $cnpj->getValor()),
            'oracle' => $oracle,
            'pncp' => $pncp,
            'resultado' => 'INVENTARIO_SOMENTE_LEITURA_CONCLUIDO',
            'sprint' => '9.4',
        ]);
    }

    private function inventoryStep(string $errorCode, callable $operation): mixed
    {
        try {
            return $operation();
        } catch (Sprint94InventoryException $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw new Sprint94InventoryException(
                $errorCode,
                'Uma etapa do inventario nao foi concluida.',
                $failure
            );
        }
    }

    private function requireStage(string $expected): void
    {
        if (($this->configuration['etapa_autorizada'] ?? null) !== $expected) {
            throw new Sprint94ConfigurationException(
                'ETAPA_NAO_AUTORIZADA',
                "A configuracao nao autoriza a etapa {$expected}."
            );
        }
    }

    /**
     * @param array<int, array{uasg: string, sequencial: int, numeros_item: int[]}> $candidates
     * @param array<string, mixed> $oracle
     * @return array<int, array<string, mixed>>
     */
    private function classify(array $candidates, array $oracle, int $year): array
    {
        $versions = $oracle['exercicios'][(string) $year]['versoes_e_projecao'] ?? [];
        foreach ($candidates as &$candidate) {
            $linked = false;
            foreach ($versions as $version) {
                if (
                    (string) ($version['UASG_ADOTADA'] ?? '') === $candidate['uasg']
                    && (int) ($version['SEQUENCIAL_PUBLICACAO_PNCP'] ?? 0) === $candidate['sequencial']
                ) {
                    $linked = true;
                    break;
                }
            }
            $candidate['classificacao_preliminar'] = $linked
                ? 'VINCULO_LOCAL_EXISTENTE'
                : 'DESCONHECIDO_PENDENTE_CONFIRMACAO';
        }
        unset($candidate);

        return $candidates;
    }
}
