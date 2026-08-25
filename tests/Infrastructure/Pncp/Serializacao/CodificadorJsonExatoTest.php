<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Pncp\Serializacao;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Infrastructure\Pncp\Serializacao\CodificadorJsonExato;
use Semae\Pca\Infrastructure\Pncp\Serializacao\FalhaSerializacaoPncp;
use Semae\Pca\Infrastructure\Pncp\Serializacao\NumeroJsonExato;
use stdClass;

final class CodificadorJsonExatoTest extends TestCase
{
    public function testDevePreservarNumerosExatosComZerosFinaisSemAspas(): void
    {
        $valor = [
            'quantidade' => new NumeroJsonExato('6.0000'),
            'valorUnitario' => new NumeroJsonExato('0.1667'),
            'valorTotal' => new NumeroJsonExato('1.0000'),
        ];

        $this->assertSame(
            '{"quantidade":6.0000,"valorUnitario":0.1667,"valorTotal":1.0000}',
            (new CodificadorJsonExato())->codificar($valor)
        );
    }

    public function testDeveCodificarEscapesUnicodeEscalaresEObjetoNaOrdemRecebida(): void
    {
        $valor = [
            'texto' => "Aspas \" barra \\ linha\n",
            'unicode' => 'ação 漢字',
            'ativo' => true,
            'vazio' => null,
            'inteiro' => 7,
        ];

        $this->assertSame(
            '{"texto":"Aspas \\" barra \\\\ linha\\n","unicode":"ação 漢字","ativo":true,"vazio":null,"inteiro":7}',
            (new CodificadorJsonExato())->codificar($valor)
        );
    }

    public function testDeveCodificarListasEValoresAninhadosRecursivamente(): void
    {
        $valor = [1, 'dois', [false, null], ['numero' => new NumeroJsonExato('-2.5000')]];

        $this->assertSame(
            '[1,"dois",[false,null],{"numero":-2.5000}]',
            (new CodificadorJsonExato())->codificar($valor)
        );
    }

    /** @return iterable<string, array{mixed}> */
    public static function tiposNaoAceitos(): iterable
    {
        yield 'float na raiz' => [1.25];
        yield 'float aninhado' => [['valor' => 1.25]];
        yield 'objeto desconhecido' => [new stdClass()];
    }

    #[DataProvider('tiposNaoAceitos')]
    public function testDeveRejeitarTiposNaoAceitosComFalhaSegura(mixed $valor): void
    {
        $this->expectException(FalhaSerializacaoPncp::class);
        $this->expectExceptionMessage('Não foi possível serializar o payload PNCP.');

        (new CodificadorJsonExato())->codificar($valor);
    }

    public function testDeveRejeitarRecursoComFalhaSegura(): void
    {
        $recurso = fopen('php://memory', 'rb');
        $this->assertIsResource($recurso);

        try {
            (new CodificadorJsonExato())->codificar($recurso);
            $this->fail('Era esperada falha de serialização para recurso.');
        } catch (FalhaSerializacaoPncp $falha) {
            $this->assertSame('Não foi possível serializar o payload PNCP.', $falha->getMessage());
        } finally {
            fclose($recurso);
        }
    }

    public function testDeveRejeitarUtf8InvalidoEmValorSemExporConteudo(): void
    {
        $this->expectException(FalhaSerializacaoPncp::class);
        $this->expectExceptionMessage('Não foi possível serializar o payload PNCP.');

        (new CodificadorJsonExato())->codificar(['texto' => "\xB1\x31"]);
    }

    public function testDeveRejeitarUtf8InvalidoEmChaveSemExporConteudo(): void
    {
        $this->expectException(FalhaSerializacaoPncp::class);
        $this->expectExceptionMessage('Não foi possível serializar o payload PNCP.');

        (new CodificadorJsonExato())->codificar(["\xB1\x31" => 'valor']);
    }
}
