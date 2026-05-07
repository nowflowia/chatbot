# Prompt — Módulo de Atendimento via WhatsApp (Meta Business API)

Cole este prompt em uma nova sessão do Claude Code para gerar o módulo de chat em outro projeto.

---

## 🎯 Objetivo

Construa um módulo completo de **atendimento via WhatsApp** integrado com a **Meta WhatsApp Business API**, com suporte a fluxos automatizados, fila de atendimento, setores (departamentos) e painel web para agentes humanos.

## 🧱 Stack e Convenções

- **Backend:** PHP 8.1+ puro (sem Laravel/Symfony), PDO MySQL, arquitetura MVC simples
- **Frontend:** Bootstrap 5 + Vanilla JS (sem build tools), views PHP renderizadas no servidor
- **Autoload:** PSR-4 via Composer (namespaces `App\` → `app/`, `Core\` → `core/`)
- **Banco:** MySQL 5.7+ / MariaDB 10.4+
- **Webhook:** HTTPS obrigatório (requisito da Meta)
- Sem dependências pesadas — use apenas `guzzlehttp/guzzle` (ou cURL nativo) para HTTP

## 📦 Entregáveis

### 1. Conexão com a Meta WhatsApp Business API

Crie `app/Services/MetaWhatsAppService.php` com métodos:

- `sendText(string $to, string $text): array`
- `sendMedia(string $to, string $type, string $url, ?string $caption = null): array` — tipos: `image|video|audio|document`
- `sendLocation(string $to, float $lat, float $lng, ?string $name = null): array`
- `sendTemplate(string $to, string $templateName, string $lang, array $components = []): array`
- `markAsRead(string $messageId): void`
- `downloadMedia(string $mediaId): array` — retorna `['path'=>..., 'mime'=>..., 'filename'=>...]`
- `verifySignature(string $payload, string $signature, string $appSecret): bool` — valida `X-Hub-Signature-256`

**Configuração em `.env`:**
```
WHATSAPP_ACCESS_TOKEN=
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_WABA_ID=
WHATSAPP_APP_SECRET=
WHATSAPP_VERIFY_TOKEN=
```

Base da API: `https://graph.facebook.com/v20.0/{phone_number_id}/messages`

### 2. Webhook da Meta

Crie `app/Controllers/WebhookController.php`:

- `GET /webhook` → validação (`hub.mode=subscribe`, `hub.verify_token`, `hub.challenge`)
- `POST /webhook`:
  1. Valida assinatura `X-Hub-Signature-256` via HMAC SHA-256 com `WHATSAPP_APP_SECRET`
  2. Roteia eventos: `messages`, `statuses`
  3. Para cada mensagem recebida:
     - Cria/atualiza `contact` (nome, telefone, último contato)
     - Busca/cria `chat` (status: `queue` | `in_service` | `finished`)
     - Persiste `message` (direção `in|out`, tipo, conteúdo, `message_id` da Meta)
     - Se o chat tem `flow_id` ativo → envia pro `FlowEngine`
     - Senão → coloca na fila do setor de entrada

Responde imediatamente `200 OK` e processa de forma assíncrona (ou enfileira).

### 3. Modelo de dados (migrations)

```sql
-- Setores / departamentos
sectors (id, name, color, is_default, created_at, updated_at)

-- Fluxos
flows (id, name, trigger_type, trigger_keyword, sector_id, is_active, timeout_minutes, created_at, updated_at)
flow_nodes (id, flow_id, type, position_x, position_y, config_json)
-- type: start | message | input | condition | delay | transfer | end | webhook
flow_edges (id, flow_id, from_node_id, to_node_id, condition_label)

-- Contatos
contacts (id, phone, name, avatar_url, tags_json, last_seen_at, created_at, updated_at)

-- Chats
chats (id, contact_id, sector_id, assigned_user_id, status, flow_id, flow_node_id,
       last_message_at, finished_at, finished_by, notes, created_at, updated_at)
-- status: queue | in_service | finished

-- Mensagens
messages (id, chat_id, direction, type, content, media_url, message_meta_id,
          status, from_user_id, sent_at, created_at)
-- direction: in | out
-- type: text | image | video | audio | document | location | template
-- status: pending | sent | delivered | read | failed

-- Usuários / agentes
users (id, name, email, password, role, is_active, created_at, updated_at)
-- role: admin | supervisor | agent

-- Agente ⇄ setor (N:N)
user_sectors (user_id, sector_id)

-- Configurações gerais
settings (key, value)  -- chave-valor (chat_greeting, auto_assign, queue_timeout, etc.)
```

### 4. Painel — Atendimento

**Rotas:**
- `GET /chats` — tela principal (lista de conversas + painel de mensagens no estilo WhatsApp Web)
- `GET /chats/{id}` — AJAX retorna histórico + dados do contato
- `POST /chats/{id}/messages` — envia mensagem (texto/mídia)
- `POST /chats/{id}/assign` — atribui a um agente
- `POST /chats/{id}/finish` — finaliza com nota opcional
- `POST /chats/{id}/transfer` — transfere para outro setor

**UI:**
- Layout 3 colunas: sidebar de conversas | thread de mensagens | dados do contato
- Filtros: Meus / Fila / Finalizados / Todos
- Indicador de mensagens não lidas por chat
- Atualização em tempo real via polling a cada 5s (ou SSE se preferir)
- Upload de mídia com preview antes do envio
- Atalhos: `/transferir`, `/finalizar`, respostas rápidas pré-cadastradas

### 5. Fila de atendimento

**Rotas:**
- `GET /queue` — lista de chats aguardando atendente (status = `queue`)
- `POST /queue/{id}/take` — agente "puxa" um chat (passa a `in_service`, assigned_user_id = user atual)

**Regras:**
- Um chat só aparece na fila do setor do qual o agente faz parte (via `user_sectors`)
- Admin/supervisor vê todos
- Ordenação: mais antigo primeiro

### 6. Configurações do Chat

Em **Configurações → WhatsApp**:
- Access Token, Phone Number ID, WABA ID, App Secret, Verify Token
- Exibir URL do webhook para copiar
- Botão **Testar conexão** — chama `/me` na Graph API

Em **Configurações → Atendimento**:
- Mensagem de saudação automática (primeira mensagem de novos contatos)
- Mensagem de fora do horário (checkbox + horários por dia da semana)
- Timeout de inatividade (ex: finaliza chat sem resposta após X minutos)
- Setor padrão de entrada
- Respostas rápidas (CRUD lista de atalho → texto)

### 7. Setores (departamentos)

**Rotas:**
- `GET /sectors` — listagem
- `POST /sectors` — criar
- `PUT /sectors/{id}` — editar
- `DELETE /sectors/{id}` — excluir (bloquear se tiver chats abertos)

**Campos:**
- Nome, cor (hex para badge), flag `is_default`
- Multi-select de agentes que atendem o setor

### 8. Fluxos Automatizados (Builder Visual)

Crie um **builder drag-and-drop** em `public/assets/js/flow-builder.js` (sem libs pesadas — pode usar uma lib leve como `jsPlumb` ou implementar conexões em SVG puro).

**Tipos de nó:**

| Tipo | Config | Comportamento |
|------|--------|---------------|
| `start` | trigger (keyword \| qualquer mensagem \| evento) | Ponto de entrada |
| `message` | texto ou mídia | Envia mensagem |
| `input` | variável, validação opcional | Aguarda resposta, salva em `chat_variables` |
| `condition` | expressão sobre variáveis | Ramifica entre `true`/`false` ou múltiplas saídas |
| `delay` | segundos ou minutos | Pausa |
| `transfer` | sector_id ou user_id | Transfere para humano |
| `webhook` | URL, método, headers | Chama API externa |
| `end` | — | Finaliza fluxo (retorna à fila ou encerra chat) |

**`FlowEngine` (serviço):**
- `start(Chat $chat, Flow $flow): void`
- `processMessage(Chat $chat, Message $message): void`
- Persiste estado em `chats.flow_node_id` + `chat_variables`

**Rotas:**
- `GET /flows` — listagem
- `GET /flows/{id}/edit` — builder visual
- `POST /flows/{id}/save` — recebe JSON do canvas e persiste nós/arestas

### 9. Dashboard

- Total de chats hoje / na semana / no mês
- Chats em atendimento agora (por setor)
- Tempo médio de primeira resposta
- Tempo médio de atendimento
- Top 5 agentes (atendimentos finalizados)
- Gráfico: volume de mensagens por hora (últimos 7 dias)

## 🔐 Segurança

- CSRF em todo POST do painel
- Rate limit no webhook (60 req/min por IP)
- Sanitização de mídia antes de exibir (validar mime type real)
- Logs estruturados em `storage/logs/` com rotação diária
- Nunca expor `WHATSAPP_ACCESS_TOKEN` no front

## 📁 Estrutura de Pastas

```
app/
├── Controllers/
│   ├── ChatController.php
│   ├── QueueController.php
│   ├── SectorController.php
│   ├── FlowController.php
│   ├── SettingsController.php
│   └── WebhookController.php
├── Models/
│   ├── Chat.php
│   ├── Message.php
│   ├── Contact.php
│   ├── Sector.php
│   ├── Flow.php
│   └── User.php
├── Services/
│   ├── MetaWhatsAppService.php
│   ├── FlowEngine.php
│   ├── QueueService.php
│   └── MediaService.php
└── Views/
    ├── chat/
    ├── queue/
    ├── sectors/
    ├── flows/
    └── settings/
```

## ✅ Ordem sugerida de implementação

1. Migrations e models básicos
2. `MetaWhatsAppService` com `sendText` + `/me` para testar conexão
3. Webhook (verify GET + receive POST com validação de assinatura)
4. CRUD de setores
5. Tela de atendimento (sem fluxos ainda) — envio/recebimento funcionando
6. Fila + atribuição
7. Configurações do chat (saudação, horário, timeout)
8. Dashboard
9. **Builder de fluxos** (deixar por último — é a parte mais complexa)

## 📝 Observações finais

- Comece entregando o **fluxo feliz**: receber mensagem → criar chat na fila → agente atende → envia texto → finaliza
- Mídia e templates tambem
- O builder de fluxos é o componente mais complexo — reserve tempo proporcional
- Teste o webhook com [ngrok](https://ngrok.com) em dev antes de publicar em HTTPS real
- Documente a URL do webhook e o Verify Token para o cliente configurar no portal da Meta
