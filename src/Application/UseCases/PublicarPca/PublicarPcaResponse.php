<?php

declare(strict_types=1);

namespace Semae\Pca\Application\UseCases\PublicarPca;

class PublicarPcaResponse
{
    /**
     * @param string[] $erros
     * @param ResultadoProcessamentoPlano[] $resultadoProcessamentoPlano
     */
    public function __construct(
        private readonly array $erros,
        private readonly array $resultadoProcessamentoPlano
    ) {}

    /**
     * @return string[]
     */
    public function getErros(): array
    {
        return $this->erros;
    }

    /**
     * @return ResultadoProcessamentoPlano[]
     */
    public function getResultadoProcessamentoPlano(): array
    {
        return $this->resultadoProcessamentoPlano;
    }
}
