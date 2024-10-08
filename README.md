# StockTrack - Sistema de Gestão de Pedidos e Estoque

**StockTrack** é um sistema robusto e intuitivo para otimizar a gestão de pedidos e o controle de estoque. Criado com foco em escalabilidade, o sistema integra diferentes setores de uma empresa, oferecendo uma visão unificada do fluxo de mercadorias, desde o fornecedor até o cliente final.

## Funcionalidades

- **Gerenciamento de Clientes**: Controle completo das informações dos clientes, incluindo histórico de compras.
- **Catálogo de Produtos**: Acompanhamento de produtos, com controle detalhado de estoque e preços.
- **Pedidos Automatizados**: Registro de pedidos vinculados aos produtos e atualização automática do estoque.
- **Gestão de Fornecedores**: Cadastro e monitoramento de fornecedores para reabastecimento de mercadorias.
- **Relatórios e Análises**: Geração de relatórios detalhados sobre pedidos, estoque e fornecedores.

## Estrutura do Sistema

O sistema é baseado em cinco tabelas principais:

1. **Clientes**:

   - Contém informações como nome, CPF, contato e cidade.
   - Permite o rastreamento do histórico de compras de cada cliente.

2. **Produtos**:

   - Armazena informações do catálogo, incluindo nome, preço e quantidade em estoque.
   - Controla o nível de estoque em tempo real, evitando excessos ou faltas de produtos.
   - Ele esta vincula uma TAG da Tabela de **Categoria**.

3. **Pedidos**:

   - Registra as transações de venda, associando clientes e produtos.
   - Monitora o status de cada pedido, garantindo atualizações precisas no sistema.

4. **Itens do Pedido**:

   - Relaciona os pedidos aos produtos adquiridos, especificando quantidades e valores.
   - Mantém o controle detalhado de cada transação, item por item.

5. **Fornecedores**:
   - Registra dados dos fornecedores responsáveis pelo abastecimento.
   - Automatiza o processo de reabastecimento com base no nível de estoque.

## Movimentos entre as Tabelas

- Quando um **pedido** é criado, o **estoque** de produtos é automaticamente atualizado.
- A tabela de **Itens do Pedido** vincula cada pedido aos produtos, detalhando quantidades e preços.
- A tabela de **Fornecedores** é usada para gerenciar o reabastecimento de produtos, com notificações automáticas quando o estoque está baixo.

## Potencial de Crescimento Futuro

O **StockTrack** foi projetado para crescer com o seu negócio. Algumas melhorias futuras incluem:

- **Gestão Financeira**: Adição de relatórios de faturamento e despesas vinculadas aos pedidos.
- **Integração Multicanal**: Controle de pedidos de múltiplos canais de venda (e-commerce, loja física).
- **Análises Preditivas**: Relatórios que utilizam dados históricos para prever demanda e sugerir reabastecimentos.
- **Automação Total**: Integração com sistemas de ERP e automação logística para otimizar processos de ponta a ponta.

## Instalação

Clone o repositório:

```bash
git clone https://github.com/guigasprog/stocktrack.git
```

Instale as dependências necessárias e configure o banco de dados conforme a estrutura de tabelas fornecida.

## Contribuições

Contribuições são bem-vindas! Sinta-se à vontade para abrir issues ou enviar pull requests.

## Licença

Este projeto não está licenciado.

---

**StockTrack** - Um sistema completo para a gestão eficiente de pedidos e estoque, pronto para escalar com o seu negócio.
