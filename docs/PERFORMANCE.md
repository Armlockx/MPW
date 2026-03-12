# Performance e cache em produção

## Cache de assets estáticos

Para melhorar o Lighthouse (audit *Use efficient cache lifetimes*) e reduzir tempo de carregamento em visitas repetidas, configure cache longo para os arquivos versionados do Vite.

Os builds geram arquivos com hash no nome (ex.: `build/assets/app-abc123.js`). Esses URLs são estáveis por versão e podem ser cacheados por muito tempo.

### Nginx

Dentro do `server` que serve a aplicação:

```nginx
location /build {
    alias /caminho/para/public/build;
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

### Apache (.htaccess)

Se o documento raiz for `public/`, adicione em `public/.htaccess` (ou no bloco correspondente):

```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType text/css "access plus 1 year"
</IfModule>
```

Para restringir apenas à pasta build:

```apache
<IfModule mod_headers.c>
    <FilesMatch "\.(js|css)$">
        Header set Cache-Control "public, max-age=31536000, immutable"
    </FilesMatch>
</IfModule>
```

### HTML / respostas Inertia

Mantenha **sem** cache longo (ou sem cache) as respostas HTML e as rotas da aplicação, para que o usuário sempre receba o HTML atualizado com os nomes corretos dos assets.

## Fontes

As fontes (Instrument Sans) são carregadas de forma não bloqueante (media="print" + onload) e com `display=swap` via parâmetro na URL do Bunny Fonts.

## Recursos bloqueadores de renderização

- O CSS das fontes foi configurado para não bloquear o primeiro paint (carregamento assíncrono).
- Os scripts do Vite são injetados como `type="module"` e não bloqueiam a análise do HTML.
- O script inline de tema no `<head>` é mínimo e evita flash de tema; mantê-lo é aceitável. Para controle total e menor dependência de rede, é possível self-hostar as fontes e definir `font-display: swap` no `@font-face` em `resources/css/app.css`.

## Análise de bundle

Para inspecionar o tamanho do JavaScript e identificar oportunidades de redução:

```bash
npm run build:analyze
```

Isso gera `build-stats.html` na raiz do projeto (arquivo ignorado pelo Git). Abra no navegador para ver o treemap do bundle.

## Lighthouse CI

O workflow `.github/workflows/lighthouse.yml` roda Lighthouse em modo headless nas rotas principais (`/`, `/login`, `/batches`) e falha o build se:

- **Performance** ou **Acessibilidade** ficarem abaixo de 95.

A configuração está em `lighthouserc.js`. Para exibir os resultados no pull request, instale o [Lighthouse CI GitHub App](https://github.com/apps/lighthouse-ci) e configure o secret `LHCI_GITHUB_APP_TOKEN` no repositório.
