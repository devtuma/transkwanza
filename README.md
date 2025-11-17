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

## 🚀 FASE 2 - SISTEMA KYC ✅

**Status:** Completa
**Objetivo:** Upload e verificação de documentos

### ✅ O que funciona:

- ✅ Upload de 3 documentos (frente, verso, selfie)
- ✅ Preview de imagens antes do upload
- ✅ Validação de tamanho (5MB) e tipo (JPG, PNG, PDF)
- ✅ Aprovação automática (para testes)
- ✅ Redirecionamento automático após login
- ✅ Verificação de status KYC
- ✅ Interface com steps visuais

### 📁 Arquivos adicionados:

```
public_html/
├── kyc.html               # Página de upload KYC
├── api/
│   └── kyc.php            # API de upload (2 endpoints)
├── js/
│   └── kyc.js             # Lógica de upload + preview
└── uploads/
    ├── .htaccess          # Proteção de segurança
    └── documents/         # Pasta para documentos
```

### 🗄️ Banco de Dados Atualizado:

**Novos campos na tabela `users`:**
- `document_type` - Tipo do documento (rg, cnh, passport, cpf)
- `document_number` - Número do documento
- `document_front` - Arquivo da frente
- `document_back` - Arquivo do verso (opcional)
- `document_selfie` - Arquivo da selfie
- `kyc_status` - Status (pending, under_review, approved, rejected)
- `kyc_submitted_at` - Data de envio
- `kyc_reviewed_at` - Data de aprovação/rejeição

### 🔄 Fluxo Completo:

1. Usuário cria conta → `kyc_status = 'pending'`
2. Login redireciona automaticamente para `kyc.html`
3. Usuário faz upload dos documentos com preview
4. Sistema valida e aprova automaticamente (FASE 2)
5. Redireciona para `dashboard.html`

### 📝 Como testar:

1. Criar nova conta ou usar teste@transkwanza.com
2. Será redirecionado para página KYC
3. Selecionar tipo de documento
4. Fazer upload de 3 fotos (frente, verso opcional, selfie)
5. Ver preview das imagens
6. Clicar em "Enviar Documentos"
7. Aprovação automática e redirecionamento

---

## 🔄 PRÓXIMAS FASES

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

## 📊 Estatísticas do Projeto:

**FASE 1:**
- Arquivos: 8
- Linhas: ~500
- Status: ✅ COMPLETA

**FASE 2:**
- Arquivos adicionados: 4
- Linhas adicionadas: ~450
- Status: ✅ COMPLETA

**TOTAL ATUAL:**
- **Total de arquivos:** 12
- **Total de linhas:** ~950
- **Fases completas:** 2 de 6
- **Status geral:** ✅ FUNCIONANDO PERFEITAMENTE

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
