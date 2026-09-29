<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAIService
{
    public function analisarPesquisa(string $pesquisa): array
    {
        $response = Http::withToken(config('services.openai.key'))
            ->acceptJson()
            ->timeout(30)
            ->post('https://api.openai.com/v1/responses', [
                'model' => config('services.openai.model'),

                'instructions' => <<<'PROMPT'
Você trabalha em um comparador de preços.

Analise a pesquisa enviada pelo usuário e identifique:
- produto procurado;
- categoria;
- marca;
- modelo;
- preço máximo;
- características desejadas.

Não invente informações.
Responda somente em JSON válido, sem Markdown.
PROMPT,

                'input' => $pesquisa,
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Erro da OpenAI: ' . $response->body()
            );
        }

        $data = $response->json();

        $texto = collect($data['output'] ?? [])
            ->flatMap(fn (array $item) => $item['content'] ?? [])
            ->firstWhere('type', 'output_text');

        if (! isset($texto['text'])) {
            throw new RuntimeException(
                'A OpenAI não retornou um texto válido.'
            );
        }

        $resultado = json_decode($texto['text'], true);

        if (! is_array($resultado)) {
            throw new RuntimeException(
                'A resposta da OpenAI não contém um JSON válido.'
            );
        }

        return $resultado;
    }
}