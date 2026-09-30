<?php

namespace App\Services\Collectors;

interface ProductCollectorInterface
{
    /**
     * Pesquisa ofertas em uma fonte externa.
     *
     * @param string $query Pesquisa original do usuário.
     * @param array<string, mixed> $filters Filtros interpretados pela IA.
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(
        string $query,
        array $filters = []
    ): array;
}