<?php

declare(strict_types=1);

namespace Semae\Pca;

use Semae\Pca\Presentation\ViewModels\PublicarPcaViewModel;

/**
 * Essa classe funciona como um DTO da fronteira pública. DTO é um objeto simples usado para transportar dados entre fronteiras.
 * 
 * Ela pertence à fronteira de integração com o Scriptcase
 */
final class ResultadoPublicacaoPcaScriptcase
{
    /**
     * @param array<int, array<string, mixed>> $eventos
     */
    public function __construct(
        private readonly PublicarPcaViewModel $resultado,
        private readonly array $eventos,
        private readonly ?string $avisoRegistroTecnico
    ) {}

    public function getResultado(): PublicarPcaViewModel
    {
        return $this->resultado;
    }

    /** @return array<int, array<string, mixed>> */
    public function getEventos(): array
    {
        return $this->eventos;
    }

    public function getAvisoRegistroTecnico(): ?string
    {
        return $this->avisoRegistroTecnico;
    }
}
