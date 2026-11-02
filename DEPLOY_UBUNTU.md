# Deploy Ubuntu (Hostinger VPS) — CardioPrenatal / sistemapretriagem

Documento de **análise técnica + guia de deploy futuro**.  
**Nesta etapa não há conexão SSH, instalação na VPS nem alteração de infraestrutura.**

Use este arquivo como checklist quando o Cursor conectar via SSH à VPS: inspecionar o que já existe, instalar só o que faltar, publicar e validar.

---

## 0. Informações que você precisa fornecer depois (não inventar)

Antes do deploy real, confirme e guarde (fora do Git):

| Item | Status |
|------|--------|
| IP da VPS + usuário SSH + chave/senha | Pendente (Hostinger) |
| Domínio (ex.: `app.seudominio.com`) e DNS apontando para a VPS | Pendente |
| Credenciais MySQL de produção (DB, user, password) | Pendente |
| `APP_KEY` Laravel (gerar na VPS ou reutilizar com cuidado) | Pendente |
| SMTP (`MAIL_*`) se e-mail ativo | Pendente |
| `GEMINI_API_KEY` / modelo | Pendente |
| `WAHA_*` e segredo de webhook | Pendente |
| Se WhatsApp completo: deploy também do repo **`apiwhatsapp`** (n8n + WAHA) + Supabase | Pendente |
| Artefatos ML: `python_api/models/model.pkl` + `model_meta.json` (e métricas, se usadas) | Confirmar presença no servidor |
| Porta/host da API ML em produção (`PYTHON_API_URL`) | Pendente |

---

## 1. Resumo da arquitetura identificada

### 1.1 Repositório principal (`sistemapretriagem`)

Aplicação **CardioPrenatal**: cadastro de gestantes, consultas, dashboard, análises de risco (CatBoost via API Python), WhatsApp (envio via WAHA), interpretação clínica (Gemini / mock no FastAPI), filas e agendamentos Laravel.

```text
[Browser / HTTPS]
       │
       ▼
[Nginx :80/443] ──► [PHP-FPM Laravel 12] ──► [MySQL 8.4]
       │                    │
       │                    ├── queue:work (jobs: análise ML, interpretação…)
       │                    └── schedule:work (reprocess + lembretes WhatsApp)
       │
       └── HTTP ──► [FastAPI Python :8010]  (CatBoost /interpretar — sem MySQL)
                         │
                         └── models/model.pkl (+ meta)

Integrações externas (HTTP):
  - WAHA (WhatsApp)     default :3000
  - Google Gemini API
  - SMTP (ex. Gmail)
  - (opcional) AWS S3 — vars no .env, disk padrão = local
```

### 1.2 Stack real (arquivos do projeto)

| Camada | Tecnologia | Evidência |
|--------|------------|-----------|
| Backend | **PHP ^8.2**, **Laravel ^12** | `composer.json`, `Dockerfile` (`php:8.3-fpm-bookworm`) |
| Frontend | Blade + **Vite ^7** + **Tailwind ^4** | `package.json`, `resources/` |
| Pacotes PHP | Composer 2 | `Dockerfile` copia `composer:2` |
| Pacotes JS | npm | `package.json` |
| Banco | **MySQL 8.4** | `docker-compose.yml` |
| Fila | **database** (tabelas Laravel) | `.env.example` → `QUEUE_CONNECTION=database` |
| Cache / sessão | **database** | `.env.example` |
| Redis | Declarado no `.env.example`, **não usado** no Compose atual | Sem serviço Redis no `docker-compose.yml` |
| RabbitMQ / Horizon | **Não presentes** | — |
| ML | **Python 3.12**, FastAPI, Uvicorn, CatBoost | `python_api/Dockerfile`, `requirements.txt` |
| Orquestração local/prod sugerida | **Docker Compose** | `docker-compose.yml` |
| Proxy app | Nginx 1.27-alpine (container) | porta host **8000→80** em dev |
| Processos longos | containers `queue` + `scheduler` | `queue:work`, `schedule:work` |

### 1.3 Serviços Compose deste repo

| Container | Função | Porta (host→container) |
|-----------|--------|-------------------------|
| `pretriagem-app` | PHP-FPM | 9000 (só rede interna) |
| `pretriagem-nginx` | HTTP → `public/` | **8000:80** (dev) |
| `pretriagem-mysql` | MySQL 8.4 | **3307:3306** (dev) |
| `pretriagem-ml` | FastAPI ML | **8010** (no Compose, publish de porta comentado; Laravel aponta `PYTHON_API_URL`) |
| `pretriagem-queue` | `php artisan queue:work` | — |
| `pretriagem-scheduler` | `php artisan schedule:work` | — |

Volume persistente Compose: `pretriagem_mysql_data`.

### 1.4 Microsserviço ML (`python_api/`)

- Entrypoint: `uvicorn app.main:app --host 0.0.0.0 --port 8010`
- Dependências: FastAPI, uvicorn, pydantic, numpy, pandas, scikit-learn, **catboost**, joblib
- Sistema: `libgomp1` (Dockerfile)
- Env típicos: `MODEL_PATH`, `METRICS_PATH`, `MODEL_META_PATH`, `LOG_LEVEL`
- **Sem banco próprio** — só inferência HTTP

> O `docker-compose.yml` monta também `../AnalisePython/data` (repo irmão). Em produção, decida se as métricas/dados vêm só de `python_api/data` ou se esse path precisa existir na VPS.

### 1.5 Integração WhatsApp (repo irmão `apiwhatsapp`)

Não faz parte deste repositório, mas o app **depende** dela em produção completa:

| Serviço | Porta típica | Papel |
|---------|--------------|--------|
| WAHA | 3000 | Sessão WhatsApp / `sendText` / webhooks |
| n8n | 5678 | Orquestra RAG Laravel → Gemini → WAHA |
| Supabase | SaaS | Sessões/histórico do fluxo GestRisk |

Laravel: `WAHA_BASE_URL`, `WAHA_API_KEY`, `WAHA_WEBHOOK_SECRET`, rotas `/api/v1/n8n/whatsapp/*`.

### 1.6 Agendamentos (`routes/console.php`)

- `analises:reprocess --only-missing` — cron `5,20,35,50 * * * *`
- `whatsapp:lembretes-agendados` — diário `08:00` (timezone `config('app.timezone')`; default em `config/app.php` = **UTC** — alinhar com `America/Sao_Paulo` em produção se desejado)

### 1.7 Armazenamento

- `FILESYSTEM_DISK=local` → `storage/app`
- Uploads/importações CSV e logs em `storage/`
- Nginx: `client_max_body_size 64M` (`docker/nginx/default.conf`)
- Assets front: build Vite → `public/build`

### 1.8 O que **não** é necessário no layout atual

- Redis (opcional no futuro)
- RabbitMQ
- PM2 (Node só para build; runtime PHP/Python/Docker)
- Node em produção além de `npm run build` no pipeline/deploy

---

## 2. Requisitos mínimos recomendados (VPS)

| Recurso | Mínimo sugerido | Motivo |
|---------|-----------------|--------|
| OS | Ubuntu 22.04 ou 24.04 LTS | Hostinger comum |
| CPU | 2 vCPU | PHP + MySQL + CatBoost |
| RAM | **4 GB** (ideal **8 GB** se WAHA+n8n na mesma VPS) | CatBoost + MySQL + n8n |
| Disco | **40 GB+** SSD | MySQL + modelos + logs + mídia WAHA |
| Rede | IP público + portas 80/443 | HTTPS |

Se WhatsApp (WAHA+n8n) for na **mesma** VPS, prefira 8 GB e monitore memória.

---

## 3. Tecnologias e versões necessárias

### 3.1 Caminho recomendado: Docker (alinhado ao projeto)

Na VPS:

- Docker Engine (versão atual estável do Ubuntu)
- Docker Compose plugin (`docker compose`)
- Git
- (Opcional no host) Nginx + Certbot **só** como reverse proxy TLS na frente do Compose

Imagens usadas pelo projeto:

- `php:8.3-fpm-bookworm` (build)
- `nginx:1.27-alpine`
- `mysql:8.4`
- `python:3.12-slim` (ML)

### 3.2 Alternativa bare-metal (não preferencial)

Só se não usar Docker:

- PHP 8.3 + extensões: `pdo_mysql`, `mbstring`, `exif`, `pcntl`, `bcmath`, `gd`, `zip`, `intl`, `opcache`
- Composer 2
- Node.js 20+ LTS (apenas build)
- Nginx
- MySQL 8.4
- Python 3.12 + venv + deps de `python_api/requirements.txt`
- systemd para `php-fpm`, `queue:work`, `schedule:work`, `uvicorn`

Este documento detalha sobretudo o **caminho Docker**.

---

## 4. Dependências a instalar na VPS (host)

```bash
# Atualização base
sudo apt update && sudo apt upgrade -y

# Pacotes úteis
sudo apt install -y ca-certificates curl git ufw fail2ban

# Docker (seguir doc oficial Ubuntu; resumo)
sudo apt install -y docker.io docker-compose-plugin
sudo systemctl enable --now docker
sudo usermod -aG docker $USER
# Relogue o SSH após usermod

# Proxy TLS no host (recomendado)
sudo apt install -y nginx certbot python3-certbot-nginx
```

Confirmar:

```bash
docker --version
docker compose version
git --version
nginx -v
```

---

## 5. Sequência correta de instalação (checklist futuro via SSH)

1. Inspecionar VPS (OS, RAM, disco, portas livres).
2. Instalar Docker + Compose + UFW + (opcional) Fail2ban.
3. Clonar `sistemapretriagem` (e, se for o caso, `apiwhatsapp` / garantir modelos ML).
4. Criar `.env` de produção a partir de `.env.docker.example` / `.env.example`.
5. Garantir `python_api/models/model.pkl` e `model_meta.json` presentes.
6. `docker compose build` + `docker compose up -d`.
7. Rodar migrations (`php artisan migrate --force` no container `app`).
8. `npm ci && npm run build` (no host com Node, ou num stage de CI; copiar `public/build`).
9. Configurar Nginx host (80/443) → `127.0.0.1:8000` (ou ajuste de porta).
10. Certbot SSL.
11. Abrir firewall só 22/80/443 (e o necessário para WAHA/n8n se expostos).
12. Validar healthchecks e fluxos (login, análise ML, WhatsApp se aplicável).
13. Documentar backups MySQL + volumes.

---

## 6. Configuração do banco de dados

### 6.1 Via Compose (padrão do projeto)

Variáveis (ver `.env.docker.example`):

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=cardioprenatal
DB_USERNAME=pretriagem
DB_PASSWORD=<SENHA_FORTE>
DB_ROOT_PASSWORD=<SENHA_ROOT_FORTE>
```

Em produção:

- **Não** publicar `3307:3306` na internet (remova o mapeamento `ports` do serviço `mysql` no Compose de produção ou bind em `127.0.0.1` apenas se precisar de túnel SSH).
- Volume `pretriagem_mysql_data` deve entrar no plano de backup.

### 6.2 Migrations

```bash
docker compose exec app php artisan migrate --force
```

### 6.3 Backup / restore (referência)

```bash
# Backup
docker compose exec -T mysql mysqldump -u root -p"$DB_ROOT_PASSWORD" cardioprenatal > backup-$(date +%F).sql

# Restore (cuidado)
docker compose exec -T mysql mysql -u root -p"$DB_ROOT_PASSWORD" cardioprenatal < backup-YYYY-MM-DD.sql
```

---

## 7. Serviços auxiliares

### 7.1 Fila e scheduler (obrigatórios neste projeto)

Já definidos no Compose:

- `queue` → `php artisan queue:work --sleep=3 --tries=3 --timeout=120 --max-time=3600`
- `scheduler` → `php artisan schedule:work`

Sem eles: análises ML / interpretação / lembretes WhatsApp **não** rodam de forma confiável.

### 7.2 Redis

**Não instalar** a menos que mude `.env` para `QUEUE_CONNECTION=redis` / `CACHE_STORE=redis`. Layout atual = database.

### 7.3 API ML

- Serviço `ml` no Compose.
- `.env` Docker típico: `PYTHON_API_URL=http://ml:8010`
- Se a API exigir auth: `PYTHON_API_KEY` igual à chave do serviço Python.
- `PYTHON_API_VERIFY_SSL=true` em HTTPS público.

### 7.4 WhatsApp (opcional na mesma VPS ou outra)

Repo `apiwhatsapp`:

- WAHA `:3000`
- n8n `:5678`
- Env: `LARAVEL_APP_URL=https://seu-dominio`, `WAHA_WEBHOOK_SECRET` alinhado ao Laravel, `GEMINI_*`, Supabase

Expor WAHA/n8n na internet exige autenticação forte, HTTPS e restrição de firewall.

---

## 8. Variáveis de ambiente (produção)

Partir de `.env.docker.example` + trechos de `.env.example`.

### 8.1 Obrigatórias / críticas

```env
APP_NAME=CardioPrenatal
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...          # php artisan key:generate
APP_URL=https://seu-dominio
APP_LOCALE=pt_BR

# FORCE_HTTPS=true
# SESSION_SECURE_COOKIE=true

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=cardioprenatal
DB_USERNAME=...
DB_PASSWORD=...

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
FILESYSTEM_DISK=local

PYTHON_API_URL=http://ml:8010
PYTHON_API_KEY=
PYTHON_API_VERIFY_SSL=true

INTERPRETACAO_GEMINI_ENABLED=true   # ou false se só mock/CatBoost

LLM_PROVIDER=gemini
GEMINI_API_KEY=
GEMINI_MODEL=gemini-3.5-flash-lite
GEMINI_HTTP_TIMEOUT=25

WAHA_BASE_URL=http://waha:3000      # ou URL pública/interna real
WAHA_API_KEY=
WAHA_SESSION_NAME=default
WAHA_NO_API_KEY=false               # produção: false + chave
WAHA_WEBHOOK_SECRET=                # mesmo valor no n8n
WHATSAPP_AUTO_LEMBRETE=false
WHATSAPP_IA_PAUSA_MINUTOS=240

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=465
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="Cardioprenatal"
MAIL_STANDBY=true                   # true = e-mails em standby
```

### 8.2 Após criar `.env`

```bash
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

---

## 9. Nginx e reverse proxy

### 9.1 Dentro do Compose

Arquivo `docker/nginx/default.conf`:

- `root /var/www/html/public`
- FastCGI → `app:9000`
- `client_max_body_size 64M`

Em produção no Compose, mapeie preferencialmente:

```yaml
ports:
  - "127.0.0.1:8000:80"
```

para o Nginx do host fazer TLS.

### 9.2 Nginx no host (TLS)

Exemplo de site (ajuste domínio e porta):

```nginx
server {
    listen 80;
    server_name SEU_DOMINIO;

    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        client_max_body_size 64M;
        proxy_read_timeout 300;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/cardioprenatal /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

---

## 10. Gerenciamento de processos

Com Docker:

```bash
cd /caminho/sistemapretriagem
docker compose up -d
docker compose ps
docker compose restart app queue scheduler ml nginx
```

`restart: unless-stopped` já está nos serviços Compose → sobem após reboot do host (Docker enabled).

Sem Docker (referência): unidades systemd para `php-fpm`, `cardioprenatal-queue`, `cardioprenatal-scheduler`, `cardioprenatal-ml` (uvicorn).

---

## 11. Domínio e SSL

```bash
# DNS A/AAAA do domínio → IP da VPS (painel Hostinger)

sudo certbot --nginx -d SEU_DOMINIO
# Renovação automática: certbot.timer
sudo systemctl status certbot.timer
```

No `.env`:

```env
APP_URL=https://SEU_DOMINIO
FORCE_HTTPS=true
SESSION_SECURE_COOKIE=true
```

Recarregar config Laravel após mudança.

---

## 12. Firewall e segurança

```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
# NÃO abrir 3306/3307, 9000, 8010, 5678, 3000 publicamente sem necessidade
sudo ufw enable
sudo ufw status
```

Checklist:

- [ ] `APP_DEBUG=false`
- [ ] Senhas MySQL fortes; MySQL só na rede Docker
- [ ] `WAHA_NO_API_KEY=false` + API key em produção
- [ ] `WAHA_WEBHOOK_SECRET` preenchido
- [ ] Secrets só no `.env` (não commitado)
- [ ] Permissões: `storage/` e `bootstrap/cache` graváveis pelo PHP-FPM
- [ ] Atualizações de segurança Ubuntu + imagens Docker
- [ ] Backup automático do volume MySQL + `storage/`

---

## 13. Comandos de build e inicialização

### 13.1 Clone e preparação

```bash
cd /var/www   # ou /opt
git clone https://github.com/Cajorgino/sistemapretriagem.git
cd sistemapretriagem
cp .env.docker.example .env
# Editar .env (domínio, senhas, chaves, PYTHON_API_URL, WAHA_*)

# Confirmar modelos ML
ls -la python_api/models/
# model.pkl e model_meta.json devem existir
```

### 13.2 Assets front

No host (Node 20+) ou CI:

```bash
npm ci
npm run build
# Garante public/build versionado/disponível no container (volume bind ou COPY)
```

### 13.3 Subir stack

```bash
docker compose build
docker compose up -d
docker compose exec app php artisan key:generate   # se APP_KEY vazio
docker compose exec app php artisan migrate --force
docker compose exec app php artisan storage:link    # se usar disco public
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

### 13.4 Dev local (referência — não é produção)

```bash
composer run dev
# ou: php artisan serve + queue:listen + npm run dev
```

Testes:

```bash
composer test
# ou: php artisan test
```

---

## 14. Testes para validar funcionamento

```bash
# Containers
docker compose ps
docker compose logs --tail=100 app
docker compose logs --tail=100 ml
docker compose logs --tail=100 queue

# HTTP app (via proxy ou porta)
curl -I https://SEU_DOMINIO
curl -I http://127.0.0.1:8000

# ML
docker compose exec app curl -sS "$PYTHON_API_URL/docs" | head
# ou health específico se existir na API

# Laravel
docker compose exec app php artisan about
docker compose exec app php artisan queue:failed

# Login web no browser
# Criar/editar consulta e verificar job de análise na fila
# (Opcional) POST /api/v1/n8n/whatsapp/rag/{telefone} com X-Webhook-Secret
```

---

## 15. Atualização e reinicialização

```bash
cd /caminho/sistemapretriagem
git pull
docker compose build
docker compose up -d
docker compose exec app composer install --no-dev --optimize-autoloader
# Se front mudou:
npm ci && npm run build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
docker compose exec app php artisan queue:restart
docker compose restart queue scheduler
```

Rollback: `git checkout <tag/commit anterior>` + rebuild + migrate com cautela (migrations irreversíveis).

---

## 16. Logs e diagnóstico

```bash
# Compose
docker compose logs -f nginx
docker compose logs -f app
docker compose logs -f queue
docker compose logs -f scheduler
docker compose logs -f ml
docker compose logs -f mysql

# Laravel
docker compose exec app tail -n 200 storage/logs/laravel.log
docker compose exec app tail -n 100 storage/logs/analises-reprocess.log
docker compose exec app tail -n 100 storage/logs/whatsapp-lembretes.log

# Host
sudo journalctl -u docker -n 100 --no-pager
sudo tail -n 100 /var/log/nginx/error.log
sudo ufw status verbose
df -h
free -h
docker stats
```

Problemas comuns:

| Sintoma | Verificar |
|---------|-----------|
| 502 Bad Gateway | `app` PHP-FPM up? `docker compose ps`; nginx → `app:9000` |
| Análise não roda | `queue` + `scheduler`; `QUEUE_CONNECTION`; `PYTHON_API_URL` |
| ML timeout | modelo presente? RAM? logs `ml` |
| WhatsApp silencioso | WAHA session WORKING; n8n ativo; Gemini cota; `LARAVEL_APP_URL` |
| Permissão storage | entrypoint + `chown www-data` em `storage` / `bootstrap/cache` |
| Conflito de portas | 80/443 host; 8000; 8010; 3000; 5678; 3307 |

---

## 17. Conflitos de portas (mapa)

| Porta | Uso atual no ecossistema |
|------|---------------------------|
| 80 / 443 | Nginx host (produção) |
| 8000 | Nginx Compose → Laravel (dev/local bind) |
| 8010 | FastAPI ML |
| 9000 | PHP-FPM (interno) |
| 3306 / 3307 | MySQL (não expor publicamente) |
| 3000 | WAHA (`apiwhatsapp`) |
| 5678 | n8n (`apiwhatsapp`) |

Na mesma VPS, planeje bind apenas em `127.0.0.1` para serviços internos e publique só 80/443.

---

## 18. Riscos de segurança (produção)

1. Expor MySQL (`3307`) ou n8n/WAHA sem auth.
2. `APP_DEBUG=true` ou `WAHA_NO_API_KEY=true` em produção.
3. Webhooks WhatsApp sem `WAHA_WEBHOOK_SECRET`.
4. Modelos/artefatos ML ausentes → falha silenciosa nas análises.
5. Volume `../AnalisePython/data` inexistente na VPS → quebra montagem Compose (ajustar compose de produção).
6. Secrets no Git / backups sem criptografia.
7. Cota Gemini (429) derruba respostas WhatsApp — monitorar.

---

## 19. Pontos que precisam de confirmação antes do deploy

- [ ] Domínio e DNS prontos?
- [ ] Tamanho da VPS (RAM) suficiente com ou sem WAHA/n8n?
- [ ] `model.pkl` / `model_meta.json` serão versionados/copiados como?
- [ ] Compose de produção remove `ports` do MySQL e ajusta volume `AnalisePython`?
- [ ] Timezone app: manter UTC ou `America/Sao_Paulo`?
- [ ] E-mail: `MAIL_STANDBY=false` + SMTP válido?
- [ ] WhatsApp na mesma VPS ou servidor separado?
- [ ] Política de backup (MySQL + `storage` + sessão WAHA)?

---

## 20. Como o Cursor deve usar este guia na etapa SSH

1. Conectar SSH (credenciais fornecidas por você).
2. Rodar inspeção: `uname -a`, `free -h`, `df -h`, `docker --version`, `ss -tlnp`, `ufw status`.
3. Comparar com seções 2–4 e 17 deste documento.
4. Instalar **somente** o que faltar.
5. Clonar/atualizar repo; criar `.env` **sem inventar secrets** (pedir os valores).
6. Subir Compose; migrate; build assets; Nginx+SSL.
7. Executar seção 14 (validação).
8. Registrar no final o que ficou pendente (WhatsApp, backups, monitoramento).

---

## 21. Caminho deste documento

```text
sistemapretriagem/DEPLOY_UBUNTU.md
```

Repositório: [github.com/Cajorgino/sistemapretriagem](https://github.com/Cajorgino/sistemapretriagem)
