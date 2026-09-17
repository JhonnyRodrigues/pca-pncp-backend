<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Pncp\Serializacao;

use Semae\Pca\Application\Commands\ComandoExcluirItensPca;
use Semae\Pca\Application\Commands\ComandoExcluirPca;
use Semae\Pca\Application\Commands\ComandoInserirItensPca;
use Semae\Pca\Application\Commands\ComandoPublicarPca;
use Semae\Pca\Domain\Entities\Demanda;

final class SerializadorPayloadPncp
{
    private const NUMERO_ITEM_MAXIMO = 2_147_483_647;

    private readonly CodificadorJsonExato $codificador;
    private readonly ConversorDecimalPncp $conversorDecimal;

    public function __construct()
    {
        $this->codificador = new CodificadorJsonExato();
        $this->conversorDecimal = new ConversorDecimalPncp();
    }

    public function serializarPublicacao(ComandoPublicarPca $comando): string
    {
        $unidadeExterna = $comando->getUnidadeExterna();

        return $this->codificador->codificar([
            'codigoUnidade' => $this->validarTexto(
                $unidadeExterna->getUasgExterna()->getCodigo(),
                20
            ),
            'anoPca' => $unidadeExterna->getAnoExecucao()->getValor(),
            'itensPlano' => $this->projetarDemandas($comando->getDemandas()),
        ]);
    }

    public function serializarInclusaoItens(ComandoInserirItensPca $comando): string
    {
        return $this->codificador->codificar(
            $this->projetarDemandas($comando->getDemandas())
        );
    }

    public function serializarExclusaoItens(ComandoExcluirItensPca $comando): string
    {
        $idsDemandas = $comando->getIdsDemandas();

        foreach ($idsDemandas as $idDemanda) {
            $this->validarNumeroItem($idDemanda);
        }

        sort($idsDemandas, SORT_NUMERIC);

        return $this->codificador->codificar([
            'justificativa' => $this->validarTexto($comando->getJustificativa(), 255),
            'listaNumerosItens' => $idsDemandas,
        ]);
    }

    public function serializarExclusaoPca(ComandoExcluirPca $comando): string
    {
        return $this->codificador->codificar([
            'justificativa' => $this->validarTexto($comando->getJustificativa(), 255),
        ]);
    }

    /**
     * @param Demanda[] $demandas
     * @return array<int, array<string, mixed>>
     */
    private function projetarDemandas(array $demandas): array
    {
        usort(
            $demandas,
            static fn (Demanda $primeira, Demanda $segunda): int =>
                $primeira->getIdDemanda() <=> $segunda->getIdDemanda()
        );

        $itens = [];
        $ultimoNumeroItem = null;

        foreach ($demandas as $demanda) {
            $numeroItem = $this->validarNumeroItem($demanda->getIdDemanda());

            if ($numeroItem === $ultimoNumeroItem) {
                throw new FalhaSerializacaoPncp();
            }

            $itens[] = $this->projetarItem($demanda);
            $ultimoNumeroItem = $numeroItem;
        }

        return $itens;
    }

    private function validarNumeroItem(int $numeroItem): int
    {
        if ($numeroItem <= 0 || $numeroItem > self::NUMERO_ITEM_MAXIMO) {
            throw new FalhaSerializacaoPncp();
        }

        return $numeroItem;
    }

    /** @return array<string, mixed> */
    private function projetarItem(Demanda $demanda): array
    {
        return [
            'numeroItem' => $demanda->getIdDemanda(),
            'categoriaItemPca' => $demanda->getCategoria(),
            'catalogo' => $demanda->getCatalogo(),
            'classificacaoCatalogo' => (string) $demanda->getClassificacaoCatalogo(),
            'classificacaoSuperiorCodigo' => $this->validarTexto(
                $demanda->getGrupo(),
                100
            ),
            'classificacaoSuperiorNome' => $this->validarTexto(
                $demanda->getDescricaoGrupo(),
                255
            ),
            'codigoItem' => $this->validarTexto($demanda->getCodigoItem(), 100),
            'descricao' => $this->validarTexto($demanda->getDescricao(), 2048),
            'unidadeFornecimento' => $this->validarTexto(
                $demanda->getUnidadeMedida(),
                255
            ),
            'quantidade' => $this->conversorDecimal->converterQuantidade(
                $demanda->getQuantidade()
            ),
            'valorUnitario' => $this->conversorDecimal->calcularValorUnitario(
                $demanda->getValorTotal(),
                $demanda->getQuantidade()
            ),
            'valorTotal' => $this->conversorDecimal->converterValor(
                $demanda->getValorTotal()
            ),
            'valorOrcamentoExercicio' => $this->conversorDecimal->converterValor(
                $demanda->getValorOrcamentoExercicio()
            ),
            'dataDesejada' => $demanda->getDataDesejada()->format('Y-m-d'),
        ];
    }

    private function validarTexto(string $texto, int $limite): string
    {
        $correspondencias = [];
        $quantidadeCaracteres = preg_match_all('/./us', $texto, $correspondencias);

        if ($quantidadeCaracteres === false || $quantidadeCaracteres > $limite) {
            throw new FalhaSerializacaoPncp();
        }

        return $texto;
    }
}
