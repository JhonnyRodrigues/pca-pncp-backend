<?php

declare(strict_types=1);

namespace Semae\Pca\Application\UseCases\PublicarPca;

use Semae\Pca\Domain\Entities\Plano;
use Semae\Pca\Domain\Enums\DecisaoProcessamentoPlano;
use Semae\Pca\Domain\Ports\PlanoRepository;

class ProcessarPublicacaoPcaUseCase
{
    public function __construct(
        private readonly PublicarPcaValidator $validator,
        private readonly PlanoRepository $planoRepository
    ) {}

    /**
     * @return array{
     *     erros: string[],
     *     processamentos: array<int, array{
     *         plano: Plano,
     *         decisao: DecisaoProcessamentoPlano
     *     }>
     * }
     */
    public function executar(PublicarPcaRequest $request): array
    {
        $resultadoValidacao = $this->validator->validarRequest($request);

        if (!$resultadoValidacao->isValido()) {
            return [
                'erros' => $resultadoValidacao->getErros(),
                'processamentos' => [],
            ];
        }

        $planos = $this->planoRepository->obterPlanosHomologadosParaProcessamento($request->getAnoExecucao());
        $processamentos = [];

        foreach ($planos as $plano) {
            $processamentos[] = [
                'plano' => $plano,
                'decisao' => $plano->decidirProcessamento(),
            ];
        }

        return [
            'erros' => [],
            'processamentos' => $processamentos,
        ];
    }
}
