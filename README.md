# 🌍 TransKwanza - MVP Incremental

Sistema P2P de remessas internacionais construído de forma incremental.

---

## 🚀 FASE 1 - AUTENTICAÇÃO BÁSICA ✅

**Status:** Completa
**Objetivo:** Usuários conseguem criar conta e fazer login

### ✅ O que funciona:

- ✅ Cadastro de usuários
- ✅ Login com email e senha
- ✅ Validações básicas
- ✅ Dashboard simples
- ✅ Sistema de sessão (localStorage)
- ✅ Design responsivo e bonito

### 📁 Arquivos criados:

```
public_html/
├── index.html           # Landing page com 2 botões
├── login.html           # Formulário de login
├── cadastro.html        # Formulário de cadastro
├── dashboard.html       # Dashboard simples
├── database_schema.sql  # Schema do banco (1 tabela)
├── api/
│   ├── config.php       # Conexão com MySQL
│   └── auth.php         # API de autenticação
├── js/
│   └── auth.js          # Lógica frontend
└── css/
    └── style.css        # Estilos bonitos
```

### 🗄️ Banco de Dados:

**Credenciais (Hostinger):**
```
Host: localhost
Database: u758469769_transkwanza
Username: u758469769_admin
Password: Life0852!
```

**Tabelas:**
- `users` (id, name, email, password, country, created_at)

### 📝 Como instalar:

1. **Importar banco de dados:**
   - Acessar phpMyAdmin no Hostinger
   - Importar arquivo `database_schema.sql`

2. **Upload dos arquivos:**
   - Fazer upload de toda a pasta `public_html/` para o servidor

3. **Testar:**
   - Acessar: `https://seudominio.com`
   - Criar conta
   - Fazer login
   - Acessar dashboard

### 🧪 Usuário de teste:

```
Email: teste@transkwanza.com
Senha: password
```

---

## 🔄 PRÓXIMAS FASES

### FASE 2 - Sistema KYC (Pendente)
- [ ] Upload de documentos
- [ ] Validação de documentos
- [ ] Status de verificação
- [ ] Redirecionamento automático

### FASE 3 - Propostas Básicas (Pendente)
- [ ] Criar propostas
- [ ] Listar propostas
- [ ] Aceitar propostas
- [ ] Tabela de moedas

### FASE 4 - Transações (Pendente)
- [ ] Fluxo de transação
- [ ] Upload de comprovante
- [ ] Confirmação de recebimento

### FASE 5 - Painel Admin (Pendente)
- [ ] Aprovar KYC
- [ ] Aprovar transações
- [ ] Estatísticas

### FASE 6 - Melhorias (Pendente)
- [ ] Login social
- [ ] Anti-fraude
- [ ] Chat
- [ ] Avaliações
- [ ] Notificações

---

## 📊 Estatísticas da Fase 1:

- **Arquivos:** 8
- **Linhas de código:** ~500
- **Tempo de desenvolvimento:** 1 dia
- **Status:** ✅ FUNCIONANDO

---

## 🎯 Filosofia do Projeto:

> "Construir do chão para cima, com vitórias pequenas e constantes."

Cada fase é:
- ✅ Testada individualmente
- ✅ Funcional e completa
- ✅ Base sólida para próxima fase

---

**Desenvolvido com abordagem MVP incremental**
© 2024 TransKwanza
