<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;

class GroqService
{
    /**
     * Interpreta uma pesquisa em linguagem natural
     * e retorna filtros estruturados.
     *
     * @throws JsonException
     */
    public function analisarPesquisa(string $pesquisa): array
    {
        $apiKey = config('services.groq.key');
        $model = config('services.groq.model');

        if (! $apiKey) {
            throw new RuntimeException(
                'A variável GROQ_API_KEY não foi configurada.'
            );
        }

        if (! $model) {
            throw new RuntimeException(
                'A variável GROQ_MODEL não foi configurada.'
            );
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(30)
            ->retry(2, 500)
            ->post(
                'https://api.groq.com/openai/v1/chat/completions',
                [
                    'model' => $model,

                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => <<<'PROMPT'
Você interpreta pesquisas para um comparador de preços brasileiro.

Sua função é transformar a pesquisa do usuário em filtros estruturados.

Regras:
- Não invente informações.
- Use null quando uma informação não for mencionada.
- Converta valores monetários para números.
- "4 mil reais" deve se tornar 4000.
- Converta memória RAM e armazenamento para gigabytes.
- "1 TB" deve se tornar 1024.
- "2 TB" deve se tornar 2048.
- preco_minimo e preco_maximo devem ser números.
- ram_gb e storage_gb devem ser números inteiros.
- A condição deve ser "novo", "usado" ou null.
- Retorne exclusivamente JSON válido.
- Não utilize Markdown.
- Não coloque o JSON dentro de blocos de código.

Formato obrigatório:

{
    "produto": "string ou null",
    "categoria": "string ou null",
    "marca": "string ou null",
    "modelo": "string ou null",
    "preco_minimo": "número ou null",
    "preco_maximo": "número ou null",
    "ram_gb": "inteiro ou null",
    "storage_gb": "inteiro ou null",
    "processador": "string ou null",
    "condicao": "novo, usado ou null",
    "caracteristicas": []
}
PROMPT,
                        ],
                        [
                            'role' => 'user',
                            'content' => trim($pesquisa),
                        ],
                    ],

                    'temperature' => 0.1,

                    'response_format' => [
                        'type' => 'json_object',
                    ],
                ]
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'Erro da Groq: ' . $response->body()
            );
        }

        $conteudo = $response->json(
            'choices.0.message.content'
        );

        if (! is_string($conteudo) || trim($conteudo) === '') {
            throw new RuntimeException(
                'A Groq não retornou uma resposta válida.'
            );
        }

        $resultado = json_decode(
            $conteudo,
            true,
            flags: JSON_THROW_ON_ERROR
        );

        if (! is_array($resultado)) {
            throw new RuntimeException(
                'A resposta da Groq não contém um JSON válido.'
            );
        }

        /*
         * Garante que todas as chaves sempre existam,
         * mesmo que a IA deixe alguma de fora.
         */
        return array_replace([
            'produto' => null,
            'categoria' => null,
            'marca' => null,
            'modelo' => null,
            'preco_minimo' => null,
            'preco_maximo' => null,
            'ram_gb' => null,
            'storage_gb' => null,
            'processador' => null,
            'condicao' => null,
            'caracteristicas' => [],
        ], $resultado);
    }
}