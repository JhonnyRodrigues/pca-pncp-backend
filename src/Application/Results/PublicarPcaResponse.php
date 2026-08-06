<?php

declare(strict_types=1);

namespace Semae\Pca\Application\Results;

use InvalidArgumentException;
use Semae\Pca\Application\Enums\SituacaoProcessamentoUnidade;
use Semae\Pca\Application\Enums\SituacaoPublicacaoPca;
use Semae\Pca\Application\ValueObjects\CorrelacaoExecucao;
use Semae\Pca\Application\ValueObjects\FalhaSegura;

final class PublicarPcaResponse
{
    /**
     * @param ResultadoProcessamentoUnidade[] $resultadosUnitarios
     */
    private function __construct(
        private readonly SituacaoPublicacaoPca $situacao,
        private readonly array $resultadosUnitarios,
        private readonly ?FalhaSegura $falhaGlobal,
        private readonly int $quantidadePlanejadas,
        private readonly int $quantidadeVisitadas,
        private readonly int $quantidadeNaoProcessadas,
        private readonly CorrelacaoExecucao $correlacao
    ) {
        $this->validarInvariantes();
    }

    public static function solicitacaoInvalida(
        FalhaSegura $falha,
        CorrelacaoExecucao $correlacao
    ): self {
        return new self(
            SituacaoPublicacaoPca::SOLICITACAO_INVALIDA,
            [],
            $falha,
            0,
            0,
            0,
            $correlacao
        );
    }

    /** @param ResultadoProcessamentoUnidade[] $resultadosUnitarios */
    public static function concluida(
        array $resultadosUnitarios,
        int $quantidadePlanejadas,
        int $quantidadeVisitadas,
        CorrelacaoExecucao $correlacao
    ): self {
        return self::semFalhaGlobal(
            SituacaoPublicacaoPca::CONCLUIDA,
            $resultadosUnitarios,
            $quantidadePlanejadas,
            $quantidadeVisitadas,
            $correlacao
        );
    }

    /** @param ResultadoProcessamentoUnidade[] $resultadosUnitarios */
    public static function concluidaComPendencias(
        array $resultadosUnitarios,
        int $quantidadePlanejadas,
        int $quantidadeVisitadas,
        CorrelacaoExecucao $correlacao
    ): self {
        return self::semFalhaGlobal(
            SituacaoPublicacaoPca::CONCLUIDA_COM_PENDENCIAS,
            $resultadosUnitarios,
            $quantidadePlanejadas,
            $quantidadeVisitadas,
            $correlacao
        );
    }

    /** @param ResultadoProcessamentoUnidade[] $resultadosUnitarios */
    public static function interrompida(
        array $resultadosUnitarios,
        FalhaSegura $falha,
        int $quantidadePlanejadas,
        int $quantidadeVisitadas,
        CorrelacaoExecucao $correlacao
    ): self {
        return new self(
            SituacaoPublicacaoPca::INTERROMPIDA,
            array_values($resultadosUnitarios),
            $falha,
            $quantidadePlanejadas,
            $quantidadeVisitadas,
            $quantidadePlanejadas - $quantidadeVisitadas,
            $correlacao
        );
    }

    public function getSituacao(): SituacaoPublicacaoPca
    {
        return $this->situacao;
    }

    /** @return ResultadoProcessamentoUnidade[] */
    public function getResultadosUnitarios(): array
    {
        return $this->resultadosUnitarios;
    }

    public function getFalhaGlobal(): ?FalhaSegura
    {
        return $this->falhaGlobal;
    }

    public function getQuantidadePlanejadas(): int
    {
        return $this->quantidadePlanejadas;
    }

    public function getQuantidadeVisitadas(): int
    {
        return $this->quantidadeVisitadas;
    }

    public function getQuantidadeNaoProcessadas(): int
    {
        return $this->quantidadeNaoProcessadas;
    }

    public function getCorrelacao(): CorrelacaoExecucao
    {
        return $this->correlacao;
    }

    /** @param ResultadoProcessamentoUnidade[] $resultadosUnitarios */
    private static function semFalhaGlobal(
        SituacaoPublicacaoPca $situacao,
        array $resultadosUnitarios,
        int $quantidadePlanejadas,
        int $quantidadeVisitadas,
        CorrelacaoExecucao $correlacao
    ): self {
        return new self(
            $situacao,
            array_values($resultadosUnitarios),
            null,
            $quantidadePlanejadas,
            $quantidadeVisitadas,
            $quantidadePlanejadas - $quantidadeVisitadas,
            $correlacao
        );
    }

    private function validarInvariantes(): void
    {
        if (
            $this->quantidadePlanejadas < 0
            || $this->quantidadeVisitadas < 0
            || $this->quantidadeNaoProcessadas < 0
            || $this->quantidadeVisitadas > $this->quantidadePlanejadas
            || count($this->resultadosUnitarios) > $this->quantidadeVisitadas
            || $this->quantidadeNaoProcessadas
                !== $this->quantidadePlanejadas - $this->quantidadeVisitadas
        ) {
            throw new InvalidArgumentException('Contagens globais da publicação são incoerentes.');
        }

        $this->validarResultadosSemDuplicidade();

        match ($this->situacao) {
            SituacaoPublicacaoPca::SOLICITACAO_INVALIDA => $this->validarSolicitacaoInvalida(),
            SituacaoPublicacaoPca::CONCLUIDA => $this->validarConcluida(),
            SituacaoPublicacaoPca::CONCLUIDA_COM_PENDENCIAS =>
                $this->validarConcluidaComPendencias(),
            SituacaoPublicacaoPca::INTERROMPIDA => $this->validarInterrompida(),
        };
    }

    private function validarSolicitacaoInvalida(): void
    {
        if (
            $this->falhaGlobal === null
            || $this->resultadosUnitarios !== []
            || $this->quantidadePlanejadas !== 0
            || $this->quantidadeVisitadas !== 0
            || $this->quantidadeNaoProcessadas !== 0
        ) {
            throw new InvalidArgumentException('SOLICITACAO_INVALIDA possui dados incoerentes.');
        }
    }

    private function validarConcluida(): void
    {
        if ($this->falhaGlobal !== null || !$this->varreduraFoiCompletamenteRepresentada()) {
            throw new InvalidArgumentException('CONCLUIDA exige varredura integral sem falha global.');
        }

        foreach ($this->resultadosUnitarios as $resultado) {
            if (
                !in_array(
                    $resultado->getSituacao(),
                    [
                        SituacaoProcessamentoUnidade::CONCLUIDO,
                        SituacaoProcessamentoUnidade::SEM_ALTERACAO,
                    ],
                    true
                )
            ) {
                throw new InvalidArgumentException(
                    'CONCLUIDA admite apenas resultados unitários sem pendências.'
                );
            }
        }
    }

    private function validarConcluidaComPendencias(): void
    {
        if ($this->falhaGlobal !== null || !$this->varreduraFoiCompletamenteRepresentada()) {
            throw new InvalidArgumentException(
                'CONCLUIDA_COM_PENDENCIAS exige varredura integral sem falha global.'
            );
        }

        foreach ($this->resultadosUnitarios as $resultado) {
            if (
                in_array(
                    $resultado->getSituacao(),
                    [
                        SituacaoProcessamentoUnidade::FALHA_RECONCILIAVEL,
                        SituacaoProcessamentoUnidade::BLOQUEADO,
                    ],
                    true
                )
            ) {
                return;
            }
        }

        throw new InvalidArgumentException(
            'CONCLUIDA_COM_PENDENCIAS exige ao menos um resultado pendente.'
        );
    }

    private function validarInterrompida(): void
    {
        if ($this->falhaGlobal === null) {
            throw new InvalidArgumentException('INTERROMPIDA exige falha global segura.');
        }
    }

    private function varreduraFoiCompletamenteRepresentada(): bool
    {
        return $this->quantidadePlanejadas === $this->quantidadeVisitadas
            && count($this->resultadosUnitarios) === $this->quantidadeVisitadas;
    }

    private function validarResultadosSemDuplicidade(): void
    {
        $unidades = [];

        foreach ($this->resultadosUnitarios as $resultado) {
            if (!$resultado instanceof ResultadoProcessamentoUnidade) {
                throw new InvalidArgumentException('Resultado unitário global inválido.');
            }

            foreach ($unidades as $unidade) {
                if ($unidade->equals($resultado->getUnidadeExterna())) {
                    throw new InvalidArgumentException(
                        'Response não admite resultados de unidades duplicadas.'
                    );
                }
            }

            $unidades[] = $resultado->getUnidadeExterna();
        }
    }
}
