<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Oracle\Observabilidade;

use InvalidArgumentException;
use JsonException;
use LogicException;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Throwable;

final class OracleRegistroEventoTecnicoAdapter
{
    private const TABELA = 'SGI.SEGURANCA_LOG';
    private const ACAO = 'execute';
    private const CRIADOR = 'pca-pncp-backend';

    public function __construct(private readonly ConexaoAdodbEmprestada $conexao) {}

    /** @param array<string, mixed> $evento */
    public function registrar(array $evento, string $login, string $aplicacao): void
    {
        $login = trim($login);
        $aplicacao = trim($aplicacao);
        if ($login === '' || $aplicacao === '') {
            throw new InvalidArgumentException('Login e aplicação são obrigatórios para o log.');
        }

        $json = $this->serializar($evento);
        $marcador = 'PCA_LOG_PENDENTE_' . strtoupper(bin2hex(random_bytes(16)));

        $this->conexao->iniciarTransacao();

        try {
            $this->conexao->executar(
                $this->sqlInsercao(),
                [$login, $aplicacao, self::CRIADOR, self::ACAO, $marcador]
            );
            $linhas = $this->conexao->buscarTodos(
                $this->sqlLocalizacao(),
                [self::ACAO, $marcador, $marcador]
            );
            $id = $this->extrairIdUnico($linhas);
            $this->conexao->atualizarClobPorId(
                self::TABELA,
                'DESCRIPTION',
                'ID',
                $id,
                $json
            );
            $this->conexao->confirmarTransacao();
        } catch (Throwable $falha) {
            $this->desfazerTransacaoAtiva();

            throw $falha;
        }
    }

    /** @param array<string, mixed> $evento */
    private function serializar(array $evento): string
    {
        foreach (['error', 'context', 'metadata'] as $campoMapa) {
            if (($evento[$campoMapa] ?? null) === []) {
                $evento[$campoMapa] = (object) [];
            }
        }

        try {
            return json_encode(
                $evento,
                JSON_THROW_ON_ERROR
                    | JSON_UNESCAPED_SLASHES
                    | JSON_PRESERVE_ZERO_FRACTION
                    | JSON_INVALID_UTF8_SUBSTITUTE
            );
        } catch (JsonException $falha) {
            throw new LogicException('O evento técnico não pôde ser serializado.', 0, $falha);
        }
    }

    /** @param array<int, array<string, mixed>> $linhas */
    private function extrairIdUnico(array $linhas): int
    {
        $id = count($linhas) === 1 ? ($linhas[0]['ID'] ?? null) : null;
        if (
            (!is_int($id) && (!is_string($id) || !ctype_digit($id)))
            || (int) $id <= 0
        ) {
            throw new LogicException('O marcador do log não localizou uma linha única.');
        }

        return (int) $id;
    }

    private function desfazerTransacaoAtiva(): void
    {
        if (!$this->conexao->estaEmTransacao()) {
            return;
        }

        try {
            $this->conexao->desfazerTransacao();
        } catch (Throwable) {
        }
    }

    private function sqlInsercao(): string
    {
        return 'INSERT INTO ' . self::TABELA . ' (
            INSERTED_DATE, 
            USERNAME, 
            APPLICATION, 
            CREATOR, 
            ACTION, 
            DESCRIPTION
        ) VALUES (SYSDATE, ?, ?, ?, ?, ?)';
    }

    private function sqlLocalizacao(): string
    {
        return 'SELECT ID AS ID FROM ' . self::TABELA
            . ' WHERE ACTION = ?'
            . ' AND DBMS_LOB.INSTR(DESCRIPTION, ?) = 1'
            . ' AND DBMS_LOB.GETLENGTH(DESCRIPTION) = LENGTH(?)';
    }
}
