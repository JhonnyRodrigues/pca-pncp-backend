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

        $this->assertSame('12345678000195', $cnpj->valor());
    }

    public function testDeveRejeitarCnpjInvalido(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Cnpj('12.345.678/0001-96');
    }
}
