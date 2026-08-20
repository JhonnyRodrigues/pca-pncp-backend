<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Oracle\Adodb;

use InvalidArgumentException;
use LogicException;
use Throwable;

final class ConexaoAdodbEmprestada
{
    private const METODOS_OBRIGATORIOS = [
        'BeginTrans',
        'CommitTrans',
        'RollbackTrans',
        'Execute',
        'GetAll',
        'Param',
        'ErrorNo',
        'ErrorMsg',
    ];

    private bool $emTransacao = false;

    public function __construct(private readonly object $adodb)
    {
        foreach (self::METODOS_OBRIGATORIOS as $metodo) {
            if (!method_exists($this->adodb, $metodo)) {
                throw new InvalidArgumentException(
                    "A conexão ADOdb emprestada não oferece o método obrigatório {$metodo}."
                );
            }
        }
    }

    public function iniciarTransacao(): void
    {
        if ($this->emTransacao) {
            throw new LogicException('Já existe uma transação Oracle ativa.');
        }

        try {
            $iniciada = $this->adodb->BeginTrans();
        } catch (Throwable) {
            throw $this->falha('Falha ao iniciar transação Oracle.');
        }

        if ($iniciada === false) {
            throw $this->falha('Falha ao iniciar transação Oracle.');
        }

        $this->emTransacao = true;
    }

    public function confirmarTransacao(): void
    {
        $this->exigirTransacaoAtiva();

        try {
            $confirmada = $this->adodb->CommitTrans();
        } catch (Throwable) {
            throw $this->falha('Falha ao confirmar transação Oracle.');
        }

        if ($confirmada === false) {
            throw $this->falha('Falha ao confirmar transação Oracle.');
        }

        $this->emTransacao = false;
    }

    public function desfazerTransacao(): void
    {
        $this->exigirTransacaoAtiva();

        try {
            $desfeita = $this->adodb->RollbackTrans();
        } catch (Throwable) {
            throw $this->falha('Falha ao desfazer transação Oracle.');
        }

        if ($desfeita === false) {
            throw $this->falha('Falha ao desfazer transação Oracle.');
        }

        $this->emTransacao = false;
    }

    public function estaEmTransacao(): bool
    {
        return $this->emTransacao;
    }

    /** @param array<int|string, mixed> $parametros */
    public function executar(string $sql, array $parametros = []): void
    {
        [$sqlPreparado, $parametrosPreparados] = $this->prepararBinds($sql, $parametros);

        try {
            $resultado = $this->adodb->Execute($sqlPreparado, $parametrosPreparados);
        } catch (Throwable) {
            throw $this->falha('Falha ao executar comando Oracle.');
        }

        if ($resultado === false) {
            throw $this->falha('Falha ao executar comando Oracle.');
        }
    }

    /**
     * @param array<int|string, mixed> $parametros
     * @return array<int, array<string, mixed>>
     */
    public function buscarTodos(string $sql, array $parametros = []): array
    {
        [$sqlPreparado, $parametrosPreparados] = $this->prepararBinds($sql, $parametros);

        try {
            $linhas = $this->adodb->GetAll($sqlPreparado, $parametrosPreparados);
        } catch (Throwable) {
            throw $this->falha('Falha ao consultar dados no Oracle.');
        }

        if (!is_array($linhas)) {
            throw $this->falha('Falha ao consultar dados no Oracle.');
        }

        foreach ($linhas as $linha) {
            if (!is_array($linha)) {
                throw new FalhaOracleException('Consulta Oracle retornou dados em formato inválido.', null);
            }
        }

        /** @var array<int, array<string, mixed>> $linhas */
        return array_values($linhas);
    }

    private function exigirTransacaoAtiva(): void
    {
        if (!$this->emTransacao) {
            throw new LogicException('Não existe transação Oracle ativa.');
        }
    }

    /**
     * @param array<int|string, mixed> $parametros
     * @return array{string, array<string, mixed>}
     */
    private function prepararBinds(string $sql, array $parametros): array
    {
        $quantidadePlaceholders = substr_count($sql, '?');

        if ($quantidadePlaceholders !== count($parametros)) {
            throw new LogicException('A quantidade de parâmetros diverge dos placeholders Oracle.');
        }

        if ($quantidadePlaceholders === 0) {
            return [$sql, []];
        }

        try {
            $this->adodb->Param(false);
            $parametrosNomeados = [];

            foreach (array_values($parametros) as $indice => $valor) {
                $nome = 'param_' . $indice;
                $placeholder = $this->adodb->Param($nome);

                if (!is_string($placeholder) || !preg_match('/^:[A-Za-z][A-Za-z0-9_]*$/D', $placeholder)) {
                    throw new LogicException('ADOdb não gerou bind nomeado compatível com Oracle.');
                }

                $posicao = strpos($sql, '?');

                if ($posicao === false) {
                    throw new LogicException('Placeholder Oracle desapareceu durante a preparação.');
                }

                $sql = substr_replace($sql, $placeholder, $posicao, 1);
                $parametrosNomeados[$nome] = $valor;
            }
        } catch (LogicException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw $this->falha('Falha ao preparar binds Oracle.');
        }

        return [$sql, $parametrosNomeados];
    }

    private function falha(string $mensagemSegura): FalhaOracleException
    {
        return new FalhaOracleException($mensagemSegura, $this->codigoOracle());
    }

    private function codigoOracle(): ?string
    {
        try {
            $numero = $this->adodb->ErrorNo();

            if (
                is_int($numero) || (is_string($numero)
                && ctype_digit(ltrim($numero, '-')))
            ) {
                $numeroAbsoluto = abs((int) $numero);

                if ($numeroAbsoluto > 0) {
                    return 'ORA-' . str_pad((string) $numeroAbsoluto, 5, '0', STR_PAD_LEFT);
                }
            }
        } catch (Throwable) {
        }

        try {
            $mensagem = $this->adodb->ErrorMsg();

            if (
                is_string($mensagem)
                && preg_match('/ORA-[0-9]{5}/', $mensagem, $resultado) === 1
            ) {
                return $resultado[0];
            }
        } catch (Throwable) {
        }

        return null;
    }
}
