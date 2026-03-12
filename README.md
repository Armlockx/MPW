# MPW

Aplicação web para **processamento em lote de vídeos** com marca d'água (watermark), redimensionamento e ajuste de velocidade. Usuários autenticados criam lotes, fazem upload de vídeos, configuram marca d'água (imagem ou texto) e processam em background via filas (Redis/Horizon).

## Stack

- **Backend:** Laravel 12, PHP 8.2+
- **Frontend:** Vue 3, Inertia.js, Tailwind CSS 4, Vite 7
- **Auth:** Laravel Fortify (login, registro, 2FA, reset de senha, verificação de email)
- **Filas:** Laravel Horizon + Redis
- **Processamento de vídeo:** FFmpeg (pbmedia/laravel-ffmpeg)

## Requisitos

- PHP 8.2+
- Composer
- Node.js 18+
- SQLite, MySQL ou PostgreSQL
- Redis (para filas; opcional com `QUEUE_CONNECTION=database`)
- FFmpeg (para processar vídeos)

## Instalação

```bash
git clone https://github.com/Armlockx/MPW
cd mpw

composer install
cp .env.example .env
php artisan key:generate

# SQLite (padrão)
touch database/database.sqlite

php artisan migrate
npm install
npm run build
```

## Configuração do .env

Para desenvolvimento local com SQLite, o `.env.example` já cobre o básico. Para usar Redis e filas em background:

```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_CLIENT=predis
```

## Rodar em desenvolvimento

```bash
composer dev
```

Isso sobe o servidor PHP, o Vite e o worker de fila em paralelo.

Ou manualmente em 3 terminais:

```bash
php artisan serve
php artisan queue:work --tries=1 --timeout=600
npm run dev
```

## Docker (Sail)

Para rodar com [Laravel Sail](https://laravel.com/docs/sail), siga as instruções em **[DOCKER.md](DOCKER.md)**. O Sail inclui Redis e FFmpeg na imagem.

## Comandos úteis

| Comando | Descrição |
|---------|-----------|
| `composer dev` | Servidor + Vite + fila (dev) |
| `composer dev:ssr` | Com Inertia SSR e logs |
| `composer test` | Testes (lint + Pest) |
| `composer ci:check` | Lint, format, types e testes |
| `composer lint` | Pint (formatação PHP) |
| `npm run build` | Build de produção |
| `npm run build:analyze` | Análise de bundle (build-stats.html) |

## Estrutura principal

- **Lotes (batches):** CRUD de lotes de processamento
- **Vídeos:** Upload por lote, processamento assíncrono via `ProcessVideoJob`
- **Watermark:** Imagem ou texto, posição, opacidade, escala
- **Downloads:** Vídeo individual ou ZIP do lote

## Testes

```bash
composer test
# ou
./vendor/bin/pest
```

Os testes usam SQLite em memória (`:memory:`).

## Qualidade e CI

- **Laravel Pint** para formatação PHP
- **ESLint + Prettier** para JavaScript/Vue
- **Vue TypeScript** com checagem de tipos
- **Lighthouse CI** no GitHub Actions (Performance e Acessibilidade ≥ 95)

O workflow `.github/workflows/lighthouse.yml` roda em pushes/PRs para `main`, `master` e `develop`. Para anotações no PR, configure o [Lighthouse CI GitHub App](https://github.com/apps/lighthouse-ci) e o secret `LHCI_GITHUB_APP_TOKEN`.

## Documentação adicional

- [DOCKER.md](DOCKER.md) – Configuração com Laravel Sail
- [docs/PERFORMANCE.md](docs/PERFORMANCE.md) – Cache de assets, fontes, Lighthouse
- [docs/ux-a11y.md](docs/ux-a11y.md) – UX e acessibilidade

## Licença

MIT
