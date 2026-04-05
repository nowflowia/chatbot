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
