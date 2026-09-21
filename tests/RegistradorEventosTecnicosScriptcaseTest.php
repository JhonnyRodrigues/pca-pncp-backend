<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Semae\Pca\RegistradorEventosTecnicosScriptcase;
use Tests\Support\PerfilPublicacaoPcaFixture;

final class RegistradorEventosTecnicosScriptcaseTest extends TestCase
{
    private const VARIAVEL_REGISTRO = 'PCA_PNCP_PROFILE_REGISTRY_FILE';
    private const SERVIDOR = 'pca.logs.test';

    private string|false $registroAnterior;
    private string $diretorioTemporario;

    protected function setUp(): void
    {
        $this->registroAnterior = getenv(self::VARIAVEL_REGISTRO);
        $base = tempnam(sys_get_temp_dir(), 'pca-log-');
        if ($base === false || !unlink($base) || !mkdir($base)) {
            throw new RuntimeException('Não foi possível preparar o teste de logs.');
        }
        $this->diretorioTemporario = $base;
        $perfil = $this->escreverPhp('perfil.php', PerfilPublicacaoPcaFixture::valido());
        $registro = $this->escreverPhp('registro.php', [self::SERVIDOR => $perfil]);
        putenv(self::VARIAVEL_REGISTRO . '=' . $registro);
    }

    protected function tearDown(): void
    {
        $this->registroAnterior === false
            ? putenv(self::VARIAVEL_REGISTRO)
            : putenv(self::VARIAVEL_REGISTRO . '=' . $this->registroAnterior);

        foreach (glob($this->diretorioTemporario . DIRECTORY_SEPARATOR . '*') ?: [] as $arquivo) {
            unlink($arquivo);
        }
        rmdir($this->diretorioTemporario);
    }

    public function testDeveRegistrarLoteOrdenadoComUmaTransacaoPorEvento(): void
    {
        $adodb = new AdodbRegistradorPublicoFake();

        $aviso = (new RegistradorEventosTecnicosScriptcase())->registrar(
            $adodb,
            'jsilva',
            self::SERVIDOR,
            'PCA_PUBLICAR_B',
            [$this->evento('PRIMEIRO'), $this->evento('SEGUNDO')]
        );

        $this->assertNull($aviso);
        $this->assertSame(2, $adodb->inicios);
        $this->assertSame(2, $adodb->commits);
        $this->assertSame(0, $adodb->rollbacks);
        $this->assertCount(2, $adodb->clobs);
        $this->assertStringContainsString('PRIMEIRO', $adodb->clobs[0]);
        $this->assertStringContainsString('SEGUNDO', $adodb->clobs[1]);
    }

    public function testDeveInterromperRestantePreservandoEventoJaConfirmado(): void
    {
        $adodb = new AdodbRegistradorPublicoFake();
        $adodb->falharUpdateClobNaChamada = 2;

        $aviso = (new RegistradorEventosTecnicosScriptcase())->registrar(
            $adodb,
            'jsilva',
            self::SERVIDOR,
            'PCA_PUBLICAR_B',
            [
                $this->evento('CONFIRMADO'),
                $this->evento('FALHOU'),
                $this->evento('NAO_TENTADO'),
            ]
        );

        $this->assertSame(
            'O resultado foi preservado, mas nem todos os eventos técnicos puderam ser registrados.',
            $aviso
        );
        $this->assertSame(2, $adodb->inicios);
        $this->assertSame(1, $adodb->commits);
        $this->assertSame(1, $adodb->rollbacks);
        $this->assertCount(2, $adodb->clobs);
        $this->assertStringNotContainsString('NAO_TENTADO', implode('', $adodb->clobs));
    }

    /** @return array<string, mixed> */
    private function evento(string $acao): array
    {
        return [
            'timestamp' => '2026-09-20T18:00:00-03:00',
            'level' => 'INFO',
            'correlationId' => '11111111-1111-4111-8111-111111111111',
            'environment' => 'HOMOLOGACAO',
            'system' => 'pca-pncp-backend',
            'module' => 'publicacao-pca',
            'action' => $acao,
            'message' => 'Evento controlado.',
            'error' => [],
            'context' => [],
            'metadata' => [],
            'ipAddress' => null,
        ];
    }

    /** @param mixed $retorno */
    private function escreverPhp(string $nome, mixed $retorno): string
    {
        $caminho = $this->diretorioTemporario . DIRECTORY_SEPARATOR . $nome;
        file_put_contents($caminho, "<?php\n\nreturn " . var_export($retorno, true) . ";\n");

        return $caminho;
    }
}

final class AdodbRegistradorPublicoFake
{
    public int $inicios = 0;
    public int $commits = 0;
    public int $rollbacks = 0;
    public int $chamadasUpdateClob = 0;
    public ?int $falharUpdateClobNaChamada = null;

    /** @var string[] */
    public array $clobs = [];

    public function BeginTrans(): bool
    {
        $this->inicios++;

        return true;
    }

    public function CommitTrans(): bool
    {
        $this->commits++;

        return true;
    }

    public function RollbackTrans(): bool
    {
        $this->rollbacks++;

        return true;
    }

    /** @param array<string, mixed> $parametros */
    public function Execute(string $sql, array $parametros = []): bool
    {
        return true;
    }

    /** @param array<string, mixed> $parametros @return array<int, array{ID: int}> */
    public function GetAll(string $sql, array $parametros = []): array
    {
        return [['ID' => 7757000 + $this->inicios]];
    }

    public function UpdateClob(
        string $tabela,
        string $coluna,
        string $conteudo,
        string $criterio
    ): bool {
        $this->chamadasUpdateClob++;
        $this->clobs[] = $conteudo;

        return $this->falharUpdateClobNaChamada !== $this->chamadasUpdateClob;
    }

    public function Param(false|string $nome): string
    {
        return $nome === false ? '' : ':' . $nome;
    }

    public function ErrorNo(): int
    {
        return 0;
    }

    public function ErrorMsg(): string
    {
        return '';
    }
}
