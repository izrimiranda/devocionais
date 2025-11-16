---
applyTo: '**'
---
# 📖 CONTEXTO DO PROJETO - Devocionais Pr. Luciano Miranda

> **Última Atualização**: 15 de Novembro de 2025  
> **Versão**: 2.0  
> **Desenvolvedor**: Código 1615  
> **Cliente**: Pr. Luciano Miranda

---

## 📋 ÍNDICE

1. [Visão Geral](#visão-geral)
2. [Informações do Servidor](#informações-do-servidor)
3. [Estrutura do Projeto](#estrutura-do-projeto)
4. [Banco de Dados](#banco-de-dados)
5. [Funcionalidades Implementadas](#funcionalidades-implementadas)
6. [APIs e Integrações](#apis-e-integrações)
7. [Sistema de Analytics](#sistema-de-analytics)
8. [Identidade Visual](#identidade-visual)
9. [Problemas Conhecidos e Soluções](#problemas-conhecidos-e-soluções)
10. [Comandos Úteis](#comandos-úteis)
11. [Roadmap Futuro](#roadmap-futuro)

---

## 🎯 VISÃO GERAL

### Descrição
Site de devocionais diários do Pr. Luciano Miranda, com sistema de gerenciamento (CRUD), analytics, curtidas, e compartilhamento em redes sociais.

### Tecnologias
- **Backend**: PHP 8.2.29
- **Banco de Dados**: MySQL 5.7
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Hosting**: Hostinger (srv723.hstgr.io)
- **Domínio**: https://pastorluciano.codigo1615.com.br

### Principais Características
- ✅ CRUD completo de devocionais
- ✅ Sistema de curtidas (likes)
- ✅ Analytics com rastreamento de visitas
- ✅ Compartilhamento WhatsApp e Instagram
- ✅ Otimização automática de imagens
- ✅ Player de áudio personalizado
- ✅ Busca por título e conteúdo
- ✅ Design responsivo mobile-first
- ✅ Meta tags otimizadas para WhatsApp/Facebook

---

## 🖥️ INFORMAÇÕES DO SERVIDOR

### Credenciais de Acesso
```
Host: srv723.hstgr.io
Database: u959347836_db_luciano
User: u959347836_luciano_user
Port: 3306
```

### Estrutura de Deploy
```
public_html/
├── pastorluciano/          # Raiz do site
│   ├── index.php           # Página inicial
│   ├── admin/              # Painel administrativo
│   ├── api/                # Endpoints JSON
│   ├── assets/             # CSS, JS, imagens
│   ├── config/             # Configurações
│   ├── database/           # SQL scripts
│   ├── devocionais/        # Páginas geradas
│   ├── templates/          # Templates PHP
│   └── uploads/            # Arquivos do usuário
```

### Permissões Importantes
- `uploads/` → 755 (escrita permitida)
- `devocionais/` → 755 (geração dinâmica de arquivos)
- `data/` → 700 (protegido, apenas PHP acessa)

---

## 📁 ESTRUTURA DO PROJETO

### Diretórios Principais

#### `/admin/` - Painel Administrativo
```
admin/
├── login.php           # Autenticação
├── dashboard.php       # Lista de devocionais
├── create.php          # Criar devocional
├── edit.php            # Editar devocional
├── delete.php          # Deletar devocional
├── analytics.php       # Dashboard de estatísticas
├── optimize-images.php # Otimização em massa
├── regenerate-all.php  # Regenerar páginas
└── logout.php          # Sair
```

#### `/api/` - Endpoints JSON
```
api/
├── track.php           # Registrar visita (analytics)
├── get-analytics.php   # Obter estatísticas
├── like.php            # Curtir/descurtir
├── get-likes.php       # Obter curtidas
└── submit.php          # (Futuro) Formulários
```

#### `/config/` - Configurações
```
config/
├── db.php              # Conexão PDO
├── auth.php            # Autenticação admin
├── helpers.php         # Funções auxiliares
└── security.php        # Sanitização e validação
```

#### `/templates/` - Templates Reutilizáveis
```
templates/
├── header.php          # Cabeçalho global
├── footer.php          # Rodapé global
├── single-devotional.php # Template de devocional
├── devotional-card.php   # Card para listagem
├── audio-player.php      # Player de áudio
└── 404.php               # Página de erro
```

#### `/assets/` - Recursos Estáticos
```
assets/
├── css/
│   ├── main.css        # Estilos do frontend
│   └── admin.css       # Estilos do admin
├── js/
│   ├── main.js         # Scripts gerais
│   ├── devotionals.js  # Likes e compartilhamento
│   ├── analytics.js    # Tracking automático
│   ├── menu.js         # Menu mobile
│   └── admin.js        # Painel admin
└── images/
    └── (imagens estáticas)
```

---

## 🗄️ BANCO DE DADOS

### Tabela: `devotionals`
```sql
CREATE TABLE devotionals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(255) UNIQUE NOT NULL,
    title VARCHAR(500) NOT NULL,
    content_html TEXT NOT NULL,
    texto_aureo TEXT,
    serie VARCHAR(200),
    numero_devocional INT,
    ano YEAR,
    image_path VARCHAR(500),
    audio_path VARCHAR(500),
    published_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_published (published_at),
    INDEX idx_slug (slug),
    INDEX idx_serie (serie, numero_devocional)
);
```

### Tabela: `devotional_likes`
```sql
CREATE TABLE devotional_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    devotional_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (devotional_id, ip_address),
    FOREIGN KEY (devotional_id) REFERENCES devotionals(id) ON DELETE CASCADE,
    INDEX idx_devotional (devotional_id)
);
```

### Tabela: `analytics`
```sql
CREATE TABLE analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_type ENUM('home', 'devotional', 'search', 'other') NOT NULL DEFAULT 'other',
    devotional_id INT NULL,
    page_url VARCHAR(500) NOT NULL,
    referrer VARCHAR(500) NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    device_type ENUM('desktop', 'mobile', 'tablet') NULL,
    browser VARCHAR(100) NULL,
    os VARCHAR(100) NULL,
    session_id VARCHAR(64) NULL,
    visited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (devotional_id) REFERENCES devotionals(id) ON DELETE SET NULL,
    INDEX idx_analytics_stats (page_type, visited_at, devotional_id)
);
```

### Executar SQLs Pendentes
```bash
# Via MySQL CLI
mysql -h srv723.hstgr.io -u u959347836_luciano_user -p u959347836_db_luciano < database/add_likes_table.sql
mysql -h srv723.hstgr.io -u u959347836_luciano_user -p u959347836_db_luciano < database/add_analytics_table.sql

# Via phpMyAdmin
# Copiar conteúdo de database/*.sql e executar na aba SQL
```

---

## ⚙️ FUNCIONALIDADES IMPLEMENTADAS

### 1. Sistema de Curtidas (Likes)
**Arquivos**: `api/like.php`, `api/get-likes.php`, `assets/js/devotionals.js`

- Identificação por IP + User-Agent
- Prevenção de duplicatas (UNIQUE constraint)
- UI otimista (atualiza antes da resposta)
- Animação heartBeat no botão
- Contador em tempo real

**Uso**:
```javascript
// Automático ao carregar a página
loadLikes(); // Carrega estado inicial

// Ao clicar no botão
<button class="btn-like" data-devotional-id="123">
    <span class="heart-icon">♡</span>
    <span class="like-count">0</span>
</button>
```

### 2. Sistema de Analytics
**Arquivos**: `api/track.php`, `api/get-analytics.php`, `admin/analytics.php`, `assets/js/analytics.js`

**Métricas Rastreadas**:
- Total de visitas
- Visitantes únicos (IP)
- Visitas por dia (gráfico)
- Devocionais mais visitados
- Tipos de dispositivo
- Navegadores
- Sistemas operacionais
- URLs mais acessadas

**Tracking Automático**:
```javascript
// analytics.js registra automaticamente cada pageview
// Detecta: home, devotional, search, other
// Extrai devotional_id da meta tag
```

**Dashboard Analytics**:
- URL: `/admin/analytics.php`
- Filtros: 7 dias, 30 dias, 90 dias, todo período
- Gráficos: Chart.js (linha, doughnut, barras)
- Exportação: Em desenvolvimento

### 3. Compartilhamento Social
**Arquivos**: `templates/single-devotional.php`, `assets/js/devotionals.js`

#### WhatsApp
- Mensagem customizada com emoji
- Link direto para o devocional
- Formato: "Olá, como vai? 😊\n\nAcabei de ler esse devocional..."

#### Instagram Stories
- Copia link automaticamente
- Abre app do Instagram (mobile) ou web (desktop)
- Deep link: `instagram://story-camera`
- **Limitação**: API oficial requer aprovação Meta

#### Copiar Link
- Clipboard API com fallback
- Notificação toast de sucesso

### 4. Otimização de Imagens
**Arquivo**: `admin/optimize-images.php`

**Processo 3 Estágios**:
1. **Qualidade**: 85% → 40% (loop até <600KB)
2. **Redimensionamento**: Max 1200px (mantém aspect ratio)
3. **Conversão PNG→JPEG**: Atualiza database automaticamente

**WhatsApp Requirements**:
- Tamanho: <600 KB
- Largura mínima: 300px
- Aspect ratio máximo: 4:1
- Dimensões precisas nas meta tags

### 5. Player de Áudio Personalizado
**Arquivo**: `templates/audio-player.php`

- Play/Pause
- Barra de progresso interativa
- Tempo decorrido / total
- Download do áudio
- Design responsivo

### 6. Busca
**Arquivo**: `search.php`

- Busca em título e conteúdo
- Sanitização de query
- Resultados em cards
- Mensagem quando vazio

### 7. Geração Dinâmica de Páginas
**Arquivos**: `admin/generate-file.php`, `admin/regenerate-all.php`

- Cria arquivos físicos em `/devocionais/`
- SEO-friendly URLs: `/devocionais/slug-do-devocional.php`
- Regeneração em massa para atualizar meta tags
- Fallback dinâmico com `default.php` se arquivo não existir

---

## 🔌 APIS E INTEGRAÇÕES

### Endpoints Disponíveis

#### POST `/api/track.php`
Registra visita de usuário

**Request**:
```json
{
  "page_type": "devotional",
  "devotional_id": 123,
  "page_url": "/devocionais/exemplo"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Visita registrada",
  "session_id": "sha256hash..."
}
```

#### GET `/api/get-analytics.php`
Obter estatísticas

**Parâmetros**:
- `type`: `overview`, `devotionals`, `pages`, `devices`
- `period`: `7days`, `30days`, `90days`, `all`

**Response**:
```json
{
  "success": true,
  "period": "30days",
  "stats": {
    "total_visits": 1500,
    "unique_visitors": 850,
    "daily_visits": [...],
    "page_types": [...]
  }
}
```

#### POST `/api/like.php`
Curtir/descurtir devocional

**Request**:
```json
{
  "devotional_id": 123,
  "action": "toggle"
}
```

**Response**:
```json
{
  "success": true,
  "liked": true,
  "total_likes": 42,
  "message": "Curtida registrada"
}
```

#### GET `/api/get-likes.php`
Obter curtidas de um devocional

**Parâmetros**:
- `devotional_id`: ID do devocional

**Response**:
```json
{
  "success": true,
  "total_likes": 42,
  "user_liked": true
}
```

---

## 📊 SISTEMA DE ANALYTICS

### Implementação

#### 1. Tracking Automático (Frontend)
```javascript
// analytics.js - Incluído em todas as páginas via header.php
// Detecta página automaticamente
// Envia POST para /api/track.php
// Cookie de sessão: 30 dias
```

#### 2. Armazenamento (Backend)
- Tabela `analytics` com 11 campos
- Índices otimizados para consultas rápidas
- Session ID para rastrear jornada do usuário

#### 3. Dashboard (Admin)
- Gráficos com Chart.js 4.4.0
- 4 cards de resumo (visitas, únicos, mobile, desktop)
- Gráfico de linha: visitas por dia (30 dias)
- Tabs: Devocionais | Dispositivos | Páginas

#### 4. Detectores Automáticos
```php
// api/track.php
detectDeviceType($userAgent);  // desktop, mobile, tablet
detectBrowser($userAgent);      // Chrome, Firefox, Safari...
detectOS($userAgent);           // Windows, Mac, Android, iOS...
getClientIP();                  // IP real (considera proxies)
```

### Métricas Disponíveis

| Métrica | Descrição | Tipo |
|---------|-----------|------|
| Total de Visitas | Pageviews totais | Número |
| Visitantes Únicos | IPs distintos | Número |
| Visitas por Dia | Gráfico temporal | Linha |
| Devocionais Top 20 | Mais acessados | Tabela |
| Device Types | Desktop/Mobile/Tablet | Doughnut |
| Navegadores | Chrome, Firefox, etc | Barra horizontal |
| Sistemas | Windows, Android, iOS | Lista |
| URLs | Páginas mais vistas | Tabela |

### Como Usar

1. **Executar SQL**:
```bash
mysql -h srv723.hstgr.io -u u959347836_luciano_user -p u959347836_db_luciano < database/add_analytics_table.sql
```

2. **Acessar Dashboard**:
```
https://pastorluciano.codigo1615.com.br/admin/analytics.php
```

3. **Filtrar Período**:
- Dropdown no topo: 7/30/90 dias ou todo período
- Gráficos atualizam automaticamente

4. **Exportar Dados** (Futuro):
```php
// Em desenvolvimento
/admin/export-analytics.php?format=csv&period=30days
```

---

## 🎨 IDENTIDADE VISUAL

### Paleta de Cores

#### Primária
- **Azul Principal**: `#0055bd`
- **Azul Escuro**: `#003d8f`
- **Azul Claro**: `#3d8bff`
- **Azul Muito Claro**: `#a6c8ff`

#### Gradientes
```css
--gradient-primary: linear-gradient(135deg, #0055bd 0%, #0a6fe3 50%, #33a1ff 100%);
--gradient-dark: linear-gradient(135deg, #003d8f 0%, #0055bd 100%);
--gradient-light: linear-gradient(135deg, #3d8bff 0%, #a6c8ff 100%);
```

#### Neutras
- Branco: `#ffffff`
- Cinza 100: `#f5f7fa`
- Cinza 200: `#e5e9f0`
- Cinza 800: `#1a202c`
- Cinza 900: `#0d1e33`

#### Feedback
- Sucesso: `#48bb78`
- Erro: `#f56565`
- Aviso: `#ed8936`
- Info: `#4299e1`

### Tipografia
```css
font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
```

### Logo e Imagens
- **Logo Site**: https://i.imgur.com/Jpaf0oW.png (1200x630px)
- **Favicon**: Mesma imagem do devocional ou logo padrão
- **Placeholder**: Imagem padrão quando não há upload

### Design System

#### Espaçamentos
```css
--spacing-xs: 0.5rem;   /* 8px */
--spacing-sm: 1rem;     /* 16px */
--spacing-md: 1.5rem;   /* 24px */
--spacing-lg: 2rem;     /* 32px */
--spacing-xl: 3rem;     /* 48px */
--spacing-2xl: 4rem;    /* 64px */
```

#### Bordas
```css
--radius-sm: 6px;
--radius-md: 10px;
--radius-lg: 16px;
--radius-full: 9999px;
```

#### Sombras
```css
--shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.06);
--shadow-md: 0 4px 12px rgba(0, 0, 0, 0.10);
--shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.15);
--shadow-xl: 0 16px 48px rgba(0, 0, 0, 0.20);
```

---

## 🐛 PROBLEMAS CONHECIDOS E SOLUÇÕES

### 1. WhatsApp Preview Não Aparece

**Problema**: Imagens não exibem no preview ao compartilhar

**Causas**:
- Imagem >600 KB
- Dimensões incorretas nas meta tags
- Cache do WhatsApp

**Solução**:
```bash
# 1. Otimizar imagens
/admin/optimize-images.php

# 2. Regenerar páginas
/admin/regenerate-all.php

# 3. Forçar scrape no Facebook Debugger
https://developers.facebook.com/tools/debug/
# Clicar em "Extrair novamente" 10-15 vezes

# 4. Aguardar 24-48h para cache global limpar
```

### 2. Dois Ícones no Dock (Electron Apps)

**Problema**: App Electron mostra ícone duplicado

**Solução**:
```javascript
// main.js
app.setDesktopName('nome-app-electron.desktop');
app.setName('nome-app-electron');

// .desktop file
StartupWMClass=nome-app-electron
```

### 3. HTML Entities em Aspas

**Problema**: `&quot;` aparece no texto áureo

**Solução**:
```php
// config/helpers.php
function sanitizeString($str) {
    return trim(strip_tags($str)); // NÃO usar htmlspecialchars
}
```

### 4. Likes Não Contam

**Problema**: Tabela `devotional_likes` não existe

**Solução**:
```bash
mysql -h srv723.hstgr.io -u u959347836_luciano_user -p u959347836_db_luciano < database/add_likes_table.sql
```

### 5. Analytics Vazio

**Problema**: Sem dados de visitas

**Checklist**:
```bash
# 1. Verificar se tabela existe
SELECT * FROM analytics LIMIT 1;

# 2. Verificar se script está carregando
view-source:https://pastorluciano.codigo1615.com.br/
# Buscar por: <script src="/assets/js/analytics.js"

# 3. Testar endpoint manualmente
curl -X POST https://pastorluciano.codigo1615.com.br/api/track.php \
  -H "Content-Type: application/json" \
  -d '{"page_type":"home","page_url":"/"}'

# 4. Aguardar visitas reais (tracking é assíncrono)
```

### 6. Imagens Muito Grandes

**Problema**: Upload de 5MB+ falha

**Solução**:
```php
// php.ini ou .htaccess
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 300

// Depois executar otimização
/admin/optimize-images.php
```

---

## 💻 COMANDOS ÚTEIS

### Banco de Dados

```bash
# Conectar ao MySQL
mysql -h srv723.hstgr.io -u u959347836_luciano_user -p u959347836_db_luciano

# Backup completo
mysqldump -h srv723.hstgr.io -u u959347836_luciano_user -p u959347836_db_luciano > backup_$(date +%Y%m%d).sql

# Restaurar backup
mysql -h srv723.hstgr.io -u u959347836_luciano_user -p u959347836_db_luciano < backup_20251115.sql

# Executar SQL file
mysql -h srv723.hstgr.io -u u959347836_luciano_user -p u959347836_db_luciano < database/add_analytics_table.sql

# Contar registros
SELECT 
  (SELECT COUNT(*) FROM devotionals) as total_devotionals,
  (SELECT COUNT(*) FROM devotional_likes) as total_likes,
  (SELECT COUNT(*) FROM analytics) as total_visits;

# Devocionais mais curtidos
SELECT d.title, COUNT(l.id) as likes
FROM devotionals d
LEFT JOIN devotional_likes l ON d.id = l.devotional_id
GROUP BY d.id
ORDER BY likes DESC
LIMIT 10;

# Analytics resumo
SELECT 
  DATE(visited_at) as dia,
  COUNT(*) as visitas,
  COUNT(DISTINCT ip_address) as visitantes_unicos
FROM analytics
WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(visited_at)
ORDER BY dia DESC;
```

### Manutenção

```bash
# Limpar cache de imagens
find uploads/images -type f -mtime +90 -name "backup_*" -delete

# Verificar permissões
ls -la uploads/
ls -la devocionais/

# Corrigir permissões
chmod 755 uploads/
chmod 755 devocionais/
chmod 755 data/

# Ver logs de erro PHP
tail -f /path/to/error.log

# Testar conexão DB
php -r "require 'config/db.php'; echo 'DB OK';"

# Regenerar todas as páginas
php admin/regenerate-all.php
```

### Deploy

```bash
# Via FTP/FileZilla
# Upload apenas arquivos modificados
# Preservar permissões de pastas

# Via Git (se configurado)
git pull origin main
php admin/regenerate-all.php

# Verificar status
curl -I https://pastorluciano.codigo1615.com.br/
```

---

## 🚀 ROADMAP FUTURO

### Em Desenvolvimento
- [ ] Exportação CSV/Excel do analytics
- [ ] Filtro de devocionais por série/ano
- [ ] Sistema de comentários
- [ ] Newsletter por e-mail
- [ ] Notificações push
- [ ] Temas claro/escuro
- [ ] Traduções (inglês/espanhol)

### Planejado
- [ ] API REST completa com autenticação JWT
- [ ] App mobile nativo (React Native)
- [ ] Versão PDF dos devocionais
- [ ] Playlist de áudios
- [ ] Integração com YouTube
- [ ] Sistema de favoritos
- [ ] Compartilhamento no Telegram
- [ ] QR Code por devocional

### Ideias Futuras
- [ ] Gamificação (badges por leitura)
- [ ] Plano de leitura personalizado
- [ ] Chatbot de oração
- [ ] Integração com Bible API
- [ ] Versão AMP (Google)
- [ ] PWA (Progressive Web App)
- [ ] Modo offline
- [ ] Sincronização entre dispositivos

---

## 📞 SUPORTE E CONTATO

### Desenvolvedor
- **Nome**: Código 1615
- **Website**: codigo1615.com.br
- **E-mail**: contato@codigo1615.com.br

### Cliente
- **Pastor**: Luciano Miranda
- **Igreja**: Verbo da Vida - Pedro Leopoldo
- **Instagram**: @lucianovieiramiranda
- **Facebook**: facebook.com/luciano.vieiramiranda

### Links Importantes
- **Site Produção**: https://pastorluciano.codigo1615.com.br
- **Admin Panel**: https://pastorluciano.codigo1615.com.br/admin/
- **Facebook Debugger**: https://developers.facebook.com/tools/debug/
- **Google PageSpeed**: https://pagespeed.web.dev/

---

## 📝 NOTAS FINAIS

### Boas Práticas
1. Sempre fazer backup antes de mudanças no DB
2. Testar em ambiente local antes de deploy
3. Otimizar imagens antes do upload
4. Regenerar páginas após mudanças nos templates
5. Limpar cache do WhatsApp após otimizações
6. Monitorar analytics semanalmente
7. Verificar erros PHP nos logs

### Segurança
- Senhas fortes com hash bcrypt
- Session timeout de 1 hora
- CSRF tokens em formulários
- Sanitização de inputs
- Prepared statements (PDO)
- HTTPS obrigatório
- Headers de segurança

### Performance
- Imagens <600 KB
- CSS/JS minificados (em produção)
- Cache de 30 dias para assets
- Lazy loading de imagens
- CDN para fonts (Google Fonts)
- Índices otimizados no DB
- Queries com LIMIT

---

**Última modificação**: 15/11/2025 às 18:30 BRT  
**Versão do documento**: 2.0  
**Status do projeto**: Em produção ativa ✅
