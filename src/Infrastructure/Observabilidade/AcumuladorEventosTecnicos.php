<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Observabilidade;

use InvalidArgumentException;
use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException as InvalidArgumentExceptionPsr;
use Psr\Log\LogLevel;
use Semae\Pca\Application\Enums\AcaoExecucaoPublicacao;
use Semae\Pca\Application\Enums\AcaoProcessamentoUnidade;
use Semae\Pca\Infrastructure\Pncp\Observabilidade\AcaoTecnicaPncp;
use Stringable;

final class AcumuladorEventosTecnicos extends AbstractLogger
{
    private const UUID_V4 = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/Di';

    /** @var string[] */
    private const CHAVES_SENSIVEIS = [
        'password',
        'senha',
        'credential',
        'credencial',
        'secret',
        'segredo',
        'token',
        'jwt',
        'authorization',
        'autorizacao',
        'sql',
        'query',
        'connection',
        'conexao',
        'payload',
        'request',
        'response',
        'resposta',
        'body',
        'header',
        'cookie',
        'exception',
        'trace',
        'stack',
    ];

    /** @var EventoTecnico[] */
    private array $eventos = [];
    private readonly string $correlationId;
    private readonly string $environment;
    private readonly string $module;
    private readonly ?string $ipAddress;
    private readonly int $inicioMonotonicoMs;

    public function __construct(
        string $correlationId,
        string $environment,
        string $module,
        ?string $ipAddress,
        private readonly RelogioEventosTecnicos $relogio
    ) {
        $environment = trim($environment);
        $module = trim($module);

        if (preg_match(self::UUID_V4, $correlationId) !== 1) {
            throw new InvalidArgumentException('Correlação dos eventos técnicos deve ser UUID v4.');
        }

        if ($environment === '') {
            throw new InvalidArgumentException('Ambiente dos eventos técnicos é obrigatório.');
        }

        if ($module === '') {
            throw new InvalidArgumentException('Módulo dos eventos técnicos é obrigatório.');
        }

        $this->correlationId = $correlationId;
        $this->environment = $environment;
        $this->module = $module;
        $this->ipAddress = $this->normalizarIp($ipAddress);
        $this->inicioMonotonicoMs = $this->relogio->tempoMonotonicoMs();
    }

    /** @param array<string, mixed> $context */
    public function log(
        mixed $level,
        string|Stringable $message,
        array $context = []
    ): void {
        $nivel = $this->normalizarNivel($level);
        $acao = $this->normalizarAcao($context['action'] ?? null);

        if ($acao === null) {
            return;
        }

        $contextoDetalhado = is_array($context['context'] ?? null)
            ? $context['context']
            : [];

        foreach ($context as $chave => $valor) {
            if (!in_array($chave, ['action', 'error', 'context', 'metadata'], true)) {
                $contextoDetalhado[$chave] = $valor;
            }
        }

        $this->eventos[] = new EventoTecnico(
            $this->relogio->agora(),
            $nivel,
            $this->correlationId,
            $this->environment,
            $this->module,
            $acao,
            $this->sanitizarMensagem((string) $message),
            $this->sanitizarMapa($context['error'] ?? []),
            $this->sanitizarMapa($contextoDetalhado),
            $this->sanitizarMapa($context['metadata'] ?? []),
            $this->ipAddress
        );
    }

    /** @return EventoTecnico[] */
    public function getEventos(): array
    {
        return $this->eventos;
    }

    public function criarLoteParaExportacao(): LoteEventosTecnicos
    {
        $duracao = max(
            0,
            $this->relogio->tempoMonotonicoMs() - $this->inicioMonotonicoMs
        );

        return new LoteEventosTecnicos($this->eventos, $duracao);
    }

    private function normalizarNivel(mixed $nivel): NivelEventoTecnico
    {
        return match ($nivel) {
            LogLevel::DEBUG => NivelEventoTecnico::DEBUG,
            LogLevel::INFO, LogLevel::NOTICE => NivelEventoTecnico::INFO,
            LogLevel::WARNING => NivelEventoTecnico::WARNING,
            LogLevel::ERROR, LogLevel::CRITICAL, LogLevel::ALERT, LogLevel::EMERGENCY =>
                NivelEventoTecnico::ERROR,
            default => throw new InvalidArgumentExceptionPsr('Nível PSR-3 inválido.'),
        };
    }

    private function normalizarAcao(mixed $acao): ?string
    {
        if ($acao instanceof AcaoExecucaoPublicacao) {
            return $acao->value;
        }

        if ($acao instanceof AcaoProcessamentoUnidade) {
            return $acao->value;
        }

        if ($acao instanceof AcaoTecnicaPncp) {
            return $acao->value;
        }

        if (!is_string($acao)) {
            return null;
        }

        $acao = trim($acao);

        return AcaoExecucaoPublicacao::tryFrom($acao)?->value
            ?? AcaoProcessamentoUnidade::tryFrom($acao)?->value
            ?? AcaoTecnicaPncp::tryFrom($acao)?->value;
    }

    /** @return array<string, mixed> */
    private function sanitizarMapa(mixed $mapa, int $profundidade = 0): array
    {
        if (!is_array($mapa) || $profundidade > 8) {
            return [];
        }

        $sanitizado = [];

        foreach ($mapa as $chave => $valor) {
            if (!is_string($chave) || $this->chaveSensivel($chave)) {
                continue;
            }

            [$aceito, $valorSanitizado] = $this->sanitizarValor(
                $valor,
                $profundidade + 1
            );

            if ($aceito) {
                $sanitizado[$chave] = $valorSanitizado;
            }
        }

        return $sanitizado;
    }

    /** @return array{bool, mixed} */
    private function sanitizarValor(mixed $valor, int $profundidade): array
    {
        if ($valor === null || is_bool($valor) || is_int($valor)) {
            return [true, $valor];
        }

        if (is_float($valor)) {
            return is_finite($valor) ? [true, $valor] : [false, null];
        }

        if (is_string($valor)) {
            return [true, $this->sanitizarTexto($valor)];
        }

        if (is_array($valor)) {
            return [true, $this->sanitizarEstrutura($valor, $profundidade)];
        }

        return [false, null];
    }

    /** @param array<int|string, mixed> $estrutura */
    private function sanitizarEstrutura(array $estrutura, int $profundidade): array
    {
        if ($profundidade > 8) {
            return [];
        }

        $sanitizado = [];

        foreach ($estrutura as $chave => $valor) {
            if (is_string($chave) && $this->chaveSensivel($chave)) {
                continue;
            }

            [$aceito, $valorSanitizado] = $this->sanitizarValor(
                $valor,
                $profundidade + 1
            );

            if ($aceito) {
                $sanitizado[$chave] = $valorSanitizado;
            }
        }

        return $sanitizado;
    }

    private function chaveSensivel(string $chave): bool
    {
        $normalizada = strtolower((string) preg_replace('/[^a-z0-9]/i', '', $chave));

        foreach (self::CHAVES_SENSIVEIS as $fragmento) {
            if (str_contains($normalizada, $fragmento)) {
                return true;
            }
        }

        return false;
    }

    private function sanitizarMensagem(string $mensagem): string
    {
        $mensagem = trim($mensagem);

        if ($mensagem === '') {
            return 'Evento técnico sem mensagem.';
        }

        $sanitizada = $this->sanitizarTexto($mensagem);

        return $sanitizada === '[REDACTED]'
            ? 'Conteúdo técnico sensível removido.'
            : $sanitizada;
    }

    private function sanitizarTexto(string $texto): string
    {
        $texto = trim($texto);

        if ($this->pareceSql($texto) || $this->pareceJsonCompleto($texto)) {
            return '[REDACTED]';
        }

        $texto = (string) preg_replace(
            '/\bBearer\s+[A-Za-z0-9._~+\/-]+=*/i',
            'Bearer [REDACTED]',
            $texto
        );
        $texto = (string) preg_replace(
            '/\b[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\b/',
            '[REDACTED]',
            $texto
        );
        $texto = (string) preg_replace(
            '/\b(password|senha|token|secret|segredo)\s*[:=]\s*[^\s;,]+/i',
            '$1=[REDACTED]',
            $texto
        );

        return $texto;
    }

    private function pareceSql(string $texto): bool
    {
        return preg_match(
            '/\b(SELECT|INSERT|UPDATE|DELETE|MERGE|ALTER|DROP|CREATE)\b.*'
                . '\b(FROM|INTO|SET|TABLE|VALUES)\b/is',
            $texto
        ) === 1;
    }

    private function pareceJsonCompleto(string $texto): bool
    {
        $primeiro = $texto[0] ?? '';

        if ($primeiro !== '{' && $primeiro !== '[') {
            return false;
        }

        json_decode($texto, true);

        return json_last_error() === JSON_ERROR_NONE;
    }

    private function normalizarIp(?string $ipAddress): ?string
    {
        if ($ipAddress === null) {
            return null;
        }

        $ipAddress = trim($ipAddress);

        return filter_var($ipAddress, FILTER_VALIDATE_IP) === false
            ? null
            : $ipAddress;
    }
}
