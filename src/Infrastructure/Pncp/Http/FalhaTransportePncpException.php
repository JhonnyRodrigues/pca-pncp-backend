<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Pncp\Http;

use RuntimeException;

final class FalhaTransportePncpException extends RuntimeException
{
    private function __construct(
        private readonly string $codigoSeguro,
        string $mensagem,
        private readonly ?int $errnoCurlSeguro = null
    ) {
        parent::__construct($mensagem);
    }

    public static function extensaoCurlIndisponivel(): self
    {
        return new self(
            'PNCP_TRANSPORTE_CURL_INDISPONIVEL',
            'Extensão cURL indisponível para o transporte PNCP.'
        );
    }

    public static function inicializacaoCurlFalhou(): self
    {
        return new self(
            'PNCP_TRANSPORTE_INICIALIZACAO_FALHOU',
            'Não foi possível inicializar o transporte PNCP.'
        );
    }

    public static function execucaoCurlFalhou(int $errno): self
    {
        return new self(
            'PNCP_TRANSPORTE_EXECUCAO_FALHOU',
            "Falha de transporte PNCP (cURL {$errno}).",
            $errno
        );
    }

    public static function configuracaoCurlFalhou(): self
    {
        return new self(
            'PNCP_TRANSPORTE_CONFIGURACAO_FALHOU',
            'Não foi possível configurar o transporte PNCP.'
        );
    }

    public static function limitePayloadExcedido(): self
    {
        return new self(
            'PNCP_TRANSPORTE_PAYLOAD_EXCEDE_LIMITE',
            'Payload PNCP excedeu o limite configurado.'
        );
    }

    public static function limiteRespostaExcedido(): self
    {
        return new self(
            'PNCP_TRANSPORTE_RESPOSTA_EXCEDE_LIMITE',
            'Resposta do PNCP excedeu o limite configurado.'
        );
    }

    public static function tlsNaoConfirmado(): self
    {
        return new self(
            'PNCP_TRANSPORTE_TLS_NAO_CONFIRMADO',
            'Não foi possível confirmar o transporte TLS do PNCP.'
        );
    }

    public static function respostaHttpInvalida(): self
    {
        return new self(
            'PNCP_TRANSPORTE_RESPOSTA_INVALIDA',
            'O PNCP retornou uma resposta HTTP inválida.'
        );
    }

    public function getCodigoSeguro(): string
    {
        return $this->codigoSeguro;
    }

    public function getErrnoCurlSeguro(): ?int
    {
        return $this->errnoCurlSeguro;
    }
}
