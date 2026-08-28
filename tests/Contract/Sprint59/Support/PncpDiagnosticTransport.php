<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint59\Support;

use Semae\Pca\Infrastructure\Pncp\Http\RequisicaoHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\RespostaHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;
use Throwable;

final class PncpDiagnosticTransport implements TransporteHttpPncp
{
    private ?RequisicaoHttpPncp $ultimaRequisicao = null;
    private ?RespostaHttpPncp $ultimaResposta = null;
    private ?Throwable $ultimaFalha = null;

    /**
     * @var array<int, array{
     *     requisicao: RequisicaoHttpPncp,
     *     resposta: RespostaHttpPncp|null,
     *     falha: Throwable|null
     * }>
     */
    private array $historico = [];

    public function __construct(private readonly TransporteHttpPncp $transport)
    {
    }

    public function enviar(RequisicaoHttpPncp $requisicao): RespostaHttpPncp
    {
        $this->ultimaRequisicao = $requisicao;
        $this->ultimaResposta = null;
        $this->ultimaFalha = null;
        $indice = count($this->historico);
        $this->historico[$indice] = [
            'requisicao' => $requisicao,
            'resposta' => null,
            'falha' => null,
        ];

        try {
            $this->ultimaResposta = $this->transport->enviar($requisicao);
            $this->historico[$indice]['resposta'] = $this->ultimaResposta;

            return $this->ultimaResposta;
        } catch (Throwable $failure) {
            $this->ultimaFalha = $failure;
            $this->historico[$indice]['falha'] = $failure;

            throw $failure;
        }
    }

    public function limparHistorico(): void
    {
        $this->historico = [];
        $this->ultimaRequisicao = null;
        $this->ultimaResposta = null;
        $this->ultimaFalha = null;
    }

    /**
     * @return array<int, array{
     *     requisicao: RequisicaoHttpPncp,
     *     resposta: RespostaHttpPncp|null,
     *     falha: Throwable|null
     * }>
     */
    public function getHistorico(): array
    {
        return $this->historico;
    }

    public function getUltimaRequisicao(): ?RequisicaoHttpPncp
    {
        return $this->ultimaRequisicao;
    }

    public function getUltimaResposta(): ?RespostaHttpPncp
    {
        return $this->ultimaResposta;
    }

    public function getUltimaFalha(): ?Throwable
    {
        return $this->ultimaFalha;
    }
}
