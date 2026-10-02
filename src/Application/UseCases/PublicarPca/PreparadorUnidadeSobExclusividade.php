<?php

declare(strict_types=1);

namespace Semae\Pca\Application\UseCases\PublicarPca;

use Semae\Pca\Application\Enums\ModoPublicacao;
use Semae\Pca\Application\Enums\SituacaoConsultaEstadoOperacional;
use Semae\Pca\Application\Enums\SituacaoInicioUnidadeTrabalho;
use Semae\Pca\Application\Ports\EstadoOperacionalPublicacaoPort;
use Semae\Pca\Application\Ports\UnidadeDeTrabalhoPort;
use Semae\Pca\Application\Results\DesfechoProcessamentoUnidade;
use Semae\Pca\Application\Results\ResultadoProcessamentoUnidade;
use Semae\Pca\Application\ValueObjects\ChaveUnidadeTrabalho;
use Semae\Pca\Application\ValueObjects\ContextoUnidadeSobExclusividade;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\Entities\Plano;
use Semae\Pca\Domain\Ports\PlanoRepository;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Throwable;

final class PreparadorUnidadeSobExclusividade
{
    private readonly DesfazedorUnidadeProcessamento $desfazedor;

    public function __construct(
        private readonly UnidadeDeTrabalhoPort $unidadeDeTrabalho,
        private readonly PlanoRepository $planoRepository,
        private readonly EstadoOperacionalPublicacaoPort $estadoOperacional
    ) {
        $this->desfazedor = new DesfazedorUnidadeProcessamento($unidadeDeTrabalho);
    }

    public function preparar(
        UnidadeExternaPublicacao $unidade
    ): ContextoUnidadeSobExclusividade|DesfechoProcessamentoUnidade {
        try {
            $inicio = $this->unidadeDeTrabalho->iniciar(
                ChaveUnidadeTrabalho::unidadeExterna($unidade)
            );
        } catch (Throwable) {
            return DesfechoProcessamentoUnidade::falhaSistemica(new FalhaSegura(
                'INICIO_UNIDADE_FALHOU',
                'Não foi possível iniciar a unidade de processamento.'
            ));
        }

        if ($inicio->getSituacao() === SituacaoInicioUnidadeTrabalho::BLOQUEADA) {
            return $this->bloquearEDesfazer(
                $unidade,
                $this->falhaOuDiagnostico(
                    $inicio->getFalha(),
                    'UNIDADE_EXTERNA_BLOQUEADA',
                    'A unidade externa já está em processamento.'
                )
            );
        }

        if ($inicio->getSituacao() === SituacaoInicioUnidadeTrabalho::FALHA_SISTEMICA) {
            return DesfechoProcessamentoUnidade::falhaSistemica(
                $this->falhaOuDiagnostico(
                    $inicio->getFalha(),
                    'INICIO_UNIDADE_FALHOU',
                    'Não foi possível iniciar a unidade de processamento.'
                )
            );
        }

        try {
            $identidadesRelidas = $this->planoRepository->listarIdentidades(
                $unidade->getAnoExecucao()
            );
        } catch (Throwable) {
            return $this->interromperEDesfazer(
                new FalhaSegura(
                    'RELEITURA_UNIDADE_FALHOU',
                    'Não foi possível reler as identidades da unidade externa.'
                )
            );
        }

        $identidadesOuFalha = $this->selecionarIdentidades($unidade, $identidadesRelidas);

        if ($identidadesOuFalha instanceof FalhaSegura) {
            return $this->bloquearEDesfazer($unidade, $identidadesOuFalha);
        }

        try {
            $planosOuFalha = $this->reconstruirPlanos($identidadesOuFalha);
        } catch (Throwable) {
            return $this->interromperEDesfazer(new FalhaSegura(
                'RECONSTRUCAO_PLANO_FALHOU',
                'Não foi possível reconstruir a fotografia vigente do Plano.'
            ));
        }

        if ($planosOuFalha instanceof FalhaSegura) {
            return $this->bloquearEDesfazer($unidade, $planosOuFalha);
        }

        try {
            $resultadoEstado = $this->estadoOperacional->consultar($unidade);
        } catch (Throwable) {
            return $this->interromperEDesfazer(new FalhaSegura(
                'CONSULTA_ESTADO_OPERACIONAL_FALHOU',
                'Não foi possível consultar o estado operacional da unidade.'
            ));
        }

        if (
            $resultadoEstado->getSituacao()
            === SituacaoConsultaEstadoOperacional::CONFLITO
        ) {
            return $this->bloquearEDesfazer(
                $unidade,
                $this->falhaOuDiagnostico(
                    $resultadoEstado->getFalha(),
                    'ESTADO_OPERACIONAL_CONFLITANTE',
                    'O estado operacional da unidade está conflitante.'
                )
            );
        }

        if (
            $resultadoEstado->getSituacao()
            === SituacaoConsultaEstadoOperacional::FALHA_SISTEMICA
        ) {
            return $this->interromperEDesfazer(
                $this->falhaOuDiagnostico(
                    $resultadoEstado->getFalha(),
                    'CONSULTA_ESTADO_OPERACIONAL_FALHOU',
                    'Não foi possível consultar o estado operacional da unidade.'
                )
            );
        }

        $estado = $resultadoEstado->getEstadoOperacional();

        if ($estado === null) {
            return $this->interromperEDesfazer(new FalhaSegura(
                'ESTADO_OPERACIONAL_INCONSISTENTE',
                'A consulta não forneceu o estado operacional da unidade.'
            ));
        }

        return new ContextoUnidadeSobExclusividade($unidade, $planosOuFalha, $estado);
    }

    /**
     * @param mixed[] $identidadesRelidas
     * @return IdentidadePlano[]|FalhaSegura
     */
    private function selecionarIdentidades(
        UnidadeExternaPublicacao $unidade,
        array $identidadesRelidas
    ): array|FalhaSegura {
        $identidadesPorUo = [];

        foreach ($identidadesRelidas as $identidade) {
            if (!$identidade instanceof IdentidadePlano) {
                return new FalhaSegura(
                    'IDENTIDADE_PLANO_INVALIDA',
                    'A releitura retornou uma identidade de Plano inválida.'
                );
            }

            if (
                $identidade->getAnoExecucao()->getValor()
                !== $unidade->getAnoExecucao()->getValor()
            ) {
                return new FalhaSegura(
                    'EXERCICIO_IDENTIDADE_INCOERENTE',
                    'A identidade relida não pertence ao exercício da unidade.'
                );
            }

            $idUo = $this->obterIdUo($identidade);

            if (isset($identidadesPorUo[$idUo])) {
                return new FalhaSegura(
                    'IDENTIDADE_PLANO_DUPLICADA',
                    'A releitura retornou identidade de Plano duplicada.'
                );
            }

            $identidadesPorUo[$idUo] = $identidade;
        }

        if ($unidade->getModo() === ModoPublicacao::CONSOLIDADO) {
            if ($identidadesPorUo === []) {
                return new FalhaSegura(
                    'IDENTIDADES_CONSOLIDADAS_DESAPARECERAM',
                    'Nenhuma identidade permaneceu na unidade consolidada.'
                );
            }

            return array_values($identidadesPorUo);
        }

        $identidadeEsperada = $unidade->getIdentidadePlano();

        if ($identidadeEsperada === null) {
            return new FalhaSegura(
                'UNIDADE_INDIVIDUAL_INCONSISTENTE',
                'A unidade individual não forneceu sua identidade de Plano.'
            );
        }

        $idUoEsperada = $this->obterIdUo($identidadeEsperada);
        $identidadeRelida = $identidadesPorUo[$idUoEsperada] ?? null;

        if ($identidadeRelida === null) {
            return new FalhaSegura(
                'IDENTIDADE_INDIVIDUAL_DESAPARECEU',
                'A identidade da unidade individual não foi localizada sob exclusividade.'
            );
        }

        if (
            $identidadeRelida->getUnidadeOrcamentaria()->getUasg()->getCodigo()
            !== $unidade->getUasgExterna()->getCodigo()
        ) {
            return new FalhaSegura(
                'UASG_INDIVIDUAL_INCOERENTE',
                'A UASG relida diverge da unidade externa individual.'
            );
        }

        return [$identidadeRelida];
    }

    /**
     * @param IdentidadePlano[] $identidades
     * @return Plano[]|FalhaSegura
     */
    private function reconstruirPlanos(array $identidades): array|FalhaSegura
    {
        $planos = [];

        foreach ($identidades as $identidade) {
            $plano = $this->planoRepository->reconstruirFotografiaVigente($identidade);

            if (!$this->planoCorrespondeAIdentidade($plano, $identidade)) {
                return new FalhaSegura(
                    'PLANO_RECONSTRUIDO_INCOERENTE',
                    'O Plano reconstruído diverge da identidade relida.'
                );
            }

            $planos[] = $plano;
        }

        return $planos;
    }

    private function planoCorrespondeAIdentidade(
        Plano $plano,
        IdentidadePlano $identidade
    ): bool {
        return $plano->getIdentidade()->equals($identidade)
            && $plano->getUnidadeOrcamentaria()->getUasg()->getCodigo()
                === $identidade->getUnidadeOrcamentaria()->getUasg()->getCodigo();
    }

    private function bloquearEDesfazer(
        UnidadeExternaPublicacao $unidade,
        FalhaSegura $falha
    ): DesfechoProcessamentoUnidade {
        return $this->desfazedor->desfazerPreservando(
            DesfechoProcessamentoUnidade::resultadoUnitario(
                ResultadoProcessamentoUnidade::bloqueado(
                    $unidade,
                    null,
                    null,
                    0,
                    0,
                    $falha
                )
            )
        );
    }

    private function interromperEDesfazer(
        FalhaSegura $falha
    ): DesfechoProcessamentoUnidade {
        return $this->desfazedor->desfazerPreservando(
            DesfechoProcessamentoUnidade::falhaSistemica($falha)
        );
    }

    private function obterIdUo(IdentidadePlano $identidade): string
    {
        return $identidade->getUnidadeOrcamentaria()->getIdentidade()->getValor();
    }

    private function falhaOuDiagnostico(
        ?FalhaSegura $falha,
        string $codigo,
        string $mensagem
    ): FalhaSegura {
        return $falha ?? new FalhaSegura($codigo, $mensagem);
    }
}
