<?php

declare(strict_types=1);

namespace Tests\Application\ValueObjects;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\ValueObjects\FalhaSegura;

final class FalhaSeguraTest extends TestCase
{
    public function testDeveNormalizarCodigoEMensagemSeguros(): void
    {
        $falha = new FalhaSegura(' CONFIGURACAO_INVALIDA ', ' Configuração incompleta. ');

        $this->assertSame('CONFIGURACAO_INVALIDA', $falha->getCodigo());
        $this->assertSame('Configuração incompleta.', $falha->getMensagem());
    }

    #[DataProvider('dadosInvalidos')]
    public function testDeveRejeitarDadosObrigatoriosVazios(string $codigo, string $mensagem): void
    {
        $this->expectException(InvalidArgumentException::class);

        new FalhaSegura($codigo, $mensagem);
    }

    /** @return array<string, array{string, string}> */
    public static function dadosInvalidos(): array
    {
        return [
            'código vazio' => ['', 'Mensagem segura.'],
            'código com espaços' => ['   ', 'Mensagem segura.'],
            'mensagem vazia' => ['CODIGO', ''],
            'mensagem com espaços' => ['CODIGO', '   '],
        ];
    }
}
