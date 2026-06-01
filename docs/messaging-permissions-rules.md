# Regras de Permissões - Sistema de Mensagens e Visualização de Perfil
**Aksanti Referências - 31 de Maio de 2026**

---

## 📋 Índice
1. [Princípio Central](#princípio-central)
2. [Visualização de Perfil Completo](#visualização-de-perfil-completo)
3. [Matriz de Permissões](#matriz-de-permissões)
4. [Implementação Técnica](#implementação-técnica)
5. [Fluxo de Exceção](#fluxo-de-exceção)

---

## 🎯 Princípio Central

**O sistema deve controlar quem pode INICIAR uma conversa**, sem impedir que alguém responda quando já existe uma conversa aberta.

- Estudantes NÃO podem contactar Mentores ou Investidores do nada
- Mentores e Investidores conseguem abordar Estudantes
- Após iniciada a conversa, ambas as partes podem trocar mensagens normalmente
- Estudantes podem comunicar livremente entre si

---

## 👁️ Visualização de Perfil Completo

### Botão "Ver Perfil Completo" - Disponível para:
✅ Mentor Especialista  
✅ Estudante Mentor  
✅ Investidor  
✅ Administrador  

### NÃO Disponível para:
❌ Estudante (vê apenas perfil resumido no cartão)

---

## 📊 Matriz de Permissões

### Quem pode INICIAR conversa:

| De → Para | Estudante | Mentor | Investidor | Admin |
|-----------|-----------|--------|------------|-------|
| **Estudante** | ✅ Sim | ❌ Não | ❌ Não | ✅ Sim |
| **Mentor** | ✅ Sim | ✅ Sim | ✅ Sim | ✅ Sim |
| **Investidor** | ✅ Sim | ✅ Sim | ✅ Sim | ✅ Sim |
| **Admin** | ✅ Sim | ✅ Sim | ✅ Sim | ✅ Sim |

### Casos Especiais:
- **Estudante ↔ Estudante**: ✅ Sem restrições
- **Mentor ↔ Investidor**: ✅ Sem restrições
- **Mentor ↔ Mentor**: ✅ Sem restrições
- **Investidor ↔ Investidor**: ✅ Sem restrições

---

## 🔧 Implementação Técnica

### Regra Técnica Global

**O botão "Mensagem" deve aparecer se:**
1. Utilizador tem permissão para iniciar conversa, OU
2. Já existe conversa ativa entre os dois utilizadores

### Pseudocódigo
```
function canShowMessageButton(currentUser, targetUser, conversationExists) {
    // Se já existe conversa, sempre permite responder
    if (conversationExists) {
        return true;
    }
    
    // Se não existe conversa, verifica permissão para iniciar
    return canInitiateConversation(currentUser, targetUser);
}

function canInitiateConversation(from, to) {
    // Estudante pode iniciar com outro Estudante
    if (from.type === 'student' && to.type === 'student') {
        return true;
    }
    
    // Estudante NÃO pode iniciar com Mentor ou Investidor
    if (from.type === 'student' && 
        (to.type === 'mentor' || to.type === 'investor')) {
        return false;
    }
    
    // Mentor e Investidor podem iniciar com qualquer um
    if (from.type === 'mentor' || from.type === 'investor') {
        return true;
    }
    
    // Admin pode iniciar com qualquer um
    if (from.type === 'admin') {
        return true;
    }
    
    return false;
}
```

### Ficheiros Implementados:

1. **[recursos/js/aksanti_modals_v2.js](../recursos/js/aksanti_modals_v2.js)** - L797
   - Verifica se estudante está tentando mensagear mentor/investidor
   - Valida `connection_status === 'accepted'`

2. **[paginas/social/rede.php](../paginas/social/rede.php)** - L259
   - Esconde botão mensagem se estudante → mentor/investidor
   - Mostra para estudante ↔ estudante

3. **[test_syntax.js](../test_syntax.js)** - L1431
   - Lógica duplicada para card de usuários

---

## 🔄 Fluxo de Exceção

### Cenário: Estudante recebe mensagem de Mentor

1. **Mentor envia primeira mensagem** ✅ (permitido)
   - Conversa é criada na tabela `messages`
   - Notificação enviada ao estudante

2. **Estudante recebe a mensagem** 📨
   - A conversa agora existe

3. **Estudante abre conversa** 📖
   - O botão "Mensagem" aparece (conversa existe)
   - Pode responder normalmente

4. **Conversa continua ativa** 💬
   - Ambos podem trocar mensagens indefinidamente

### Mesma lógica aplica-se para:
- Investidor → Estudante
- Mentor → Mentor
- Investor → Investidor
- Qualquer um → Qualquer um (se conversa existe)

---

## 📌 Nota Importante

Embora o sistema restrinja o INÍCIO de conversa, a RESPOSTA é sempre permitida uma vez que a conversa está ativa.

Isto garante:
- **Proteção**: Estudantes não sofrem spam de estranhos
- **Oportunidade**: Mentores podem contactar estudantes ativamente
- **Comunicação**: Uma vez iniciada, conversas fluem normalmente
- **Flexibilidade**: Estudantes podem responder a qualquer momento

---

## ✅ Status de Implementação

- ✅ Regra documentada
- ✅ Lógica parcialmente implementada em múltiplos ficheiros
- ⏳ Verificação e consolidação necessárias
- ⏳ Testes de funcionalidade necessários

