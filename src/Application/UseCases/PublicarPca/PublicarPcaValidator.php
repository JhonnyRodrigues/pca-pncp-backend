<?php

declare(strict_types=1);

namespace Semae\Pca\Application\UseCases\PublicarPca;

class PublicarPcaValidator
{
    private const CHAVES_CREDENCIAIS = ['ambiente','baseUrl','usuario','senha'];

    public function validarRequest(PublicarPcaRequest $request): ValidationResult
    {
        $erros = [];

        if (trim($request->getUsuarioLogado()) === '') {
            $erros[] = 'A identificação do Usuário logado é obrigatória.';
        }

        if (!$this->credenciaisSaoValidas($request->getCredenciais())) {
            $erros[] = 'Credenciais de autenticação PNCP são obrigatórias.';
        }

        return new ValidationResult($erros);
    }

    private function credenciaisSaoValidas(array $credenciais): bool
    {
        foreach (self::CHAVES_CREDENCIAIS as $chave) {
            if (!array_key_exists($chave, $credenciais) || $credenciais[$chave] === null || $credenciais[$chave] === '') {
                return false;
            }
        }

        return true;
    }
}
