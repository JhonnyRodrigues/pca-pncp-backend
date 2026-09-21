<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Oracle\Auditoria;

use Semae\Pca\Application\Ports\RegistroAuditoriaPort;
use Semae\Pca\Application\Results\ResultadoPersistenciaLocal;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Application\ValueObjects\IntencaoRegistroHistorico;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Throwable;

final class OracleRegistroAuditoriaAdapter implements RegistroAuditoriaPort
{
    public function __construct(
        private readonly ConexaoAdodbEmprestada $conexao,
        private readonly MapeamentoHistoricosOracle $mapeamento
    ) {}

    public function registrar(
        IntencaoRegistroHistorico $intencao
    ): ResultadoPersistenciaLocal {
        if (!$this->conexao->estaEmTransacao()) {
            return ResultadoPersistenciaLocal::falhaSistemica(new FalhaSegura(
                'REGISTRO_AUDITORIA_EXIGE_TRANSACAO',
                'O histórico somente pode ser registrado em uma transação ativa.'
            ));
        }

        if (!$this->possuiDadosValidos($intencao)) {
            return ResultadoPersistenciaLocal::falhaSistemica(new FalhaSegura(
                'DADOS_AUDITORIA_INVALIDOS',
                'A intenção de auditoria possui autoria ou conteúdo inválido.'
            ));
        }

        try {
            $this->conexao->executar(
                $this->sqlRegistro(),
                [
                    $intencao->getProcedimento()->value,
                    $intencao->getMensagem(),
                    $intencao->getRepresentante()->getId(),
                    $intencao->getLotacao()->getId(),
                ]
            );

            return ResultadoPersistenciaLocal::concluida();
        } catch (Throwable) {
            return ResultadoPersistenciaLocal::falhaSistemica(new FalhaSegura(
                'REGISTRO_AUDITORIA_FALHOU',
                'Não foi possível registrar o histórico de negócio.'
            ));
        }
    }

    private function sqlRegistro(): string
    {
        $tabela = $this->mapeamento->getTabelaQualificada();
        $instante = $this->mapeamento->getColunaInstante();
        $procedimento = $this->mapeamento->getColunaProcedimento();
        $mensagem = $this->mapeamento->getColunaMensagem();
        $representante = $this->mapeamento->getColunaRepresentante();
        $lotacao = $this->mapeamento->getColunaLotacao();

        return "INSERT INTO {$tabela} (
            {$instante}, 
            {$procedimento}, 
            {$mensagem}, 
            {$representante}, 
            {$lotacao}
        )" 
        . ' VALUES (SYSDATE, ?, ?, ?, ?)';
    }

    private function possuiDadosValidos(IntencaoRegistroHistorico $intencao): bool
    {
        return trim($intencao->getMensagem()) !== ''
            && $intencao->getRepresentante()->getId() > 0
            && trim($intencao->getRepresentante()->getNome()) !== ''
            && $intencao->getLotacao()->getId() > 0
            && trim($intencao->getLotacao()->getNome()) !== '';
    }
}
