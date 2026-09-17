<?php

declare(strict_types=1);

namespace Tests\Integration\Composition\Support;

use RuntimeException;

final class AdodbControlado
{
    /** @var array<int, array{tipo: string, linhas: array<int, array<string, mixed>>}> */
    private array $filaConsultas = [];

    /** @var string[] */
    private array $tiposConsultados = [];

    /** @var array<int, array{tipo: string, sql: string, parametros: array<int|string, mixed>}> */
    private array $escritas = [];

    public int $inicios = 0;
    public int $commits = 0;
    public int $rollbacks = 0;
    public int $fechamentos = 0;

    public function __construct(private readonly RegistroCronologico $eventos) {}

    /** @param array<int, array<string, mixed>> $linhas */
    public function programarConsulta(string $tipo, array $linhas): void
    {
        $this->filaConsultas[] = ['tipo' => $tipo, 'linhas' => $linhas];
    }

    public function BeginTrans(): bool
    {
        $this->inicios++;
        $this->eventos->registrar('oracle:begin');

        return true;
    }

    public function CommitTrans(): bool
    {
        $this->commits++;
        $this->eventos->registrar('oracle:commit');

        return true;
    }

    public function RollbackTrans(): bool
    {
        $this->rollbacks++;
        $this->eventos->registrar('oracle:rollback');

        return true;
    }

    /** @param array<int|string, mixed> $parametros */
    public function Execute(string $sql, array $parametros = []): bool
    {
        $tipo = $this->classificarEscrita($sql);
        $this->escritas[] = compact('tipo', 'sql', 'parametros');
        $this->eventos->registrar('oracle:write:' . $tipo);

        return true;
    }

    /**
     * @param array<int|string, mixed> $parametros
     * @return array<int, array<string, mixed>>
     */
    public function GetAll(string $sql, array $parametros = []): array
    {
        $tipo = $this->classificarConsulta($sql);
        $programada = array_shift($this->filaConsultas);

        if ($programada === null || $programada['tipo'] !== $tipo) {
            $esperada = $programada['tipo'] ?? 'nenhuma';

            throw new RuntimeException(
                "Consulta Oracle inesperada: recebida {$tipo}, esperada {$esperada}."
            );
        }

        $this->tiposConsultados[] = $tipo;
        $this->eventos->registrar('oracle:query:' . $tipo);

        return $programada['linhas'];
    }

    public function Param(false|string $nome): string
    {
        return $nome === false ? '' : ':' . $nome;
    }

    public function ErrorNo(): int
    {
        return 0;
    }

    public function ErrorMsg(): string
    {
        return '';
    }

    public function Close(): bool
    {
        $this->fechamentos++;
        $this->eventos->registrar('oracle:close');

        return true;
    }

    public function quantidadeLocks(): int
    {
        return count(array_filter(
            $this->tiposConsultados,
            static fn (string $tipo): bool => $tipo === 'lock'
        ));
    }

    public function quantidadeEscritas(string $tipo): int
    {
        return count(array_filter(
            $this->escritas,
            static fn (array $escrita): bool => $escrita['tipo'] === $tipo
        ));
    }

    /** @return array<int, array<int|string, mixed>> */
    public function parametrosEscritas(string $tipo): array
    {
        return array_values(array_map(
            static fn (array $escrita): array => $escrita['parametros'],
            array_filter(
                $this->escritas,
                static fn (array $escrita): bool => $escrita['tipo'] === $tipo
            )
        ));
    }

    /** @return string[] */
    public function respostasPendentes(): array
    {
        return array_column($this->filaConsultas, 'tipo');
    }

    private function classificarEscrita(string $sql): string
    {
        return match (true) {
            str_contains($sql, 'PAC_CONTROLES_CONCORRENCIA') => 'provisionar_controle',
            str_contains($sql, 'DATA_PUBLICACAO') => 'confirmar_plano',
            str_contains($sql, 'PAC_HISTORICOS') => 'auditoria',
            str_contains($sql, 'ESTADO_PUBLICACAO') => 'estado_operacional',
            str_contains($sql, 'ESTRATEGIA_ADOTADA_PUBLICACAO') => 'adotar_estrategia',
            default => throw new RuntimeException('Escrita Oracle nÃ£o programada.'),
        };
    }

    private function classificarConsulta(string $sql): string
    {
        return match (true) {
            str_contains($sql, 'RH_FUNCIONARIOS f') => 'contexto_representante',
            str_contains($sql, 'RH_FUNCIONARIOS_LOTACOES fl') => 'contexto_lotacao',
            str_contains($sql, 'FOR UPDATE NOWAIT') => 'lock',
            str_contains($sql, 'CHAVE_CONFIGURACAO') => 'estrategia_corrente',
            str_contains($sql, 'SEQUENCIAL_PUBLICACAO_PNCP AS SEQUENCIAL_PUBLICACAO_PNCP')
                => 'estado_operacional',
            str_contains($sql, 'ESTRATEGIA_ADOTADA_PUBLICACAO AS ESTRATEGIA_ADOTADA')
                => 'estrategia_adotada',
            str_contains($sql, 'BASE.CHAVE_UO_PLANO') => 'identidades',
            str_contains($sql, 'AS CODIGO_UASG') => 'uasgs_individuais',
            str_contains($sql, 'AS VERSAO') => 'plano_vigente',
            str_contains($sql, 'AS ID_DEMANDA') => 'demandas_vigentes',
            str_contains($sql, 'AS DATA_CONFIRMACAO') => 'confirmacao_plano',
            default => throw new RuntimeException('Consulta Oracle nÃ£o programada.'),
        };
    }
}
