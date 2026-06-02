# Sistema Pré-Triagem (CardioPrenatal)

**Repositório:** [github.com/Cajorgino/sistemapretriagem](https://github.com/Cajorgino/sistemapretriagem)

## 📌 Sobre o Projeto
O **CardioPrenatal** é uma plataforma especializada no acompanhamento de gestantes, com foco na análise de riscos cardiovasculares e comorbidades durante o período gestacional. O sistema centraliza dados de consultas, realiza análises estatísticas automáticas via Python e fornece insights visuais para auxiliar na tomada de decisão clínica.

## 🚀 Funcionalidades Principais

### 1. Painel de Controle (Dashboard)
- **Indicadores Chave (KPIs):** Visualização rápida do total de gestantes atendidas, total de consultas realizadas e casos confirmados de CHD (Cardiopatia Congênita).
- **Análise Estatística:** Tabela detalhada com média, desvio padrão, valores mínimos/máximos e percentis de variáveis clínicas.
- **Análise de Risco:** Agrupamento de pacientes por níveis de risco, monitorando pressão sistólica, frequência cardíaca fetal e idade gestacional.
- **Monitoramento de Comorbidades:** Cruzamento de dados entre diferentes condições de saúde e indicadores vitais.
- **Visualização de Dados:** Exibição de gráficos gerados automaticamente para análise de tendências.

### 2. Gestão de Pacientes
- Cadastro e listagem completa de gestantes.
- Histórico de consultas e evolução do quadro clínico.

### 3. Integração de Dados
- **Importação via CSV:** Facilidade para subir grandes volumes de dados de consultas externas para o sistema.

### 4. Segurança e Administração
- Autenticação robusta (incluindo 2FA - Autenticação de dois fatores).
- Gestão de perfis (Administradores e Editores).
- Gerenciamento de tokens de API para integrações de terceiros.
- Suporte a múltiplos idiomas (atualmente com tradução completa para PT-BR).

## 🛠️ Tecnologias Utilizadas

### Backend & Frameworks
- **PHP / Laravel:** Framework principal para lógica de negócio, rotas e autenticação.
- **Python:** Responsável pelo motor de análise de dados e geração de gráficos estatísticos.
- **Blade:** Engine de templates para a interface do usuário.

### Frontend
- **Tailwind CSS:** Framework utilitário para um design moderno e responsivo.
- **Heroicons:** Conjunto de ícones integrados.

### Bibliotecas Adicionais
- **RxJS:** Utilizado no ecossistema Node para processamento reativo (se aplicável ao frontend).
- **Vite:** Ferramenta de build para um desenvolvimento frontend ágil.

## 📂 Estrutura de Arquivos Relevantes

- `resources/views/dashboard.blade.php`: Interface principal de visualização de dados.
- `lang/pt_BR.json`: Arquivo de tradução e localização para o português brasileiro.
- `routes/web.php`: Definição das rotas de navegação e ações do sistema.

## 🔧 Configuração do Ambiente

### Pré-requisitos
- PHP 8.x
- Composer
- Node.js & NPM
- Python 3.x (com bibliotecas Pandas, Matplotlib/Seaborn)

### Instalação
1. Clone o repositório:
   ```bash
   git clone https://github.com/Cajorgino/sistemapretriagem.git
   ```
2. Instale as dependências do PHP:
   ```bash
   composer install
   ```
3. Instale as dependências do Node:
   ```bash
   npm install && npm run dev
   ```
4. Configure o arquivo `.env` com suas credenciais de banco de dados.
5. Execute as migrações:
   ```bash
   php artisan migrate
   ```
6. Inicie o servidor:
   ```bash
   php artisan serve
   ```

---
*Desenvolvido para auxiliar a saúde materna e fetal através de dados.*