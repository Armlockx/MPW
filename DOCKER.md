# Docker (Laravel Sail) - MPW

O Sail ja inclui **FFmpeg** e **Redis** na imagem. O `compose.yaml` esta configurado para Redis.

## Pre-requisitos

- Docker Desktop instalado e rodando
- Backend WSL2 no Docker Desktop (recomendado)
- Git Bash ou WSL para rodar o script `sail` (no Windows)

## 1. Configurar .env para Sail

No seu `.env`, ajuste para uso com Docker:

```env
REDIS_HOST=redis
QUEUE_CONNECTION=redis
FFMPEG_BINARIES=ffmpeg
```

Para WWWGROUP/WWWUSER (obrigatorio no Linux/WSL):

```env
WWWGROUP=1000
WWWUSER=1000
```

No Windows, essas variaveis costumam ser ignoradas ou podem ser omitidas.

## 2. Subir os containers

**PowerShell (Windows):**

```powershell
.\vendor\bin\sail.bat up -d
```

**Git Bash / WSL / Linux / Mac:**

```bash
./vendor/bin/sail up -d
```

Ou via Composer (se tiver bash):

```bash
composer sail up -d
```

## 3. Instalar dependencias e migrar

```powershell
.\vendor\bin\sail.bat composer install
.\vendor\bin\sail.bat artisan key:generate
.\vendor\bin\sail.bat artisan migrate
.\vendor\bin\sail.bat npm install
.\vendor\bin\sail.bat npm run build
```

Se o banco for SQLite, crie o arquivo antes de migrar:

```powershell
.\vendor\bin\sail.bat exec laravel.test touch database/database.sqlite
.\vendor\bin\sail.bat artisan migrate
```

## 4. Rodar o ambiente de desenvolvimento

Abra 3 terminais:

**Terminal 1 - Containers e servidor PHP:**

```powershell
.\vendor\bin\sail.bat up
```

**Terminal 2 - Vite:**

```powershell
.\vendor\bin\sail.bat npm run dev
```

**Terminal 3 - Fila de jobs:**

```powershell
.\vendor\bin\sail.bat artisan queue:work redis --tries=1 --timeout=600
```

Acesse: http://localhost (ou a porta definida em APP_PORT no .env).

## 5. Comandos uteis

| Acao | Comando      |
|------|--------------|
| Parar containers    | `.\vendor\bin\sail.bat down` |
| Ver logs            | `.\vendor\bin\sail.bat logs -f` |
| Entrar no container | `.\vendor\bin\sail.bat shell` |
| Executar artisan    | `.\vendor\bin\sail.bat artisan migrate` |
| Executar composer   | `.\vendor\bin\sail.bat composer install` |
| Executar npm        | `.\vendor\bin\sail.bat npm run build` |

## 6. Horizon (opcional, so no Linux/WSL)

Dentro do container, o Horizon funciona (extensoes pcntl/posix presentes):

```powershell
composer require laravel/horizon
.\vendor\bin\sail.bat artisan horizon:install
.\vendor\bin\sail.bat artisan horizon
```

Em seguida, use `horizon` em vez de `queue:work`.

## Observacoes

- **FFmpeg** esta incluso na imagem Sail 8.5.
- **Redis** esta no `compose.yaml`; o Laravel se conecta via `REDIS_HOST=redis`.
- **Storage** (videos, watermarks) fica no volume montado e e persistido.
- No Windows, use sempre `.\vendor\bin\sail.bat` em vez de `./vendor/bin/sail`.
