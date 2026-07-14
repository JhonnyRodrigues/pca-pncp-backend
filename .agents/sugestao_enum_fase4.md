enum UnidadePncpMapper: int
{
    case GABINETE_PRESIDENCIA = 204;
    case PROCURADORIA_JURIDICA = 207;
    case DIRETORIA_GERAL_SANEAMENTO = 210;
    case DIRETORIA_ADMIN_FINANCEIRA = 205;

    public static function fromLotacoesSgi(int $codLotacao): self
    {
        return match ($codLotacao) {
            1   => self::GABINETE_PRESIDENCIA,
            3   => self::PROCURADORIA_JURIDICA,
            94  => self::DIRETORIA_GERAL_SANEAMENTO,
            149 => self::DIRETORIA_GERAL_ADMINISTRATIVA_FINANCEIRA,
            default => throw new InvalidArgumentException(
                'Lotação de unidade orçamentária não mapeada para o PNCP.'
            ),
        };
    }
}