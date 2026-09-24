<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint94\Support;

use DomainException;
use JsonException;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Infrastructure\Pncp\Autenticacao\SessaoPncpAutenticada;
use Semae\Pca\Infrastructure\Pncp\Http\ConfiguracaoTransportePncp;
use Semae\Pca\Infrastructure\Pncp\Http\RequisicaoHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\RespostaHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Leitura\ConfiguracaoLeituraPncp;
use UnexpectedValueException;

/** Leitor temporario que enumera candidatos sem aplicar a regra de unicidade do fluxo normal. */
final class Sprint94PncpInventoryReader
{
    public function __construct(
        private readonly TransporteHttpPncp $transport,
        private readonly ConfiguracaoTransportePncp $transportConfig,
        private readonly ConfiguracaoLeituraPncp $readConfig
    ) {}

    /**
     * @param string[] $expectedUasgs
     * @return array<int, array{uasg: string, sequencial: int, numeros_item: int[]}>
     */
    public function inventory(
        SessaoPncpAutenticada $session,
        Cnpj $cnpj,
        int $year,
        array $expectedUasgs
    ): array {
        $base = '/v1/orgaos/' . rawurlencode($cnpj->getValor()) . '/pca/' . $year;
        $candidates = [];
        $seen = [];

        $candidateRecords = iterator_to_array(
            $this->pages(
                $session,
                $base . '/consolidado/unidades',
                fn () => $this->proveAnnualAbsence(
                    $session,
                    $cnpj,
                    $year,
                    $expectedUasgs
                )
            ),
            false
        );
        foreach ($candidateRecords as $record) {
            $identity = $this->identity($record, $cnpj, $year);
            $key = $identity['uasg'] . '|' . $identity['sequencial'];
            if (isset($seen[$key])) {
                throw new DomainException('Candidato PNCP duplicado no inventario.');
            }
            $seen[$key] = true;
            $items = [];
            foreach ($this->pages($session, $base . '/' . $identity['sequencial'] . '/itens') as $item) {
                $itemIdentity = $this->identity($item, $cnpj, $year);
                $number = $item['numeroItem'] ?? null;
                if (
                    $itemIdentity !== $identity
                    || !is_int($number)
                    || $number <= 0
                    || isset($items[$number])
                ) {
                    throw new DomainException('Item PNCP inconsistente no inventario.');
                }
                $items[$number] = true;
            }
            $numbers = array_keys($items);
            sort($numbers, SORT_NUMERIC);
            $candidates[] = [
                'uasg' => $identity['uasg'],
                'sequencial' => $identity['sequencial'],
                'numeros_item' => $numbers,
            ];
        }

        usort($candidates, static fn (array $a, array $b): int => [
            $a['uasg'], $a['sequencial'],
        ] <=> [$b['uasg'], $b['sequencial']]);

        return $candidates;
    }

    /** @return iterable<int, array<string, mixed>> */
    private function pages(
        SessaoPncpAutenticada $session,
        string $path,
        ?callable $proveFirstPageAbsence = null
    ): iterable
    {
        $pageSize = $this->readConfig->getTamanhoPagina();
        $fingerprints = [];
        for ($page = 1; $page <= $this->readConfig->getLimiteMaximoPaginas(); $page++) {
            $response = $this->get(
                $session,
                $path . '?pagina=' . $page . '&tamanhoPagina=' . $pageSize
            );
            if ($response->getStatus() === 204) {
                return;
            }
            if ($response->getStatus() === 404 && $page === 1 && $proveFirstPageAbsence !== null) {
                $proveFirstPageAbsence();
                return;
            }
            if (
                $response->getStatus() === 404
                && $page > 1
                && $fingerprints !== []
                && $proveFirstPageAbsence !== null
            ) {
                return;
            }
            if ($response->getStatus() !== 200) {
                throw new UnexpectedValueException('Resposta PNCP inconclusiva no inventario.');
            }
            $records = $this->decodeList($response);
            if (count($records) > $pageSize) {
                throw new DomainException('Pagina PNCP excede o tamanho solicitado.');
            }
            $fingerprint = hash('sha256', json_encode($records, JSON_THROW_ON_ERROR));
            if (isset($fingerprints[$fingerprint])) {
                throw new DomainException('Pagina PNCP repetida no inventario.');
            }
            $fingerprints[$fingerprint] = true;
            foreach ($records as $record) {
                if (!is_array($record) || array_is_list($record)) {
                    throw new UnexpectedValueException('Registro PNCP invalido no inventario.');
                }
                yield $record;
            }
            if (count($records) < $pageSize) {
                return;
            }
        }

        throw new UnexpectedValueException('Limite de paginas PNCP atingido no inventario.');
    }

    /** @param string[] $expectedUasgs */
    private function proveAnnualAbsence(
        SessaoPncpAutenticada $session,
        Cnpj $cnpj,
        int $year,
        array $expectedUasgs
    ): void {
        $expected = [];
        foreach ($expectedUasgs as $uasg) {
            if (!is_string($uasg) || trim($uasg) === '' || isset($expected[trim($uasg)])) {
                throw new DomainException('Escopo de UASGs invalido para comprovar ausencia anual.');
            }
            $expected[trim($uasg)] = 0;
        }
        if ($expected === []) {
            throw new DomainException('Escopo de UASGs vazio para comprovar ausencia anual.');
        }

        $baseOrganization = '/v1/orgaos/' . rawurlencode($cnpj->getValor());
        $unitsResponse = $this->get($session, $baseOrganization . '/unidades');
        if ($unitsResponse->getStatus() !== 200) {
            throw new UnexpectedValueException('Unidades PNCP inconclusivas na comprovacao de ausencia.');
        }
        foreach ($this->decodeList($unitsResponse) as $unit) {
            $organization = is_array($unit) ? ($unit['orgao'] ?? null) : null;
            $uasg = is_array($unit) ? ($unit['codigoUnidade'] ?? null) : null;
            if (
                !is_array($unit)
                || array_is_list($unit)
                || !is_array($organization)
                || array_is_list($organization)
                || !is_string($uasg)
                || trim($uasg) === ''
            ) {
                throw new UnexpectedValueException('Unidade PNCP invalida na comprovacao de ausencia.');
            }
            try {
                $unitCnpj = is_string($organization['cnpj'] ?? null)
                    ? (new Cnpj($organization['cnpj']))->getValor()
                    : null;
            } catch (\InvalidArgumentException) {
                $unitCnpj = null;
            }
            if ($unitCnpj !== $cnpj->getValor()) {
                throw new DomainException('Identidade do orgao divergiu na comprovacao de ausencia.');
            }
            $normalizedUasg = trim($uasg);
            if (array_key_exists($normalizedUasg, $expected)) {
                $expected[$normalizedUasg]++;
            }
        }
        foreach ($expected as $matches) {
            if ($matches !== 1) {
                throw new DomainException('UASG esperada nao foi comprovada de forma univoca.');
            }
        }

        $annualResponse = $this->get($session, $baseOrganization . '/pca/' . $year . '/consolidado');
        if ($annualResponse->getStatus() !== 404) {
            throw new UnexpectedValueException('Ausencia anual PNCP nao foi comprovada.');
        }

    }

    private function get(SessaoPncpAutenticada $session, string $path): RespostaHttpPncp
    {
        return $this->transport->enviar(new RequisicaoHttpPncp(
            'GET',
            $path,
            ['Accept' => 'application/json', 'Authorization' => 'Bearer ' . $session->getTokenOpaco()],
            null,
            $this->transportConfig->getTimeoutLeituraSegundos()
        ));
    }

    /** @return array<int, mixed> */
    private function decodeList(RespostaHttpPncp $response): array
    {
        try {
            $decoded = json_decode(
                $response->getCorpo(),
                true,
                $this->readConfig->getProfundidadeMaximaJson(),
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $failure) {
            throw new UnexpectedValueException('JSON PNCP invalido no inventario.', 0, $failure);
        }
        if (!is_array($decoded) || !array_is_list($decoded)) {
            throw new UnexpectedValueException('Lista PNCP invalida no inventario.');
        }

        return $decoded;
    }

    /** @param array<string, mixed> $record @return array{uasg: string, sequencial: int} */
    private function identity(array $record, Cnpj $expectedCnpj, int $expectedYear): array
    {
        $cnpj = $record['cnpj'] ?? null;
        $year = $record['anoPca'] ?? null;
        $uasg = $record['codigoUnidade'] ?? null;
        $sequence = $record['sequencialPca'] ?? null;
        try {
            $normalizedCnpj = is_string($cnpj) ? (new Cnpj($cnpj))->getValor() : null;
        } catch (\InvalidArgumentException) {
            $normalizedCnpj = null;
        }
        if (
            $normalizedCnpj !== $expectedCnpj->getValor()
            || $year !== $expectedYear
            || !is_string($uasg)
            || trim($uasg) === ''
            || !is_int($sequence)
            || $sequence <= 0
        ) {
            throw new DomainException('Identidade PNCP divergente no inventario.');
        }

        return ['uasg' => trim($uasg), 'sequencial' => $sequence];
    }
}
