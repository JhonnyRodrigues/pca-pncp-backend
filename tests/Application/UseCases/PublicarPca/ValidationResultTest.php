<?php

declare(strict_types=1);

namespace Tests\Application\UseCases\PublicarPca;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\UseCases\PublicarPca\ValidationResult;

class ValidationResultTest extends TestCase
{
    public function testDeveInformarQuandoValidacaoForValida(): void
    {
        $resultado = new ValidationResult([]);

        $this->assertTrue($resultado->isValido());
    }

    public function testDeveInformarQuandoValidacaoForInvalida(): void
    {
        $resultado = new ValidationResult(['Este array contém erro de validação.']);

        $this->assertFalse($resultado->isValido());
    }

    public function testDeveRetornarListaDeErros(): void
    {
        $erros = ['Primeiro erro.', 'Segundo erro.'];

        $resultado = new ValidationResult($erros);

        $this->assertSame($erros, $resultado->getErros());
    }
}
