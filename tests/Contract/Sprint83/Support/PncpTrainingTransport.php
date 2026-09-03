<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint83\Support;

use Semae\Pca\Infrastructure\Pncp\Http\FalhaTransportePncpException;
use Semae\Pca\Infrastructure\Pncp\Http\RequisicaoHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\RespostaHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;

final class PncpTrainingTransport implements TransporteHttpPncp
{
    private bool $perderRespostaPublicacao = false;

    /** @var RequisicaoHttpPncp[] */
    private array $requests = [];

    public function __construct(private readonly TransporteHttpPncp $delegate)
    {
    }

    public function perderProximaRespostaDePublicacao(): void
    {
        $this->perderRespostaPublicacao = true;
    }

    public function enviar(RequisicaoHttpPncp $requisicao): RespostaHttpPncp
    {
        $this->requests[] = $requisicao;
        $response = $this->delegate->enviar($requisicao);

        if (
            $this->perderRespostaPublicacao
            && $this->isPublication($requisicao)
            && in_array($response->getStatus(), [200, 201], true)
        ) {
            $this->perderRespostaPublicacao = false;

            throw FalhaTransportePncpException::execucaoCurlFalhou(28);
        }

        return $response;
    }

    public function contar(string $method, string $pathSuffix): int
    {
        return count(array_filter(
            $this->requests,
            static fn (RequisicaoHttpPncp $request): bool =>
                $request->getMetodo() === $method
                && str_ends_with($request->getCaminho(), $pathSuffix)
        ));
    }

    /** @param string[] $lexemes */
    public function publicacaoContemLexemas(array $lexemes): bool
    {
        foreach ($this->requests as $request) {
            if (!$this->isPublication($request)) {
                continue;
            }

            $body = $request->getCorpo() ?? '';
            foreach ($lexemes as $lexeme) {
                if (!str_contains($body, $lexeme)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    public function percorreuPaginaPosteriorAUm(): bool
    {
        foreach ($this->requests as $request) {
            if (
                $request->getMetodo() === 'GET'
                && str_contains($request->getCaminho(), '/itens?')
                && preg_match('/[?&]pagina=([2-9]|[1-9][0-9]+)/D', $request->getCaminho()) === 1
            ) {
                return true;
            }
        }

        return false;
    }

    private function isPublication(RequisicaoHttpPncp $request): bool
    {
        return $request->getMetodo() === 'POST'
            && preg_match('#^/v1/orgaos/[^/]+/pca$#D', $request->getCaminho()) === 1;
    }
}
