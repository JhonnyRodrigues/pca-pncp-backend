<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Oracle\Plano;

use DateTimeImmutable;
use DateTimeZone;
use Semae\Pca\Domain\Entities\Demanda;
use Semae\Pca\Domain\Entities\Plano;
use Semae\Pca\Domain\Entities\UnidadeOrcamentaria;
use Semae\Pca\Domain\Enums\SituacaoDemanda;
use Semae\Pca\Domain\ValueObjects\DecimalExato;
use Semae\Pca\Domain\ValueObjects\IdentidadePlano;
use Semae\Pca\Domain\ValueObjects\IdentidadeUnidadeOrcamentaria;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Uasg;
use Throwable;
use UnexpectedValueException;

final class MapeadorDadosPlanoOracle
{
    private const CATALOGO_OUTROS = 2;

    public function __construct(private readonly DateTimeZone $fusoOperacional) {}

    /** @param array<string, mixed> $linha */
    public function mapearUnidadeOrcamentaria(array $linha): UnidadeOrcamentaria
    {
        try {
            return new UnidadeOrcamentaria(
                new IdentidadeUnidadeOrcamentaria(
                    $this->textoObrigatorio($this->valor($linha, 'IDENTIDADE_UO'))
                ),
                new Lotacao(
                    $this->inteiroPositivo($this->valor($linha, 'ID_LOTACAO')),
                    $this->textoObrigatorio($this->valor($linha, 'DESCRICAO_LOTACAO'))
                ),
                new Uasg(
                    $this->textoObrigatorio($this->valor($linha, 'CODIGO_UASG')),
                    $this->textoObrigatorio($this->valor($linha, 'DESCRICAO_UASG'))
                ),
                $this->textoObrigatorio($this->valor($linha, 'DESCRICAO_UO'))
            );
        } catch (Throwable) {
            throw new DadosPlanoOracleInvalidosException('Os dados Oracle da Unidade Orçamentária são inválidos.');
        }
    }

    /** @param array<string, mixed> $linha */
    public function mapearDemanda(array $linha, IdentidadePlano $identidade): Demanda
    {
        try {
            return new Demanda(
                $this->inteiroPositivo($this->valor($linha, 'ID_DEMANDA')),
                $this->situacaoDemanda($this->valor($linha, 'SITUACAO')),
                $this->inteiroPositivo($this->valor($linha, 'ID_UNIDADE_REQUISITANTE')),
                $identidade->getUnidadeOrcamentaria(),
                $identidade->getAnoExecucao(),
                $this->dataCalendario($this->valor($linha, 'DATA_DESEJADA')),
                $this->inteiroPositivo($this->valor($linha, 'CATEGORIA')),
                $this->textoObrigatorio($this->valor($linha, 'DESCRICAO_DEMANDA')),
                $this->textoObrigatorio($this->valor($linha, 'UNIDADE_MEDIDA')),
                $this->inteiroPositivo($this->valor($linha, 'QUANTIDADE')),
                $this->decimalExato($this->valor($linha, 'VALOR_TOTAL')),
                $this->decimalExato($this->valor($linha, 'VALOR_ORCAMENTO_EXERCICIO')),
                self::CATALOGO_OUTROS,
                $this->inteiroPositivo($this->valor($linha, 'CLASSIFICACAO_CATALOGO')),
                $this->textoObrigatorio($this->valor($linha, 'CODIGO_ITEM')),
                $this->textoObrigatorio($this->valor($linha, 'GRUPO')),
                $this->textoObrigatorio($this->valor($linha, 'DESCRICAO_GRUPO')),
                $this->textoObrigatorio($this->valor($linha, 'ORIGEM'))
            );
        } catch (Throwable) {
            throw new DadosPlanoOracleInvalidosException('Os dados Oracle da Demanda são inválidos.');
        }
    }

    /**
     * @param array<string, mixed> $linha
     * @param Demanda[] $demandas
     */
    public function mapearPlano(
        array $linha,
        IdentidadePlano $identidade,
        array $demandas
    ): Plano {
        try {
            return new Plano(
                $identidade,
                $this->inteiroPositivo($this->valor($linha, 'VERSAO')),
                $this->instante($this->valor($linha, 'DATA_HOMOLOGACAO')),
                $demandas,
                $this->mapearDataConfirmacao($linha)
            );
        } catch (Throwable) {
            throw new DadosPlanoOracleInvalidosException('Os dados Oracle do Plano são inválidos.');
        }
    }

    /** @param array<string, mixed> $linha */
    public function mapearDataConfirmacao(array $linha): ?DateTimeImmutable
    {
        try {
            $confirmacao = $this->valor($linha, 'DATA_CONFIRMACAO');

            return $confirmacao === null ? null : $this->instante($confirmacao);
        } catch (Throwable) {
            throw new DadosPlanoOracleInvalidosException(
                'Os dados Oracle da confirmação do Plano são inválidos.'
            );
        }
    }

    /** @param array<string, mixed> $linha */
    private function valor(array $linha, string $alias): mixed
    {
        if (!array_key_exists($alias, $linha)) {
            throw new UnexpectedValueException('Alias Oracle obrigatório ausente.');
        }

        return $linha[$alias];
    }

    private function inteiroPositivo(mixed $valor): int
    {
        if (is_int($valor)) {
            if ($valor <= 0) {
                throw new UnexpectedValueException('Inteiro Oracle inválido.');
            }

            return $valor;
        }

        if (!is_string($valor)) {
            throw new UnexpectedValueException('Inteiro Oracle inválido.');
        }

        $valor = trim($valor);

        if (preg_match('/^[1-9][0-9]*$/D', $valor) !== 1) {
            throw new UnexpectedValueException('Inteiro Oracle inválido.');
        }

        $inteiro = filter_var(
            $valor,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if (!is_int($inteiro)) {
            throw new UnexpectedValueException('Inteiro Oracle inválido.');
        }

        return $inteiro;
    }

    private function textoObrigatorio(mixed $valor): string
    {
        if (!is_string($valor) && !is_int($valor)) {
            throw new UnexpectedValueException('Texto Oracle inválido.');
        }

        $texto = trim((string) $valor);

        if ($texto === '') {
            throw new UnexpectedValueException('Texto Oracle inválido.');
        }

        return $texto;
    }

    private function situacaoDemanda(mixed $valor): SituacaoDemanda
    {
        $situacao = SituacaoDemanda::tryFrom($this->inteiroPositivo($valor));

        if ($situacao === null) {
            throw new UnexpectedValueException('Situação Oracle inválida.');
        }

        return $situacao;
    }

    private function dataCalendario(mixed $valor): DateTimeImmutable
    {
        return $this->dataComMascara($valor, 'Y-m-d');
    }

    private function instante(mixed $valor): DateTimeImmutable
    {
        return $this->dataComMascara($valor, 'Y-m-d H:i:s');
    }

    private function dataComMascara(mixed $valor, string $mascara): DateTimeImmutable
    {
        if (!is_string($valor)) {
            throw new UnexpectedValueException('Data Oracle inválida.');
        }

        $valor = trim($valor);
        $data = DateTimeImmutable::createFromFormat(
            '!' . $mascara,
            $valor,
            $this->fusoOperacional
        );
        $erros = DateTimeImmutable::getLastErrors();

        if (
            $data === false
            || ($erros !== false && ($erros['warning_count'] > 0 || $erros['error_count'] > 0))
            || $data->format($mascara) !== $valor
        ) {
            throw new UnexpectedValueException('Data Oracle inválida.');
        }

        return $data;
    }

    private function decimalExato(mixed $valor): DecimalExato
    {
        if (!is_string($valor)) {
            throw new UnexpectedValueException('Decimal Oracle inválido.');
        }

        $valor = trim($valor);

        if (preg_match('/^-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?$/D', $valor) !== 1) {
            throw new UnexpectedValueException('Decimal Oracle inválido.');
        }

        return new DecimalExato($valor);
    }
}
