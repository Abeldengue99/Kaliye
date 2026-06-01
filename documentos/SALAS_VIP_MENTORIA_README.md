# 📚 Salas VIP de Mentoria - Documentação Completa

## ✓ O que foi implementado

Sistema completo de **Salas VIP de Mentoria** para plataforma Aksanti, permitindo que mentores criem salas de grupo para gerenciar mentorandos com funcionalidades avançadas.

---

## 🎯 Funcionalidades Principais

### 1️⃣ **Criar Sala VIP** 
- Mentor clica no botão "+ Sala VIP" no sidebar de mensagens
- Insere um nome inspirador para a turma
- Sistema cria automaticamente a sala com tabelas no banco de dados

### 2️⃣ **Adicionar Mentorandos**
- Mentor pode adicionar mentorandos à sala por ID ou email
- Sistema envia notificação ao mentorado
- Mentorado vê a sala aparecer no sidebar

### 3️⃣ **Enviar Mensagens**
- Mentorandos e mentor trocam mensagens em tempo real
- Suporta:
  - ✓ Mensagens de texto
  - ✓ Notas de voz (WebRTC)
  - ✓ Emojis
  - ✓ Auto-refresh a cada 4 segundos

### 4️⃣ **Iniciar Videochamadas**
- Mentor pode iniciar reuniões Jitsi dentro da sala
- Link é compartilhado automaticamente
- Todos na sala podem entrar

### 5️⃣ **Enviar Materiais**
- Mentor envia PDFs, documentos, vídeos, etc
- Suporta até 50MB por arquivo
- Sistema organiza por tipo (PDF, Word, Excel, imagem, vídeo)
- Mentorandos recebem notificação

---

## 📁 Arquivos Criados/Modificados

### Tabelas de Banco de Dados
```
✓ mentor_chat_groups - Salas VIP (mentor_id, name)
✓ mentor_group_members - Membros (group_id, user_id, role)
✓ mentor_group_messages - Mensagens (group_id, sender_id, message, type)
✓ mentor_group_meetings - Reuniões Jitsi (group_id, meet_link)
✓ mentor_group_resources - Materiais (group_id, file_path, type)
```

### APIs PHP (Backend)
| Arquivo | Função |
|---------|--------|
| `create_mentor_group.php` | Criar nova sala |
| `send_mentor_group_message.php` | Enviar mensagens/áudio/vídeo |
| `get_mentor_group_messages.php` | Recuperar mensagens |
| `add_member_to_group.php` | Adicionar mentorandos |
| `send_mentor_group_resource.php` | Enviar materiais |
| `get_mentor_group_members.php` | Listar membros |
| `get_mentor_groups.php` | Listar grupos do usuário |

### JavaScript/Frontend (Modificado)
| Arquivo | Mudanças |
|---------|----------|
| `chat_scripts.php` | ✓ fetchMentorGroupMessages() ✓ renderMentorMessages() ✓ loadMentorGroupChat() ✓ startMentorMeeting() ✓ sendMentorGroupResource() ✓ addMemberToMentorGroup() |
| `messages.php` | ✓ Carrega grupos do mentor na query SQL |
| `chat_sidebar.php` | ✓ Exibe salas VIP com ícone de coroa |
| `cabecalho.php` | ✓ Auto-inicializa as tabelas |

### Migrações
```
argumentos/migrations/mentor_chat_groups_migration.php - Cria tabelas automaticamente
```

---

## 🚀 Como Usar

### Para Mentores:

#### 1. Criar Sala VIP
```javascript
createMentorGroup() // Clique no botão "+ Sala VIP"
```

#### 2. Adicionar Mentorandos
- Abrir a sala VIP
- Clicar em "Adicionar Mentorado"
- Digitar ID ou email do mentorado

#### 3. Enviar Mensagens
- Digitar a mensagem no campo de input
- Ou gravar nota de voz
- Sistema auto-envia

#### 4. Compartilhar Materiais
```javascript
sendMentorGroupResource() // Clique para enviar
```

#### 5. Iniciar Videochamada
```javascript
startMentorMeeting() // Clique no ícone de vídeo
```

### Para Mentorandos:

1. **Receber Convite**: Notificação quando adicionado à sala
2. **Acessar Sala**: Clica na sala no sidebar (aba "Salas de Mentoria VIP")
3. **Participar**: 
   - Envia mensagens
   - Visualiza materiais compartilhados
   - Entra em videochamadas

---

## ✅ Testes

### Executar Teste de Validação
```
http://seu-dominio.com/test_mentor_vip_rooms.php
```

Este script verifica:
- ✓ Existência de todas as tabelas
- ✓ Colunas obrigatórias
- ✓ Arquivos de API
- ✓ Permissões de escrita
- ✓ Estatísticas do sistema

---

## 🔒 Segurança Implementada

1. **Autenticação**: Verifica `$_SESSION['user_id']`
2. **Autorização**: Mentor só modifica seus próprios grupos
3. **Validação**: Entrada sanitizada com `ChatSecurity::normalizeText()`
4. **SQL Injection**: Prepared statements com PDO
5. **Rate Limiting**: Limite de mensagens via `ChatSecurity::checkRateLimit()`
6. **Tipos Permitidos**: Upload restrito a 50MB e extensões seguras
7. **XSS Protection**: HTML escapado com `htmlspecialchars()`

---

## 🐛 Troubleshooting

### Problema: "Sem permissão para publicar"
**Solução**: Verifique se o usuário está na tabela `mentor_group_members`

### Problema: Mensagens não aparecem
**Solução**: 
- Verifique se `fetchMentorGroupMessages()` é chamado regularmente
- Cheque a consola do navegador para erros JavaScript

### Problema: Upload de arquivo falha
**Solução**:
- Verifique permissões de `carregamentos/mentorship_resources/`
- Arquivo < 50MB
- Extensão na lista permitida

### Problema: "Tabela não existe"
**Solução**: Acesse `test_mentor_vip_rooms.php` para regenerar tabelas

---

## 📊 Estrutura de Dados

### Exemplo: Fluxo Completo

```
1. Mentor "João" cria sala: INSERT INTO mentor_chat_groups (mentor_id='123', name='Turma A')
   → ID: 1

2. João adiciona "Maria" (ID=456): INSERT INTO mentor_group_members (group_id=1, user_id=456)

3. João envia mensagem: INSERT INTO mentor_group_messages (group_id=1, sender_id=123, message='Olá!')

4. Maria recebe (polling): SELECT * FROM mentor_group_messages WHERE group_id=1

5. João envia PDF: INSERT INTO mentor_group_resources + INSERT INTO mentor_group_messages (type='resource')

6. João cria reunião: INSERT INTO mentor_group_meetings + INSERT INTO mentor_group_messages (type='meeting')
```

---

## 🔧 Configuração

### Variáveis de Ambiente
Nenhuma variável especial necessária. Sistema usa:
- `AKSANTI_CONFIG.baseUrl` (já disponível)
- `AKSANTI_CONFIG.userId` (já disponível)

### Limites Padrão
- Tamanho máximo de arquivo: **50MB**
- Rate limit de mensagens: **8 mensagens / 10 minutos**
- Polling de atualização: **4 segundos**

---

## 📝 Notas Técnicas

1. **Auto-Refresh**: Mensagens carregam a cada 4 segundos via `setInterval()`
2. **WebRTC Audio**: Gravação de voz nativa sem servidor
3. **Jitsi Integration**: Links automáticos para meet.jit.si
4. **Arquitetura**: MVC com PDO + AJAX + JSON

---

## 🎨 UI/UX Melhorias

- ✓ Ícone de coroa para mentores
- ✓ Indicadores de tipo (audio, video, resource)
- ✓ Cores diferenciadas por tipo de arquivo
- ✓ Feedback visual (spinner durante envio)
- ✓ Toast notifications para feedback
- ✓ Auto-scroll para mensagens novas

---

## 📞 Support

Para reportar bugs ou sugestões:
1. Acesse `test_mentor_vip_rooms.php` para diagnosticar
2. Verifique error logs em `error_log()`
3. Inspecione Network tab (DevTools) para requisições AJAX

---

**Sistema pronto para produção! 🚀**
