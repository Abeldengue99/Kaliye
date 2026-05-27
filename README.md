# KALIYE Platform

Plataforma de mentoria e networking para estudantes, mentores e investidores da Aksanti.

## 📋 Estrutura do Projeto

```
KALIYE/
├── admin/              # Painel administrativo
├── api/                # Endpoints da API REST
├── assets/             # Recursos estáticos (CSS, JS, imagens)
├── config/             # Configurações (database, etc)
├── docs/               # Documentação e SQL schemas
├── includes/           # Componentes reutilizáveis (header, footer, i18n)
├── languages/          # Ficheiros de tradução (PT/EN)
├── scripts/            # Scripts de manutenção e debug
├── uploads/            # Ficheiros enviados pelos utilizadores
├── index.php           # Feed social principal
├── entrar.php           # Autenticação
├── registar.php        # Registo de utilizadores
├── projects.php        # Explorar ideias/projetos
├── mentorship.php      # Sistema de mentoria em cascata
├── messages.php        # Sistema de mensagens
└── profile.php         # Perfil do utilizador
```

## 🚀 Funcionalidades

### Autenticação & Perfis
- Sistema de login/registo seguro
- Perfis personalizáveis com foto e informação académica
- Tipos de utilizador: Estudante Universitário, Ensino Médio, Secundário, Mentor, Investidor, Admin

### Feed Social
- Publicação de ideias/projetos com múltiplas imagens e vídeos
- Sistema de likes e comentários em tempo real
- Edição e eliminação de posts próprios
- Carrossel de anúncios/oportunidades

### Mentoria em Cascata
- Universitários mentoram estudantes do ensino médio
- Estudantes do médio mentoram secundário
- Mentores especialistas orientam universitários
- Sistema de matching automático

### Mensagens
- Chat privado entre utilizadores
- Notificações de mensagens não lidas
- Interface responsiva mobile/desktop

### Multi-idioma
- Suporte completo PT/EN
- Tradução de toda a interface
- Persistência de preferência de idioma

## 🛠️ Tecnologias

- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Bibliotecas:** 
  - Font Awesome 6.0
  - AOS (Animate On Scroll)
  - Google Fonts (Inter, Outfit)

## ⚙️ Instalação

1. Clone o repositório para o diretório do servidor web:
```bash
git clone [repo-url] C:\xampp\htdocs\KALIYE
```

2. Importe o schema da base de dados:
```bash
mysql -u root -p < docs/database.sql
```

3. Configure as credenciais em `config/base_dados.php`

4. Aceda via navegador:
```
http://localhost/Aksanti%20Mentorship/
```

## 🎨 Paleta de Cores (Aksanti Brand)

- **Primary Background:** `#1a1a1a`
- **Secondary Background:** `#2a2a2a`
- **Accent Orange:** `#f7941d` (Cor oficial Aksanti)
- **Accent Gold:** `#d4af37`
- **Text Primary:** `#ffffff`
- **Text Secondary:** `#a0a0a0`

## 📝 Scripts de Manutenção

Os scripts em `/scripts` são ferramentas de debug e manutenção:
- `check_*.php` - Verificação de tabelas e dados
- `*_fix.php` - Scripts de correção de schema
- `verify_db.php` - Validação da base de dados

## 🔒 Segurança

- Prepared statements para prevenir SQL Injection
- Sanitização de inputs
- Validação server-side
- Sessões seguras
- Upload de ficheiros com validação de tipo

## 👥 Contribuidores

Desenvolvido pela equipa Aksanti Tech

## 📄 Licença

© 2025 Aksanti Investimentos. Todos os direitos reservados.
