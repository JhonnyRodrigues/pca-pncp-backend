<?php
namespace Semae\Pca\Infrastructure\Client;

# Classe responsável por construir o payload para a requisição de publicação no PNCP.
# Ela conhece o formato exigido pelo PNCP, mas o Plano não conhece JSON nem HTTP.