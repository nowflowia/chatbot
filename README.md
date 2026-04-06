# ChatBot System

Plataforma completa de atendimento via WhatsApp com fluxos automatizados, fila de atendimento, painel web e integração com a API Oficial da Meta (WhatsApp Business).

---

## Funcionalidades

- **Atendimento humano** — fila, transferência entre agentes, finalização de chats
- **Fluxos automatizados** — builder visual com drag-and-drop, 8+ tipos de nós
- **WhatsApp Business API** — envio e recebimento de texto, imagem, vídeo, áudio, documento, GIF e localização
- **Painel web responsivo** — Dashboard, atendimento em tempo real, relatórios
- **Configurações SMTP** — envio de convites e redefinição de senha por e-mail
- **Identidade visual** — logo, ícone/favicon e CSS personalizado por instalação
- **Licenciamento** — controle de usuários e fluxos por domínio via API externa
- **Instalador web** — configuração guiada similar ao WordPress (`/install/`)

---

## Requisitos do Servidor

| Item | Mínimo |
|------|--------|
| PHP | **8.1** ou superior |
| Banco de dados | MySQL **5.7+** ou MariaDB **10.4+** |
| Extensões PHP | `pdo`, `pdo_mysql`, `mbstring`, `curl`, `json`, `fileinfo` |
| Servidor web | Apache 2.4+ (com `mod_rewrite`) ou Nginx |
| HTTPS | Recomendado (obrigatório para webhook da Meta) |
| RAM | 512 MB mínimo |
| Disco | 500 MB livres |

### Configuração do Apache

O arquivo `public/.htaccess` já está incluído. Certifique-se de que `AllowOverride All` está habilitado no VirtualHost:

```apache
<VirtualHost *:80>
    ServerName chat.seudominio.com.br
    DocumentRoot /var/www/chatbot/public

    <Directory /var/www/chatbot/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Configuração do Nginx

```nginx
server {
    listen 80;
    server_name chat.seudominio.com.br;
    root /var/www/chatbot/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}
```

---

## Instalação

### Opção 1 — Instalador Web (recomendado)

1. **Clone ou faça upload** dos arquivos para o servidor:

```bash
git clone https://github.com/nowflowia/chatbot.git /var/www/chatbot
```

2. **Instale as dependências** via Composer:

```bash
cd /var/www/chatbot
composer install --no-dev --optimize-autoloader
```

3. **Permissões de escrita:**

```bash
chmod -R 755 /var/www/chatbot
chown -R www-data:www-data /var/www/chatbot
chmod -R 775 /var/www/chatbot/storage
chmod -R 775 /var/www/chatbot/public/assets/uploads
```

4. **Configure o servidor web** apontando o DocumentRoot para `/var/www/chatbot/public`

5. **Acesse o instalador** no navegador:

```
https://chat.seudominio.com.br/install/
```

O instalador irá guiar você por 5 etapas:

| Etapa | Descrição |
|-------|-----------|
| 1 — Requisitos | Verifica PHP, extensões e permissões |
| 2 — Banco de dados | Host, porta, nome, usuário e senha |
| 3 — Licença | Chave de licença (opcional em modo dev) |
| 4 — Configuração | Nome do sistema, URL e conta admin |
| 5 — Instalar | Grava `.env`, executa migrations, cria admin e bloqueia o instalador |

---

### Opção 2 — Instalação Manual

1. Clone o repositório e instale as dependências (passos 1 e 2 acima)

2. Copie e edite o arquivo de ambiente:

```bash
cp .env.example .env
nano .env
```

3. Preencha as variáveis principais:

```env
APP_NAME="Meu ChatBot"
APP_URL=https://chat.seudominio.com.br
APP_KEY=base64:GERE_UMA_CHAVE_ALEATORIA_32_BYTES

DB_HOST=localhost
DB_DATABASE=chatbot
DB_USERNAME=chatbot_user
DB_PASSWORD=senha_segura
```

4. Execute as migrations:

```bash
php migrate.php up
```

5. Ajuste permissões (passo 3 acima)

---

## Instalação em cPanel (Hospedagem Compartilhada)

### Deploy inicial via SSH

A forma recomendada mesmo em cPanel é usar o **Terminal SSH** para clonar o repositório diretamente no servidor. Isso habilita atualizações com um clique pelo painel web.

1. **Acesse o Terminal SSH** no cPanel (ou use um cliente como PuTTY / Termius)

2. **Clone o repositório** na pasta do seu domínio:

```bash
# Substitua "chatbot" pelo nome da pasta configurada no cPanel
git clone https://github.com/nowflowia/chatbot.git ~/public_html
# ou em um subdiretório:
git clone https://github.com/nowflowia/chatbot.git ~/public_html/chat
```

3. **Instale as dependências** (se o Composer estiver disponível):

```bash
cd ~/public_html
composer install --no-dev --optimize-autoloader
```

> Se o Composer não estiver disponível via SSH, faça upload da pasta `vendor/` gerada localmente.

4. **Permissões de escrita:**

```bash
chmod -R 755 ~/public_html
chmod -R 775 ~/public_html/storage
chmod -R 775 ~/public_html/public/assets/uploads
```

5. **Configure o Document Root** no cPanel para apontar para a subpasta `public/`:
   - cPanel → **Domains** (ou Subdomains) → edite o domínio → altere o **Document Root** para `public_html/public` (ou `public_html/chat/public`)

6. **Acesse o instalador** no navegador: `https://seudominio.com.br/install/`

---

### Deploy via FTP/Upload (sem SSH)

Se o seu plano não tem SSH, faça o upload dos arquivos manualmente:

1. Gere a pasta `vendor/` localmente:

```bash
composer install --no-dev --optimize-autoloader
```

2. Faça upload de **todos os arquivos** (incluindo `vendor/`) para o servidor via FTP/File Manager

3. Configure o Document Root para a pasta `public/` (conforme passo 5 acima)

4. Acesse o instalador: `https://seudominio.com.br/install/`

> **Atenção:** Deploy por FTP **não inicializa um repositório Git**, portanto as atualizações automáticas pelo painel não estarão disponíveis. Para habilitar, conecte via SSH após o upload e execute:
> ```bash
> cd ~/public_html
> git init
> git remote add origin https://github.com/nowflowia/chatbot.git
> git fetch
> git reset --hard origin/main
> ```

---

### Habilitando atualizações automáticas no cPanel

Para que o botão **Atualização** no painel funcione, o servidor precisa de dois requisitos:

**1. Git instalado e acessível pelo PHP**

Verifique via SSH:
```bash
which git
# Resultado esperado em cPanel: /usr/local/cpanel/3rdparty/bin/git
#                           ou: /opt/cpanel/ea-git/root/usr/bin/git
```

**2. Funções de execução habilitadas no PHP**

No cPanel, vá em:  
**Software → Select PHP Version → aba "Options"**

Verifique se `shell_exec` e `exec` **não estão** listados em `disable_functions`. Se estiverem, remova-os e salve.

> Após ajustar, acesse **Administração → Atualização** no painel. A página irá diagnosticar o que ainda está bloqueado.

---

## Atualização do Sistema

### Via painel web (recomendado)

1. No menu lateral, clique em **Atualização** (ícone de nuvem)
2. Clique em **Verificar** — o sistema consulta o repositório remoto e informa quantos commits novos existem
3. Clique em **Atualizar Agora** — executa `git pull` no servidor e recarrega a página automaticamente

> Requisito: deploy feito via `git clone` e Git acessível pelo PHP (ver seção cPanel acima).

---

### Atualização manual via SSH

```bash
cd ~/public_html   # ou o caminho da sua instalação
git pull
```

Se houver conflito de arquivos locais modificados:

```bash
git stash          # salva alterações locais
git pull           # atualiza
git stash pop      # restaura alterações locais (se necessário)
```

---

### Atualização manual via FTP

1. Baixe a versão mais recente do repositório em [github.com/nowflowia/chatbot](https://github.com/nowflowia/chatbot)
2. **Não sobrescreva** os seguintes arquivos/pastas:
   - `.env` — suas configurações de ambiente
   - `storage/` — logs e cache
   - `public/assets/uploads/` — arquivos enviados pelos usuários
3. Faça upload dos demais arquivos substituindo os existentes
4. Se houver novas migrations, execute via SSH:

```bash
php migrate.php up
```

> Ou acesse `/install/` com o arquivo `storage/.installed` removido temporariamente (restaure após).

---

## Configuração do WhatsApp Business API

1. Acesse [developers.facebook.com](https://developers.facebook.com) e crie um App do tipo **Business**
2. Adicione o produto **WhatsApp** ao app
3. Obtenha o **Access Token**, **Phone Number ID** e **WABA ID**
4. No painel, acesse **Configurações → WhatsApp** e preencha os dados
5. Cole a **URL do Webhook** no portal da Meta:

```
https://chat.seudominio.com.br/webhook
```

6. Use o mesmo **Verify Token** cadastrado nas configurações
7. Assine o evento: `messages`

> ⚠️ O webhook requer HTTPS válido. Use Let's Encrypt ou Cloudflare.

---

## Configuração de E-mail (SMTP)

No painel, acesse **Configurações → E-mail / SMTP** e preencha:

| Campo | Exemplo |
|-------|---------|
| Host | `smtp.gmail.com` |
| Porta | `587` |
| Criptografia | `TLS` |
| Usuário | `seu@gmail.com` |
| Senha | App Password (não a senha da conta) |

---

## Estrutura de Diretórios

```
/
├── app/
│   ├── Controllers/     # Controladores HTTP
│   ├── Models/          # Models (PDO)
│   ├── Services/        # Serviços (WhatsApp, Mail, License, etc.)
│   └── Views/           # Templates PHP
├── bootstrap/           # Bootstrap da aplicação
├── config/              # Arquivos de configuração
├── core/                # Micro-framework (Router, DB, Auth, etc.)
├── database/
│   └── migrations/      # Migrations versionadas
├── install/             # Instalador web (desativado após uso)
├── public/              # Document root (index.php, assets)
├── routes/              # Definição de rotas
├── storage/
│   ├── cache/
│   └── logs/
└── .env                 # Variáveis de ambiente (não versionado)
```

---

## Licenciamento

O sistema requer uma licença válida para uso em produção.

**Adquira sua licença em [nowflow.com.br](https://nowflow.com.br)**

Após a compra você receberá uma **Chave de Licença** que deve ser informada durante a instalação (etapa 3 do instalador web) ou manualmente no `.env`:

```env
LICENSE_KEY=sua-chave-aqui
```

A licença define os limites do plano contratado (número de usuários, fluxos, etc.) e é verificada automaticamente pelo sistema.

---

## Troubleshooting

### Atualização pelo painel

#### `fatal: detected dubious ownership in repository`

O processo PHP roda com um usuário diferente do dono dos arquivos (comum em cPanel/LiteSpeed). O sistema já passa `-c safe.directory` automaticamente. Se o erro persistir, execute via SSH:

```bash
git config --global --add safe.directory /home/usuario/public_html
```

Substitua `/home/usuario/public_html` pelo diretório exibido no erro.

---

#### `Git não disponível` / Git não encontrado

1. Via SSH, descubra o caminho do git:

```bash
which git
```

2. Adicione ao `.env` da instalação:

```env
GIT_PATH=/usr/local/cpanel/3rdparty/bin/git
```

3. Caminhos comuns no cPanel:
   - `/usr/local/cpanel/3rdparty/bin/git`
   - `/opt/cpanel/ea-git/root/usr/bin/git`
   - `/usr/bin/git`

---

#### `shell_exec` / `exec` desabilitados

No cPanel: **Software → Select PHP Version → aba "Options"** → remova `shell_exec`, `exec` e `proc_open` do campo `disable_functions`.

---

#### `fatal: no commit on branch 'master' yet` ao fazer git pull

O repositório foi inicializado localmente mas ainda não tem commits. Execute:

```bash
git pull origin main
git checkout -b main --track origin/main
```

A partir daí `git pull` simples funcionará.

---

#### `There is no tracking information for the current branch`

O branch local não está rastreando o remoto. Resolva com:

```bash
git branch --set-upstream-to=origin/main main
# ou se o branch local ainda for 'master':
git pull origin main
git checkout -b main --track origin/main
```

---

#### Versão instalada mostra `fatal:` na página de Atualização

Indica que o git retornou erro ao consultar o log local. Causas comuns:
- Branch sem commits (`git pull origin main` resolve)
- Permissão negada (`safe.directory` — ver acima)
- Git não encontrado (`GIT_PATH` no `.env` — ver acima)

---

### Login

#### `Erro de conexão` ao fazer login (cPanel/LiteSpeed)

Alguns servidores cPanel removem o header `X-Requested-With`. O sistema já trata isso automaticamente. Se o problema persistir, verifique se há algum plugin de cache ou WAF interceptando as requisições.

---

### Instalador

#### `ERR_TOO_MANY_REDIRECTS` ao acessar `/install/`

O DocumentRoot não está apontando para a pasta `public/`. Corrija no cPanel em **Domains → Document Root** para que aponte para `public_html/public` (ou o equivalente na sua estrutura).

#### `storage/` sem permissão de escrita

```bash
chmod -R 775 storage
chmod -R 775 public/assets/uploads
```

---

## Segurança

- Nunca versione o arquivo `.env`
- Mantenha `APP_DEBUG=false` em produção
- O instalador é bloqueado automaticamente após o uso
- Todas as rotas protegidas exigem autenticação por sessão
- Tokens CSRF em todos os formulários POST
- Validação de assinatura X-Hub-Signature-256 no webhook

---

## Suporte

Abra uma issue em [github.com/nowflowia/chatbot](https://github.com/nowflowia/chatbot/issues)

---

**NowFlow © 2026** — Desenvolvido com PHP puro, Bootstrap 5 e Meta WhatsApp Business API
