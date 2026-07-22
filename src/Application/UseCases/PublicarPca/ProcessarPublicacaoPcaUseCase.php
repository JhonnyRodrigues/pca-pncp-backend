<?php

declare(strict_types=1);

namespace Semae\Pca\Application\UseCases\PublicarPca;

use Semae\Pca\Domain\Ports\PlanoRepository;

class ProcessarPublicacaoPcaUseCase
{
    public function __construct(
        private readonly PublicarPcaValidator $validator,
        private readonly PlanoRepository $planoRepository
    ) {}

    public function processar(PublicarPcaRequest $request): PublicarPcaResponse
    {
        $resultadoValidacao = $this->validator->validarRequest($request);

        if (!$resultadoValidacao->isValido()) {
            return new PublicarPcaResponse($resultadoValidacao->getErros(), []);
        }

        $planos = $this->planoRepository->obterPlanosHomologadosParaProcessamento($request->getAnoExecucao());
        $resultadosProcessamentosPlano = [];

        foreach ($planos as $plano) {
            $resultadosProcessamentosPlano[] = new ResultadoProcessamentoPlano(
                $plano,
                $plano->decidirProcessamento()
            );
        }

        return new PublicarPcaResponse([], $resultadosProcessamentosPlano);
    }
}
