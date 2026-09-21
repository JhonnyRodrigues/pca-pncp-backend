<?php

declare(strict_types=1);

namespace Semae\Pca\Composition;

use Semae\Pca\Infrastructure\Observabilidade\AcumuladorEventosTecnicos;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Semae\Pca\Presentation\Facades\FachadaPublicarPca;
use Semae\Pca\Presentation\ViewModels\PublicarPcaViewModel;
use Semae\Pca\ResultadoPublicacaoPcaScriptcase;
use Throwable;

final class ComposicaoExecucaoPublicacaoPca
{
    private const AVISO_TRANSACAO_ATIVA =
        'Os eventos técnicos não foram disponibilizados porque o processamento local não foi encerrado.';
    private const AVISO_EXPORTACAO =
        'Os eventos técnicos não puderam ser disponibilizados para registro.';

    public function __construct(
        private readonly FachadaPublicarPca $fachada,
        private readonly AcumuladorEventosTecnicos $eventos,
        private readonly ConexaoAdodbEmprestada $conexao
    ) {}

    public function getFachada(): FachadaPublicarPca
    {
        return $this->fachada;
    }

    public function exportar(
        PublicarPcaViewModel $resultado
    ): ResultadoPublicacaoPcaScriptcase {
        if ($this->conexao->estaEmTransacao()) {
            return new ResultadoPublicacaoPcaScriptcase(
                $resultado,
                [],
                self::AVISO_TRANSACAO_ATIVA
            );
        }

        try {
            $envelopes = $this->eventos
                ->criarLoteParaExportacao()
                ->getEnvelopes();
        } catch (Throwable) {
            return new ResultadoPublicacaoPcaScriptcase(
                $resultado,
                [],
                self::AVISO_EXPORTACAO
            );
        }

        return new ResultadoPublicacaoPcaScriptcase($resultado, $envelopes, null);
    }
}
