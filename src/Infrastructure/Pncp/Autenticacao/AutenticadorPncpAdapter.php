<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Pncp\Autenticacao;

use InvalidArgumentException;
use JsonException;
use Psr\Log\LoggerInterface;
use Semae\Pca\Application\Ports\AutenticadorPncp;
use Semae\Pca\Application\Results\ResultadoAutenticacaoPncp;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Infrastructure\Pncp\Http\ConfiguracaoTransportePncp;
use Semae\Pca\Infrastructure\Pncp\Http\FalhaTransportePncpException;
use Semae\Pca\Infrastructure\Pncp\Http\RequisicaoHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\RespostaHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Observabilidade\AcaoTecnicaPncp;
use Throwable;

final class AutenticadorPncpAdapter implements AutenticadorPncp
{
    public function __construct(
        private readonly TransporteHttpPncp $transporte,
        private readonly ConfiguracaoTransportePncp $configuracao,
        private readonly LoggerInterface $logger,
        private readonly string $login,
        private readonly string $senha
    ) {
        if ($login === '' || $senha === '') {
            throw new InvalidArgumentException('Credenciais PNCP são obrigatórias.');
        }
    }

    public function autenticar(): ResultadoAutenticacaoPncp
    {
        try {
            $resposta = $this->transporte->enviar(new RequisicaoHttpPncp(
                'POST',
                '/v1/usuarios/login',
                [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                $this->codificarCredenciais(),
                $this->configuracao->getTimeoutLoginSegundos()
            ));

            return $this->processarResposta($resposta);
        } catch (FalhaTransportePncpException) {
            return $this->falhaSistemica(
                'PNCP_AUTENTICACAO_TRANSPORTE_FALHOU',
                'Não foi possível autenticar no PNCP.'
            );
        } catch (JsonException) {
            return $this->falhaSistemica(
                'PNCP_AUTENTICACAO_CREDENCIAIS_INVALIDAS',
                'Não foi possível preparar a autenticação no PNCP.'
            );
        } catch (Throwable) {
            return $this->falhaSistemica(
                'PNCP_AUTENTICACAO_RESPOSTA_INVALIDA',
                'Resposta de autenticação PNCP inválida.'
            );
        }
    }

    /** @throws JsonException */
    private function codificarCredenciais(): string
    {
        return json_encode(
            ['login' => $this->login, 'senha' => $this->senha],
            JSON_THROW_ON_ERROR
        );
    }

    private function processarResposta(RespostaHttpPncp $resposta): ResultadoAutenticacaoPncp
    {
        $status = $resposta->getStatus();

        if ($status === 401 || $status === 403) {
            return $this->falhaSistemica(
                'PNCP_AUTENTICACAO_NAO_AUTORIZADA',
                'Autenticação PNCP não autorizada.',
                $status
            );
        }

        if ($status !== 200) {
            return $this->falhaSistemica(
                'PNCP_AUTENTICACAO_RESPOSTA_INVALIDA',
                'Resposta de autenticação PNCP inválida.',
                $status
            );
        }

        $authorization = $resposta->getHeader('authorization');

        if (
            $authorization === null
            || preg_match('/^Bearer[ \t]+([^\s]+)$/iD', $authorization, $partes) !== 1
        ) {
            return $this->falhaSistemica(
                'PNCP_AUTENTICACAO_RESPOSTA_INVALIDA',
                'Resposta de autenticação PNCP inválida.',
                $status
            );
        }

        $sessao = new SessaoPncpAutenticada($partes[1]);
        $this->logger->info('Autenticação PNCP concluída.', [
            'action' => AcaoTecnicaPncp::AUTENTICAR_PNCP->value,
            'metadata' => ['statusHttp' => $status],
        ]);

        return ResultadoAutenticacaoPncp::autenticada($sessao);
    }

    private function falhaSistemica(
        string $codigo,
        string $mensagem,
        ?int $statusHttp = null
    ): ResultadoAutenticacaoPncp {
        $metadata = [];

        if ($statusHttp !== null) {
            $metadata['statusHttp'] = $statusHttp;
        }

        $this->logger->error($mensagem, [
            'action' => AcaoTecnicaPncp::AUTENTICAR_PNCP->value,
            'error' => ['code' => $codigo, 'message' => $mensagem],
            'metadata' => $metadata,
        ]);

        return ResultadoAutenticacaoPncp::falhaSistemica(
            new FalhaSegura($codigo, $mensagem)
        );
    }
}
