<?php

declare(strict_types=1);

namespace Semae\Pca\Application\UseCases\PublicarPca;

use Semae\Pca\Application\Contracts\ProcessadorUnidade;
use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Application\Enums\ModoPublicacao;
use Semae\Pca\Application\Results\ResultadoProcessamentoUnidade;
use Semae\Pca\Application\Results\ResultadoVarreduraUnidades;
use Semae\Pca\Application\ValueObjects\ContextoUsuario;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Throwable;

final class CoordenadorVarreduraUnidades
{
    public function __construct(private readonly ProcessadorUnidade $processador) {}

    /** @param mixed[] $unidades */
    public function processar(
        ContextoUsuario $contextoUsuario,
        SessaoPncp $sessao,
        Cnpj $cnpj,
        array $unidades
    ): ResultadoVarreduraUnidades {
        $falhaTopologia = $this->validarTopologia($unidades);

        if ($falhaTopologia !== null) {
            return ResultadoVarreduraUnidades::interrompida([], $falhaTopologia, 0);
        }

        $resultados = [];
        $quantidadeVisitadas = 0;

        foreach ($unidades as $unidade) {
            $quantidadeVisitadas++;

            try {
                $desfecho = $this->processador->processar(
                    $contextoUsuario,
                    $sessao,
                    $cnpj,
                    $unidade
                );
            } catch (Throwable) {
                return ResultadoVarreduraUnidades::interrompida(
                    $resultados,
                    new FalhaSegura(
                        'PROCESSAMENTO_UNIDADE_FALHOU',
                        'Não foi possível processar a unidade externa.'
                    ),
                    $quantidadeVisitadas
                );
            }

            if ($desfecho->getFalhaSistemica() !== null) {
                return ResultadoVarreduraUnidades::interrompida(
                    $resultados,
                    $desfecho->getFalhaSistemica(),
                    $quantidadeVisitadas
                );
            }

            $resultado = $desfecho->getResultadoUnitario();

            if (!$resultado instanceof ResultadoProcessamentoUnidade) {
                return ResultadoVarreduraUnidades::interrompida(
                    $resultados,
                    new FalhaSegura(
                        'DESFECHO_UNIDADE_INCONSISTENTE',
                        'O processamento não forneceu um resultado unitário válido.'
                    ),
                    $quantidadeVisitadas
                );
            }

            if (!$resultado->getUnidadeExterna()->equals($unidade)) {
                return ResultadoVarreduraUnidades::interrompida(
                    $resultados,
                    new FalhaSegura(
                        'RESULTADO_UNIDADE_INCOERENTE',
                        'O resultado não pertence à unidade externa visitada.'
                    ),
                    $quantidadeVisitadas
                );
            }

            $resultados[] = $resultado;
        }

        return ResultadoVarreduraUnidades::concluida($resultados);
    }

    /** @param mixed[] $unidades */
    private function validarTopologia(array $unidades): ?FalhaSegura
    {
        if ($unidades === []) {
            return new FalhaSegura(
                'VARREDURA_SEM_UNIDADES',
                'A varredura exige ao menos uma unidade externa.'
            );
        }

        $primeira = $unidades[0] ?? null;

        if (!$primeira instanceof UnidadeExternaPublicacao) {
            return $this->falhaUnidadesInconsistentes();
        }

        if (
            $primeira->getModo() === ModoPublicacao::CONSOLIDADO
            && count($unidades) !== 1
        ) {
            return new FalhaSegura(
                'VARREDURA_CONSOLIDADA_INCONSISTENTE',
                'A varredura consolidada exige uma única unidade conjunta.'
            );
        }

        $anteriores = [];

        foreach ($unidades as $unidade) {
            if (!$unidade instanceof UnidadeExternaPublicacao) {
                return $this->falhaUnidadesInconsistentes();
            }

            if (
                $unidade->getModo() !== $primeira->getModo()
                || $unidade->getAnoExecucao()->getValor()
                    !== $primeira->getAnoExecucao()->getValor()
            ) {
                return $this->falhaUnidadesInconsistentes();
            }

            foreach ($anteriores as $anterior) {
                if ($anterior->equals($unidade)) {
                    return new FalhaSegura(
                        'VARREDURA_UNIDADE_DUPLICADA',
                        'A varredura não admite unidades externas duplicadas.'
                    );
                }
            }

            $anteriores[] = $unidade;
        }

        return null;
    }

    private function falhaUnidadesInconsistentes(): FalhaSegura
    {
        return new FalhaSegura(
            'VARREDURA_UNIDADES_INCONSISTENTE',
            'As unidades externas da varredura são inconsistentes.'
        );
    }
}
