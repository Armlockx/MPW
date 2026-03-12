# UX e acessibilidade

Guidelines para manter boa experiência de uso e pontuação de acessibilidade (Lighthouse e leitores de tela).

## Botões e ícones

- Todo **botão que contém apenas um ícone** deve ter nome acessível:
  - Use a prop `aria-label` no componente `Button` (ex.: `aria-label="Excluir lote"`).
  - Ou inclua texto visível apenas para leitores de tela com a classe `sr-only`.
- Priorize rótulos em português para ações destrutivas (excluir, remover, cancelar) e navegação (voltar, abrir menu).

## Headings

- Use **apenas um `h1` por página** (geralmente o título principal da página Inertia).
- Não pule níveis: depois de `h1` use `h2` para seções; dentro delas use `h3`.
- Seções que não têm título visível podem usar um `h2` com classe `sr-only` para manter a hierarquia (ex.: "Lista de lotes" antes de cards).

## Meta tags e SEO

- Cada página deve definir `<Head title="...">` descritivo.
- Para páginas importantes, adicione meta description via `<Head>`:
  ```vue
  <Head title="Lotes">
    <meta name="description" content="Gerencie seus lotes de processamento de vídeos." />
  </Head>
  ```
- A descrição padrão é definida em `resources/views/app.blade.php`; as páginas podem sobrescrevê-la com o bloco acima.

## Checklist para novas telas

- [ ] Botões apenas com ícone têm `aria-label` (ou texto `sr-only`).
- [ ] A página tem um único `h1` e os demais headings seguem a ordem (h2, h3, sem pular).
- [ ] A página usa `<Head title="...">` e, se relevante, `<meta name="description">`.
