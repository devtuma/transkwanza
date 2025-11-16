# 🌍 TRANSKWANZA - Plataforma P2P de Remessas Internacionais

Sistema completo de remessas internacionais peer-to-peer suportando 9 países e moedas.

## 📋 Visão Geral

**TransKwanza** é uma plataforma P2P que permite usuários trocarem moedas diretamente entre si, eliminando intermediários e oferecendo as melhores taxas de câmbio.

### Funcionalidades Principais

- ✅ **Calculadora de Conversão em Tempo Real** - Conversor interativo na página inicial
- ✅ Sistema de autenticação tradicional (email/senha)
- ✅ Login social (Google, Facebook, Instagram, Apple)
- ✅ KYC (Know Your Customer) com upload de documentos e preview
- ✅ Sistema anti-fraude integrado com detecção automática
- ✅ Painel administrativo completo com estatísticas
- ✅ Propostas de câmbio P2P com filtros avançados
- ✅ Sistema de transações com aprovação manual
- ✅ Suporte a 9 países/moedas com informações detalhadas
- ✅ Central de suporte com FAQ completo
- ✅ Páginas legais (Termos de Uso e Política de Privacidade LGPD/GDPR)
- ✅ Dashboard completo do usuário com 4 abas
- ✅ Sistema de testes integrado (test_conexao.php)

### Países e Moedas Suportados

| País | Moeda | Símbolo |
|------|-------|---------|
| 🇧🇷 Brasil | BRL | R$ |
| 🇦🇴 Angola | AOA | Kz |
| 🇵🇹 Portugal | EUR | € |
| 🇺🇸 Estados Unidos | USD | $ |
| 🇨🇺 Cuba | CUP | $ |
| 🇷🇺 Rússia | RUB | ₽ |
| 🇿🇦 África do Sul | ZAR | R |
| 🇳🇦 Namíbia | NAD | $ |
| 🇲🇿 Moçambique | MZN | MT |

## 🏗️ Arquitetura

### Stack Tecnológica

**Frontend:**
- HTML5
- CSS3 (Glassmorphism Design)
- JavaScript Vanilla (ES6+)
- Font Awesome 6.4.0

**Backend:**
- PHP 7.4+
- PDO (MySQL)
- JWT para autenticação
- bcrypt para senhas

**Banco de Dados:**
- MySQL 8.0+
- 11 tabelas relacionadas
- Charset: utf8mb4_unicode_ci

**Hospedagem:**
- Hostinger (cPanel)
- SSL Let's Encrypt

### Estrutura de Arquivos

```
public_html/
├── index.html              # Landing page com calculadora de conversão
├── login.html              # Página de login
├── cadastro.html           # Página de cadastro
├── dashboard.html          # Dashboard do usuário (4 abas)
├── kyc.html                # Upload de documentos KYC
├── admin.html              # Painel administrativo
├── paises.html             # Informações sobre 9 países suportados
├── suporte.html            # Central de suporte com FAQ
├── termos.html             # Termos de Uso
├── privacidade.html        # Política de Privacidade (LGPD/GDPR)
├── test_conexao.php        # Teste de conexão e configuração
├── .htaccess               # Configurações Apache
│
├── css/
│   ├── theme.css           # Variáveis CSS
│   ├── style.css           # Estilos globais + glassmorphism + calculadora
│   ├── auth.css            # Estilos de login/cadastro
│   ├── dashboard.css       # Estilos do dashboard
│   └── countries.css       # Estilos da página de países
│
├── js/
│   ├── main.js             # Utilidades globais (API, Auth, Loading, Notificações)
│   ├── auth.js             # Login/cadastro tradicional
│   ├── social-login.js     # OAuth integração (4 providers)
│   ├── currency.js         # Calculadora de conversão (72 pares de moedas)
│   ├── kyc.js              # Upload KYC com preview
│   ├── admin.js            # Painel admin (estatísticas, aprovações)
│   └── dashboard.js        # Dashboard usuário (propostas, transações)
│
├── api/
│   ├── config.php          # Configuração + funções anti-fraude + JWT
│   ├── auth.php            # API de autenticação
│   ├── social_login.php    # API OAuth (Google, Facebook, Instagram, Apple)
│   ├── kyc.php             # API KYC com validação
│   ├── admin.php           # API admin (12 ações)
│   ├── upload.php          # Handler de uploads
│   └── proposals.php       # API de propostas P2P
│
├── uploads/
│   ├── documents/          # Documentos KYC
│   ├── avatars/            # Fotos de perfil
│   ├── payment_proofs/     # Comprovantes de pagamento
│   └── temp/               # Arquivos temporários
│
└── database/
    └── production_schema.sql  # Schema completo (11 tabelas + dados iniciais)
```

### Estatísticas do Projeto

- **Total de Arquivos:** 30+
- **Linhas de Código:** ~8,500+
- **Páginas HTML:** 10
- **APIs PHP:** 7
- **Scripts JavaScript:** 7
- **Folhas de Estilo:** 5
- **Tabelas no Banco:** 11
- **Moedas Suportadas:** 9

## 🗄️ Banco de Dados

### Credenciais MySQL (Hostinger)

```
Host: localhost
Database: u442547792_transkwanza
Username: u442547792_admin
Password: Life0852new2580!
```

### 11 Tabelas

1. **users** - Usuários do sistema
2. **currencies** - 9 moedas suportadas
3. **proposals** - Propostas de câmbio P2P
4. **transactions** - Transações
5. **uploads** - Registro de arquivos
6. **fraud_checks** - Alertas de fraude
7. **messages** - Chat entre usuários
8. **ratings** - Avaliações
9. **notifications** - Notificações
10. **activity_log** - Log de atividades
11. **admin_actions** - Ações administrativas

### Importar Schema

```bash
# Via phpMyAdmin
1. Acesse phpMyAdmin no Hostinger
2. Selecione o banco u442547792_transkwanza
3. Importar > Selecione database/production_schema.sql
4. Executar

# Via linha de comando
mysql -u u442547792_admin -p u442547792_transkwanza < public_html/database/production_schema.sql
```

## 🚀 Instalação e Deploy

### Passo 1: Upload de Arquivos

```bash
# Via FTP ou File Manager do cPanel
1. Conecte ao servidor Hostinger
2. Faça upload de todos os arquivos da pasta public_html/
3. Mantenha a estrutura de pastas
```

### Passo 2: Configurar Permissões

```bash
# Pastas de upload precisam permissão 755
chmod 755 public_html/uploads
chmod 755 public_html/uploads/documents
chmod 755 public_html/uploads/avatars
chmod 755 public_html/uploads/payment_proofs
chmod 755 public_html/uploads/temp
```

### Passo 3: Importar Banco de Dados

- Acesse phpMyAdmin
- Importe o arquivo `database/production_schema.sql`
- Verifique se as 9 moedas e o admin foram criados

### Passo 4: Configurar OAuth (Opcional)

#### Google OAuth

1. Acesse: https://console.cloud.google.com/
2. Crie um novo projeto
3. Habilite "Google+ API"
4. Criar credenciais > OAuth 2.0 Client ID
5. Tipo: Web application
6. Authorized JavaScript origins: `https://seudominio.com`
7. Authorized redirect URIs: `https://seudominio.com/login.html`
8. Copie o Client ID
9. Edite `js/social-login.js` linha 8 e insira seu Client ID

#### Facebook Login

1. Acesse: https://developers.facebook.com/
2. Criar App > Tipo: Consumer
3. Adicionar produto > Facebook Login
4. Valid OAuth Redirect URIs: `https://seudominio.com/login.html`
5. Copie o App ID
6. Edite `js/social-login.js` linha 11 e insira seu App ID
7. Publicar app (modo Live)

#### Apple Sign In

1. Acesse: https://developer.apple.com/
2. Certificates, IDs & Profiles
3. Criar Services ID
4. Configure Return URLs
5. Edite `js/social-login.js` linhas 14-15

### Passo 5: Ativar SSL

```bash
# No Hostinger
1. Painel > SSL
2. Instalar SSL gratuito (Let's Encrypt)
3. Aguardar propagação (5-10 min)
```

### Passo 6: Testar Instalação

**Teste de Conexão:**

Acesse `https://seudominio.com/test_conexao.php` para verificar:

- ✅ Versão do PHP (>= 7.4)
- ✅ Extensões PHP necessárias (PDO, pdo_mysql, mbstring, json, fileinfo)
- ✅ Conexão com MySQL
- ✅ Verificação de todas as 11 tabelas
- ✅ Moedas cadastradas (9 esperadas)
- ✅ Usuário administrador criado
- ✅ Permissões da pasta uploads/
- ✅ Protocolo HTTPS ativo

**⚠️ IMPORTANTE:** Delete o arquivo `test_conexao.php` após os testes por segurança!

```bash
rm public_html/test_conexao.php
```

**Acesso Admin:**
```
Email: admin@transkwanza.com
Senha: admin123
```

**Fluxo de teste completo:**
1. Acessar página inicial e testar calculadora de conversão
2. Criar conta normal via cadastro
3. Fazer login
4. Verificar redirecionamento para KYC
5. Enviar documentos KYC (frente, verso, selfie)
6. Login como admin e aprovar KYC
7. Retornar ao usuário normal e acessar dashboard
8. Criar proposta de câmbio
9. Buscar propostas disponíveis com filtros
10. Aceitar uma proposta e criar transação
11. Login como admin e aprovar transação
12. Verificar histórico e reputação

## 🔒 Segurança

### Implementado

- ✅ Senhas com bcrypt
- ✅ JWT para autenticação stateless
- ✅ PDO Prepared Statements (SQL Injection)
- ✅ HTTPS forçado via .htaccess
- ✅ Validação de uploads (tipo, tamanho)
- ✅ Headers de segurança (CSP, HSTS, etc)
- ✅ Sistema anti-fraude
- ✅ Rate limiting (configurar)

### Recomendações Adicionais

1. Implementar rate limiting com Redis
2. Adicionar 2FA (autenticação de dois fatores)
3. Monitorar logs regularmente
4. Backup automático do banco
5. Firewall WAF no Hostinger

## 📊 APIs Disponíveis

### Autenticação (`/api/auth.php`)

```javascript
// Cadastro
POST /api/auth.php?action=register
Body: { name, email, password, country, phone }

// Login
POST /api/auth.php?action=login
Body: { email, password }

// Verificar autenticação
GET /api/auth.php?action=check
Headers: { Authorization: "Bearer <token>" }
```

### Social Login (`/api/social_login.php`)

```javascript
// Google
POST /api/social_login.php?provider=google
Body: { access_token }

// Facebook
POST /api/social_login.php?provider=facebook
Body: { access_token }

// Instagram
POST /api/social_login.php?provider=instagram
Body: { access_token }

// Apple
POST /api/social_login.php?provider=apple
Body: { id_token }
```

### KYC (`/api/kyc.php`)

```javascript
// Upload de documentos
POST /api/kyc.php
Headers: { Authorization: "Bearer <token>" }
Body: FormData (multipart/form-data)
  - document_type: "rg" | "cpf" | "cnh" | "passport"
  - document_number: string
  - document_front: file
  - document_selfie: file
  - document_back: file (opcional)

// Status do KYC
GET /api/kyc.php?action=status
Headers: { Authorization: "Bearer <token>" }
```

### Admin (`/api/admin.php`)

Todas as rotas requerem `is_admin = 1`

```javascript
// Estatísticas
GET /api/admin.php?action=stats

// KYC Pendente
GET /api/admin.php?action=pending_kyc

// Aprovar KYC
POST /api/admin.php?action=approve_kyc
Body: { user_id }

// Rejeitar KYC
POST /api/admin.php?action=reject_kyc
Body: { user_id, reason }

// Transações Pendentes
GET /api/admin.php?action=pending_transactions

// Aprovar Transação
POST /api/admin.php?action=approve_transaction
Body: { transaction_id }

// E mais... (ver api/admin.php para todas)
```

### Propostas (`/api/proposals.php`)

```javascript
// Buscar propostas
GET /api/proposals.php?action=search
Params: currency_from, currency_to, min_amount, max_amount

// Criar proposta
POST /api/proposals.php?action=create
Headers: { Authorization: "Bearer <token>" }
Body: {
  type: "buy" | "sell",
  currency_from, currency_to,
  amount_from, exchange_rate,
  payment_method, min_amount, max_amount
}

// Aceitar proposta
POST /api/proposals.php?action=accept
Body: { proposal_id }
```

## 💱 Calculadora de Conversão

### Funcionalidade Principal

A calculadora de conversão em tempo real é uma das principais features do TransKwanza, disponível na landing page (index.html).

**Recursos:**
- Conversão entre todas as 9 moedas suportadas (72 combinações possíveis)
- Cálculo automático de taxa de 3%
- Atualização em tempo real conforme o usuário digita
- Botão de swap para inverter moedas rapidamente
- Interface visual com glassmorphism
- Taxas de câmbio pré-configuradas

**Exemplo de Uso:**

```javascript
// Em currency.js
const converter = new CurrencyConverter();

// Converter 1000 BRL para USD
const result = converter.convert(1000, 'BRL', 'USD');
// result = 200.00 USD

// Com taxa de 3%
const fee = converter.calculateFee(1000);
// fee = 30.00 BRL

const total = result - converter.calculateFee(result);
// total = 194.00 USD (após taxa)
```

**Taxas de Câmbio Implementadas:**

As taxas estão configuradas em `js/currency.js` no objeto `EXCHANGE_RATES`:

```javascript
const EXCHANGE_RATES = {
    'BRL': { 'AOA': 150.25, 'EUR': 0.18, 'USD': 0.20, ... },
    'AOA': { 'BRL': 0.0067, 'EUR': 0.0012, ... },
    // ... todas as 72 combinações
};
```

**Localização:** `index.html` linha 120+ (seção calculator)

## 🎨 Design

### Glassmorphism

O sistema usa design glassmorphism (efeito de vidro fosco):

- Background com gradiente
- Cards semi-transparentes
- Backdrop blur
- Bordas sutis
- Sombras suaves

### Cores

```css
--primary-color: #3498db;
--secondary-color: #2ecc71;
--error-color: #e74c3c;
--warning-color: #f39c12;
```

## ✅ Status do Projeto

### Completamente Implementado

✅ **Sistema Core:**
- Sistema de autenticação completo (tradicional + OAuth)
- Calculadora de conversão em tempo real (72 pares de moedas)
- Dashboard completo do usuário (4 abas funcionais)
- Painel administrativo completo (estatísticas + aprovações)
- Sistema KYC com upload e preview de documentos
- Sistema anti-fraude integrado
- 11 tabelas de banco de dados com relacionamentos

✅ **Páginas Públicas:**
- Landing page com calculadora interativa
- Página de login e cadastro
- Informações sobre 9 países suportados
- Central de suporte com FAQ completo
- Termos de Uso
- Política de Privacidade (LGPD/GDPR)

✅ **Recursos Técnicos:**
- 7 APIs RESTful em PHP
- JWT para autenticação stateless
- PDO com prepared statements (SQL Injection protection)
- Upload seguro de arquivos (validação + detecção de fraude)
- Design responsivo com glassmorphism
- Sistema de notificações toast
- Loading states e tratamento de erros

### 🚀 Melhorias Futuras (Opcional)

Recursos que podem ser adicionados no futuro:

- [ ] App mobile (React Native / Flutter)
- [ ] Gateway de pagamento integrado (Stripe/PayPal)
- [ ] Chat em tempo real (WebSocket / Socket.io)
- [ ] Sistema de reputação avançado com badges
- [ ] Multi-idiomas (i18n) - EN, ES, FR
- [ ] API de câmbio em tempo real (ExchangeRate-API)
- [ ] 2FA (autenticação de dois fatores via SMS/App)
- [ ] Notificações push (web push API)
- [ ] Analytics e relatórios avançados
- [ ] Programa de afiliados

## 🐛 Troubleshooting

### Erro 500

- Verificar logs PHP: `logs/php_errors.log`
- Verificar permissões de arquivos
- Verificar conexão com banco

### CORS Error

- Verificar headers em `api/config.php`
- Em produção, alterar `Access-Control-Allow-Origin` para o domínio específico

### Upload Falha

- Verificar permissões 755 em `uploads/`
- Verificar limite de upload no `.htaccess`
- Verificar `upload_max_filesize` no PHP

### Login Social Não Funciona

- Verificar se Client IDs estão corretos em `js/social-login.js`
- Verificar se URLs de redirect estão configuradas nos consoles
- Verificar se apps estão em modo "Live"

## 📞 Suporte

Para dúvidas ou problemas:

- Email: support@transkwanza.com
- GitHub Issues: (link do repositório)

## 📄 Licença

Todos os direitos reservados © 2024 TransKwanza

---

**Desenvolvido com base no blueprint completo fornecido.**
