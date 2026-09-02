<?php

declare(strict_types=1);

namespace Semae\Pca\Composition;

use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Semae\Pca\Infrastructure\Oracle\Auditoria\OracleRegistroAuditoriaAdapter;
use Semae\Pca\Infrastructure\Oracle\Contexto\OracleContextoUsuarioAdapter;
use Semae\Pca\Infrastructure\Oracle\EstadoOperacional\OracleEstadoOperacionalPublicacaoAdapter;
use Semae\Pca\Infrastructure\Oracle\Estrategia\OracleEstrategiaPublicacaoAdapter;
use Semae\Pca\Infrastructure\Oracle\Plano\OraclePlanoRepository;
use Semae\Pca\Infrastructure\Oracle\Transacao\OracleUnidadeDeTrabalhoAdapter;

final class ComposicaoOraclePublicacaoPca
{
    private readonly OracleContextoUsuarioAdapter $contextoUsuario;
    private readonly OraclePlanoRepository $planoRepository;
    private readonly OracleEstrategiaPublicacaoAdapter $estrategiaPublicacao;
    private readonly OracleUnidadeDeTrabalhoAdapter $unidadeDeTrabalho;
    private readonly OracleEstadoOperacionalPublicacaoAdapter $estadoOperacional;
    private readonly OracleRegistroAuditoriaAdapter $registroAuditoria;

    public function __construct(
        ConexaoAdodbEmprestada $conexao,
        PerfilPublicacaoPca $perfil,
        string $loginAutenticado
    ) {
        $this->contextoUsuario = new OracleContextoUsuarioAdapter(
            $conexao,
            $perfil->getRepresentantes(),
            $perfil->getLotacoesUsuario(),
            $loginAutenticado
        );
        $this->planoRepository = new OraclePlanoRepository(
            $conexao,
            $perfil->getPlanos(),
            $perfil->getDemandas(),
            $perfil->getUnidadesOrcamentarias(),
            $perfil->getLotacoes(),
            $perfil->getFusoOperacional()
        );
        $this->estrategiaPublicacao = new OracleEstrategiaPublicacaoAdapter(
            $conexao,
            $perfil->getConfiguracaoEstrategia(),
            $perfil->getPlanosEstrategia(),
            $perfil->getUnidadesOrcamentarias()
        );
        $this->unidadeDeTrabalho = new OracleUnidadeDeTrabalhoAdapter(
            $conexao,
            $perfil->getControlesConcorrencia()
        );
        $this->estadoOperacional = new OracleEstadoOperacionalPublicacaoAdapter(
            $conexao,
            $perfil->getEstadoOperacional()
        );
        $this->registroAuditoria = new OracleRegistroAuditoriaAdapter(
            $conexao,
            $perfil->getHistoricos()
        );
    }

    public function getContextoUsuario(): OracleContextoUsuarioAdapter
    {
        return $this->contextoUsuario;
    }

    public function getPlanoRepository(): OraclePlanoRepository
    {
        return $this->planoRepository;
    }

    public function getEstrategiaPublicacao(): OracleEstrategiaPublicacaoAdapter
    {
        return $this->estrategiaPublicacao;
    }

    public function getUnidadeDeTrabalho(): OracleUnidadeDeTrabalhoAdapter
    {
        return $this->unidadeDeTrabalho;
    }

    public function getEstadoOperacional(): OracleEstadoOperacionalPublicacaoAdapter
    {
        return $this->estadoOperacional;
    }

    public function getRegistroAuditoria(): OracleRegistroAuditoriaAdapter
    {
        return $this->registroAuditoria;
    }
}
