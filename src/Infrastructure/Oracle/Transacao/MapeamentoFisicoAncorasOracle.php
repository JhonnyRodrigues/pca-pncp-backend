<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Oracle\Transacao;

use InvalidArgumentException;

final class MapeamentoFisicoAncorasOracle
{
    private readonly string $schema;
    private readonly string $tabela;
    private readonly string $colunaEscopo;
    private readonly string $colunaAnoExecucao;
    private readonly string $colunaIdentificadorAlvo;
    private readonly string $identificadorAlvoEstrategiaAnual;

    public function __construct(
        string $schema,
        string $tabela,
        string $colunaEscopo,
        string $colunaAnoExecucao,
        string $colunaIdentificadorAlvo,
        string $identificadorAlvoEstrategiaAnual
    ) {
        $this->schema = self::normalizarIdentificador($schema, 'schema');
        $this->tabela = self::normalizarIdentificador($tabela, 'tabela de âncoras');
        $this->colunaEscopo = self::normalizarIdentificador(
            $colunaEscopo,
            'coluna de escopo'
        );
        $this->colunaAnoExecucao = self::normalizarIdentificador(
            $colunaAnoExecucao,
            'coluna de exercício'
        );
        $this->colunaIdentificadorAlvo = self::normalizarIdentificador(
            $colunaIdentificadorAlvo,
            'coluna de identificador-alvo'
        );

        $identificadorAlvoEstrategiaAnual = trim($identificadorAlvoEstrategiaAnual);

        if ($identificadorAlvoEstrategiaAnual === '') {
            throw new InvalidArgumentException(
                'O identificador-alvo da estratégia anual é obrigatório.'
            );
        }

        $this->identificadorAlvoEstrategiaAnual = $identificadorAlvoEstrategiaAnual;
    }

    public function getTabelaQualificada(): string
    {
        return $this->schema . '.' . $this->tabela;
    }

    public function getColunaEscopo(): string
    {
        return $this->colunaEscopo;
    }

    public function getColunaAnoExecucao(): string
    {
        return $this->colunaAnoExecucao;
    }

    public function getColunaIdentificadorAlvo(): string
    {
        return $this->colunaIdentificadorAlvo;
    }

    public function getIdentificadorAlvoEstrategiaAnual(): string
    {
        return $this->identificadorAlvoEstrategiaAnual;
    }

    private static function normalizarIdentificador(string $valor, string $campo): string
    {
        $valor = trim($valor);

        if (!preg_match('/^[A-Za-z][A-Za-z0-9_$#]*$/D', $valor)) {
            throw new InvalidArgumentException(
                "O mapeamento da {$campo} contém um identificador Oracle inválido."
            );
        }

        return strtoupper($valor);
    }
}
