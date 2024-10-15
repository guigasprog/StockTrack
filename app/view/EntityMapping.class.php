<?php
use Adianti\Control\TPage;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TAction;
use Adianti\Widget\Datagrid\TActionGroup;
use Adianti\Database\TTransaction;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Util\TTable;

class EntityMapping extends TPage
{
    private $datagrid;

    public function __construct()
    {
        parent::__construct();

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);

        // Definir as colunas do mapeamento
        $col_class  = new TDataGridColumn('class', 'Classe', 'left');
        $col_table  = new TDataGridColumn('table', 'Tabela', 'left');
        $col_fields = new TDataGridColumn('fields', 'Campos', 'left');
        $col_relacionamento = new TDataGridColumn('relacionamento', 'Relacionamentos', 'left');
        $col_entidade_relacionada = new TDataGridColumn('entidade_relacionada', 'Entidade Relacionada', 'left');

        $this->datagrid->addColumn($col_class);
        $this->datagrid->addColumn($col_table);
        $this->datagrid->addColumn($col_fields);
        $this->datagrid->addColumn($col_relacionamento);
        $this->datagrid->addColumn($col_entidade_relacionada);

        $this->datagrid->createModel();

        $panel = new TPanelGroup('Mapeamento de Entidades');
        $panel->add($this->datagrid);
        parent::add($panel);

        // Carregar os dados do mapeamento
        $this->onReload();
    }

    public function onReload()
    {
        try {
            TTransaction::open('development');

            // Exemplo de mapeamento de entidades manual (você pode ajustar para buscar dinamicamente)
            $entities = [
                [
                    'class' => 'Estoque', 
                    'table' => 'estoque', 
                    'fields' => 'produto_id, quantidade, data_entrada', 
                    'relacionamento' => 'One-to-One', 
                    'entidade_relacionada' => 'Produto'
                ],
                [
                    'class' => 'Endereco', 
                    'table' => 'enderecos', 
                    'fields' => 'idEndereco, cep, cidade, estado, bairro, numero, logradouro, complemento', 
                    'relacionamento' => '', 
                    'entidade_relacionada' => ''
                ],
                [
                    'class' => 'Cliente', 
                    'table' => 'clientes', 
                    'fields' => 'id, endereco_id, nome, email, telefone, cpf', 
                    'relacionamento' => 'One-to-One', 
                    'entidade_relacionada' => 'Endereco'
                ],
                [
                    'class' => 'Categoria', 
                    'table' => 'categorias', 
                    'fields' => 'idCategoria, nome, descricao',
                ],
                [
                    'class' => 'Produto', 
                    'table' => 'produtos', 
                    'fields' => 'id, categoria_id, nome, descricao, validade, preco', 
                    'relacionamento' => 'Many-to-One', 
                    'entidade_relacionada' => 'Categoria'
                ],
                [
                    'class' => 'Pedido', 
                    'table' => 'pedidos', 
                    'fields' => 'id, cliente_id, data_pedido, total, status', 
                    'relacionamento' => 'One-to-One', 
                    'entidade_relacionada' => 'Cliente'
                ],
                [
                    'class' => 'PedidoProduto', 
                    'table' => 'pedido_produto', 
                    'fields' => 'id, pedido_id, produto_id, quantidade, preco', 
                    'relacionamento' => 'Many-to-Many', 
                    'entidade_relacionada' => '[Pedido, Produto]'
                ]
            ];

            foreach ($entities as $entity) {
                $this->datagrid->addItem((object) $entity);
            }

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
