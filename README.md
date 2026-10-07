# CotaSmart

O **CotaSmart** é uma plataforma web para pesquisa, comparação e acompanhamento de preços de produtos.

A aplicação centraliza ofertas vindas de diferentes fontes, mantém histórico de preços e permite que o usuário analise se uma oferta realmente está barata com base em dados anteriores.

O projeto está sendo desenvolvido em **Laravel** e utiliza inteligência artificial para interpretar pesquisas feitas em linguagem natural.

---

## Sobre o projeto

Encontrar o menor preço de um produto nem sempre significa encontrar uma boa oferta.

O CotaSmart foi criado com a proposta de reunir informações de diferentes lojas, organizar o histórico de preços e facilitar a comparação entre produtos, vendedores e ofertas.

O usuário pode realizar pesquisas como:

> Quero um notebook Dell com 16GB de RAM até R$ 4.000

A aplicação interpreta a pesquisa e transforma a frase em filtros estruturados, como:

```json
{
    "produto": "notebook",
    "marca": "Dell",
    "preco_maximo": 4000,
    "ram_gb": 16
}
