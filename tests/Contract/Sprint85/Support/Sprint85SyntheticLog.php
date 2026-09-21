<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint85\Support;

use DateTimeImmutable;
use JsonException;
use Semae\Pca\Infrastructure\Observabilidade\EventoTecnico;
use Semae\Pca\Infrastructure\Observabilidade\NivelEventoTecnico;
use Tests\Contract\Sprint84\Support\Sprint84GateException;

final class Sprint85SyntheticLog
{
    public const MINIMUM_BYTES = 4001;

    /** @return array{json: string, bytes: int, sha256: string} */
    public static function create(string $identifier, string $correlationId): array
    {
        if (
            preg_match(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/Di',
                $correlationId
            ) !== 1
        ) {
            throw new Sprint84GateException(
                'CORRELACAO_INVALIDA',
                'LOG_SINTETICO',
                'O gate exige um X-Request-ID UUID v4 válido.'
            );
        }

        $filler = str_repeat(
            'Validação técnica de acentuação: ação, publicação, órgão e execução. ',
            80
        );
        $event = new EventoTecnico(
            new DateTimeImmutable(),
            NivelEventoTecnico::INFO,
            $correlationId,
            'HOMOLOGACAO',
            'sprint85-log-gate',
            'VALIDAR_LOG_SCRIPTCASE',
            'Evento sintético para validar a persistência integral pelo Scriptcase.',
            [],
            [],
            [
                'identificadorGate' => $identifier,
                'conteudoSintetico' => $filler,
            ],
            null
        );

        $envelope = $event->paraEnvelope();
        $envelope['error'] = (object) [];
        $envelope['context'] = (object) [];

        try {
            $json = json_encode(
                $envelope,
                JSON_THROW_ON_ERROR
                    | JSON_UNESCAPED_SLASHES
                    | JSON_PRESERVE_ZERO_FRACTION
                    | JSON_INVALID_UTF8_SUBSTITUTE
            );
        } catch (JsonException $failure) {
            throw new Sprint84GateException(
                'LOG_SERIALIZACAO_FALHOU',
                'LOG_SINTETICO',
                'O evento sintético não pôde ser serializado.',
                [],
                $failure
            );
        }

        $bytes = strlen($json);
        if (
            $bytes < self::MINIMUM_BYTES
            || !str_contains($json, '\\u00e7')
            || !str_contains($json, '"error":{}')
            || !str_contains($json, '"context":{}')
        ) {
            throw new Sprint84GateException(
                'LOG_SINTETICO_INVALIDO',
                'LOG_SINTETICO',
                'O evento sintético não satisfez o contrato do gate.',
                ['bytes' => $bytes]
            );
        }

        return [
            'json' => $json,
            'bytes' => $bytes,
            'sha256' => hash('sha256', $json),
        ];
    }
}
