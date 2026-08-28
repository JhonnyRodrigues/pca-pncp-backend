<?php

declare(strict_types=1);

namespace Tests\Application\ValueObjects;

use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\ValueObjects\ContextoUsuario;
use Semae\Pca\Domain\ValueObjects\Lotacao;
use Semae\Pca\Domain\ValueObjects\Representante;

final class ContextoUsuarioTest extends TestCase
{
    public function testDeveManterRepresentanteELotacaoComoUmContextoCoeso(): void
    {
        $representante = new Representante(709, 'Maria da Silva');
        $lotacao = new Lotacao(159, 'Coordenadoria de Tecnologia');

        $contexto = new ContextoUsuario($representante, $lotacao);

        $this->assertSame($representante, $contexto->getRepresentante());
        $this->assertSame($lotacao, $contexto->getLotacao());
    }
}
