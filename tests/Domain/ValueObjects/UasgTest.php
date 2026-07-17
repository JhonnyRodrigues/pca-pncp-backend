<?php

declare(strict_types=1);

namespace Tests\Domain\ValueObjects;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Semae\Pca\Domain\ValueObjects\Uasg;

class UasgTest extends TestCase
{
    public function testDeveCriarUasgValida(): void
    {
        $uasg = new Uasg('928344', 'SEMAE Piracicaba');

        $this->assertSame('928344', $uasg->getCodigo());
        $this->assertSame('SEMAE Piracicaba', $uasg->getDescricao());
    }

    public function testDeveRejeitarCodigoVazio(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Uasg('   ', 'SEMAE Piracicaba');
    }

    public function testDeveRejeitarDescricaoVazia(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Uasg('928344', '   ');
    }

    public function testDeveSerImutavel(): void
    {
        $reflection = new ReflectionClass(Uasg::class);

        foreach ($reflection->getMethods() as $method) {
            $this->assertStringStartsNotWith('set', $method->getName());
        }
    }
}
