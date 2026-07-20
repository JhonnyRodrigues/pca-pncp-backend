<?php

declare(strict_types=1);

namespace Tests\Application\UseCases\PublicarPca;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\UseCases\PublicarPca\PublicarPcaRequest;
use Semae\Pca\Application\UseCases\PublicarPca\PublicarPcaValidator;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;

class PublicarPcaValidatorTest extends TestCase
{
    private const ERRO_USUARIO_LOGADO = 'A identificação do Usuário logado é obrigatória.';
    private const ERRO_CREDENCIAIS = 'Credenciais de autenticação PNCP são obrigatórias.';

    public function testDeveValidarRequestSemErros(): void
    {
        $resultado = (new PublicarPcaValidator())->validarRequest($this->criarRequest('jsilva', $this->criarCredenciais()));

        $this->assertTrue($resultado->isValido());
        $this->assertSame([], $resultado->getErros());
    }

    #[DataProvider('usuariosLogadosInvalidos')]
    public function testDeveRetornarErroQuandoUsuarioLogadoForInvalido(string $usuarioLogado): void
    {
        $resultado = (new PublicarPcaValidator())->validarRequest($this->criarRequest($usuarioLogado, $this->criarCredenciais()));

        $this->assertFalse($resultado->isValido());
        $this->assertSame([self::ERRO_USUARIO_LOGADO],$resultado->getErros());
    }

    public static function usuariosLogadosInvalidos(): array
    {
        return [
            'vazio' => [''],
            'apenas espaços' => ['   '],
        ];
    }

    public function testDeveRetornarErroQuandoCredenciaisForemObrigatorias(): void
    {
        $resultado = (new PublicarPcaValidator())->validarRequest($this->criarRequest('jsilva', []));

        $this->assertFalse($resultado->isValido());
        $this->assertSame([self::ERRO_CREDENCIAIS],$resultado->getErros());
    }

    #[DataProvider('credenciaisInvalidas')]
    public function testDeveRetornarErroQuandoEstruturaDasCredenciaisForInvalida(array $credenciais): void
    {
        $resultado = (new PublicarPcaValidator())->validarRequest($this->criarRequest('jsilva', $credenciais));

        $this->assertFalse($resultado->isValido());
        $this->assertSame([self::ERRO_CREDENCIAIS],$resultado->getErros());
    }

    public static function credenciaisInvalidas(): array
    {
        $credenciais = [
            'ambiente' => 'homologacao',
            'baseUrl' => 'https://treina.pncp.gov.br/api/pncp',
            'usuario' => 'usuario-pncp',
            'senha' => 'senha-pncp',
        ];

        $casos = [];

        foreach (array_keys($credenciais) as $chave) {
            $semChave = $credenciais;
            unset($semChave[$chave]);
            $casos["sem {$chave}"] = [$semChave];

            $comValorNulo = $credenciais;
            $comValorNulo[$chave] = null;
            $casos["{$chave} nulo"] = [$comValorNulo];

            $comValorVazio = $credenciais;
            $comValorVazio[$chave] = '';
            $casos["{$chave} vazio"] = [$comValorVazio];
        }

        return $casos;
    }

    public function testDeveRetornarMultiplosErros(): void
    {
        $resultado = (new PublicarPcaValidator())->validarRequest($this->criarRequest('   ', []));

        $this->assertFalse($resultado->isValido());
        $this->assertSame([self::ERRO_USUARIO_LOGADO, self::ERRO_CREDENCIAIS], $resultado->getErros());
    }

    private function criarRequest(string $usuarioLogado, array $credenciais): PublicarPcaRequest
    {
        return new PublicarPcaRequest(
            new Cnpj('12.345.678/0001-95'),
            new AnoExecucao((int) date('Y')),
            $usuarioLogado,
            $credenciais
        );
    }

    private function criarCredenciais(): array
    {
        return [
            'ambiente' => 'homologacao',
            'baseUrl' => 'https://treina.pncp.gov.br/api/pncp',
            'usuario' => 'usuario-pncp',
            'senha' => 'senha-pncp',
        ];
    }
}
