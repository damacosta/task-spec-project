# portal-docs

Portal de documentação com duas abas: **Documentation**, escrita à mão em MDX, e
**API reference**, gerada do OpenAPI que o Scramble produz a partir dos controllers.

O conteúdo é versionado junto com o código, em `content/`.

## Escrever uma página

Crie o arquivo e liste o slug dele no `docs.json`. As duas coisas são necessárias: um
`.mdx` que não aparece no `docs.json` não é navegável, não entra no `llms.txt` e responde 404. Isso impede uma página meio escrita de vazar.

```
app-modules/portal-docs/content/
├── docs.json           navegação, cores, âncoras
├── get-started.mdx
├── concepts/arquitetura.mdx
├── flows/documentar-um-endpoint.mdx
└── reference/errors.mdx
```

### Frontmatter

| Campo          | Efeito                                                                                  |
| -------------- | --------------------------------------------------------------------------------------- |
| `title`        | H1 da página, `<title>`, prev/next e `llms.txt`                                         |
| `description`  | subtítulo e descrição no `llms.txt`                                                     |
| `sidebarTitle` | rótulo na barra lateral; não substitui o H1                                             |
| `icon`         | ícone lucide na barra lateral, pelo nome ([lucide.dev/icons](https://lucide.dev/icons)) |
| `mode: wide`   | remove o índice lateral e libera a largura                                              |

O bloco `---` precisa ser a primeira coisa do arquivo. Uma linha em branco antes dele faz
o MDX ler os três traços como linha horizontal, e a página perde o título.

### Armadilhas do MDX que quebram o build

- `{` e `}` no texto viram expressão JavaScript. Escape com `\{` ou reescreva a frase.
- Nunca escreva `# Titulo` no corpo: o H1 vem do `title` do frontmatter.
- Blocos de código dentro de um componente precisam de indentação consistente.

Um erro de MDX aparece no `bun run build`, não em runtime, e derruba o bundle inteiro do
portal. Rode o build antes de abrir PR.

## Componentes

Todos os componentes do Mintlify estão no escopo global, sem `import`. A página
`content/reference/componentes.mdx` exercita cada um e serve de referência viva: se uma
atualização do `@mintlify/components` quebrar um wrapper, o build dela falha.

Nove componentes passam por um wrapper em `resources/js/mdx-components.jsx`, cada um por
um motivo anotado no arquivo. O mais comum: `icon="rocket"` iria para o resolvedor de
ícones da Mintlify por CDN e não renderizaria offline, então é convertido para lucide.

Diagramas usam Mermaid, com a crase dentro da chave:

```mdx
<Mermaid
    chart={`flowchart LR
    A[docs.json] --> B[DocsSite]`}
/>
```

## Navegação

```json
{
    "name": "Sycorax",
    "colors": { "primary": "#7C3AED", "light": "#A78BFA", "dark": "#5B21B6" },
    "navigation": {
        "tabs": [
            {
                "tab": "API reference",
                "openapi": true,
                "groups": [{ "group": "Sobre a API", "pages": ["reference/errors"] }]
            }
        ]
    }
}
```

`openapi: true` acrescenta, **depois** dos grupos escritos à mão, um grupo por tag do
OpenAPI, com uma página por operação. As cores viram variáveis CSS no elemento raiz, então
mudam sem rebuild.

Slugs precisam casar `^[a-z0-9_-]+(/[a-z0-9_-]+)*$`. Qualquer outra coisa é ignorada, o que
é a defesa contra path traversal.

## Documentar a API

A aba de API é lida do código. O guia completo está em
`content/flows/documentar-um-endpoint.mdx`; o resumo:

| Onde                                      | O que vira                                       |
| ----------------------------------------- | ------------------------------------------------ |
| PHPDoc no método do controller            | primeira linha é o título, o resto é a descrição |
| `#[QueryParameter]`, `#[HeaderParameter]` | parâmetros, com exemplo e valor padrão           |
| `#[Response(status:, description:)]`      | descrição da resposta                            |
| PHPDoc por regra no FormRequest           | descrição, `@example` e `@default` de cada campo |
| `max:`, `min:`, `email`, `regex:`         | `maxLength`, `minimum`, `format`, `pattern`      |
| PHPDoc em cada case de um enum            | `x-enumDescriptions`, a descrição por opção      |

Escreva `@example` sempre que puder: sem ele a página mostra um marcador como `<string>`.

> **Autenticação documentada não é autenticação aplicada.** O documento anuncia
> `bearerAuth` através de `DocumentsApiConventions`, mas quem protege a rota é o
> middleware dela, que hoje é o `auth` de sessão. Manter os dois alinhados é trabalho
> manual, e vale reavaliar antes de expor a API para fora.

## Endpoints para modelos de linguagem

| Rota                  | O que devolve                                                |
| --------------------- | ------------------------------------------------------------ |
| `/docs/llms.txt`      | índice por grupo, com título, URL e descrição de cada página |
| `/docs/llms-full.txt` | todas as páginas concatenadas, endpoints inclusive           |
| `/docs/{slug}.md`     | uma página como markdown cru                                 |

O menu "Copiar página" em cada página consome o `.md` correspondente.

## Configuração

`config/portal-docs.php`:

| Chave           | Para quê                                                               |
| --------------- | ---------------------------------------------------------------------- |
| `prefix`        | onde o portal mora; `docs` por padrão                                  |
| `content_path`  | onde estão o `docs.json` e os `.mdx`; os testes apontam para a fixture |
| `gate`          | habilidade verificada fora de `local`; vazio publica para todos        |
| `cache.version` | hash do deploy, para não varrer mtimes a cada requisição               |
| `openapi.api`   | qual API do Scramble gerar, quando houver mais de uma                  |
| `contextual`    | entradas do menu "Copiar página"                                       |

## Deploy

```bash
php artisan scramble:cache      # grava o documento OpenAPI; o portal só o lê
php artisan portal-docs:cache   # aquece navegação e documento resolvido
bun run build                   # falha se algum .mdx estiver inválido
```

Em produção, defina `PORTAL_DOCS_VERSION` com o hash do deploy. Sem isso, a chave de cache
é calculada a partir dos mtimes do conteúdo, o que custa um `glob` por requisição mas faz
uma página editada aparecer sem `cache:clear`.

## Decisões que não são óbvias

- **O portal tem bundle de CSS e JS separado do app.** O `styles.css` do
  `@mintlify/components` é um Tailwind pré-compilado com preflight e paleta próprios, e
  misturá-lo com o tema do Filament gera colisões que nenhum dos dois lados controla.
  A ordem dos `@import` em `resources/css/portal-docs.css` é carregada de significado, e
  cada override no fim do arquivo existe por causa dela.
- **O SSR fica desligado no prefixo do portal.** O Mintlify destaca código num worker e o
  Mermaid precisa de DOM.
- **`Scramble::ignoreDefaultRoutes()` é chamado no `register()`,** não no `boot()`. O
  Scramble expõe `GET docs/api` por padrão e lê essa flag no boot dele, que roda antes do
  boot deste módulo: a partir do boot a chamada chega depois da rota já ter sido
  substituída, em silêncio.
- **O Scramble é usado só como gerador de array OpenAPI.** A interface `SpecLoader` é o
  contrato, e é ela que permite a suíte de testes rodar sem análise estática.

## Testes

```bash
php artisan test --compact app-modules/portal-docs/tests
```

As fixtures em `tests/Fixtures/` são independentes do conteúdo real, então editar uma
página nunca quebra a suíte. A exceção é `ScrambleSpecTest`, que roda o Scramble de
verdade contra a API do host: é ele que pega quando uma anotação para de produzir o que a
página espera.
