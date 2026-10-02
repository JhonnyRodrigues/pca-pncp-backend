<?php
// require_once __DIR__ . '/../../../autoload.php'; /* foi substituido pelo namespace */
namespace Tests\Domain\ValueObjects;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use InvalidArgumentException;

class CnpjTest extends TestCase
{
    public function testDeveAceitarCnpjValido(): void
    {
        $cnpj = new Cnpj('12.345.678/0001-95');

        $this->assertSame('12345678000195', $cnpj->getValor());
    }

    public function testDeveAceitarCnpjAlfanumericoValido(): void
    {
        $cnpj = new Cnpj('J3.ABB.3LZ/0001-46');

        $this->assertSame('J3ABB3LZ000146', $cnpj->getValor());
    }

    public function testDeveRejeitarCnpjInvalido(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Cnpj('12.345.678/0001-96');
    }

    public function testDeveRejeitarCnpjAlfanumericoComDigitoVerificadorInvalido(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Cnpj('J3.ABB.3LZ/0001-47');
    }
}
