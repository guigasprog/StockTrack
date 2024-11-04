<?php
use Adianti\Control\TPage;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TActionGroup;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Database\TTransaction;

class EntityMapping extends TPage
{
    private $datagrid;

    public function __construct()
    {
        parent::__construct();

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->setHeight(400);

        $col_class = new TDataGridColumn('class', 'Classe', 'left', '15%');
        $col_class->setTransformer(function($value) {
            return "<span style='font-weight:bold; color: #2A9D8F;'>$value</span>";
        });

        $col_table = new TDataGridColumn('table', 'Tabela', 'left', '15%');
        $col_fields = new TDataGridColumn('fields', 'Campos', 'left', '25%');

        $col_relacionamento = new TDataGridColumn('relacionamento', 'Relacionamento', 'center', '15%');
        $col_relacionamento->setTransformer(function($value) {
            if ($value == 'One-to-One') {
                return '<i class="fas fa-link" style="color:#8E44AD;"></i> ' . $value;
            } elseif ($value == 'Many-to-One') {
                return '<i class="fas fa-share-alt" style="color:#3498DB;"></i> ' . $value;
            } elseif ($value == 'Many-to-Many') {
                return '<i class="fas fa-project-diagram" style="color:#E74C3C;"></i> ' . $value;
            } else {
                return $value;
            }
        });

        $col_entidade_relacionada = new TDataGridColumn('entidade_relacionada', 'Entidade Relacionada', 'left', '20%');

        $this->datagrid->addColumn($col_class);
        $this->datagrid->addColumn($col_table);
        $this->datagrid->addColumn($col_fields);
        $this->datagrid->addColumn($col_relacionamento);
        $this->datagrid->addColumn($col_entidade_relacionada);

        $this->datagrid->createModel();

        $panel = new TPanelGroup('Mapeamento de Entidades');
        $panel->add($this->datagrid);
        $panel->style = 'background:#F9F9F9; border: 1px solid #DDD;';

        parent::add($panel);
        $this->onReload();
    }

    public function onReload()
    {
        try {
            TTransaction::open('development');

            $entities = [
                [
                    'class' => 'Estoque', 
                    'table' => 'estoque', 
                    'fields' => 'id, produto_id, quantidade, data_entrada', 
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
                    'relacionamento' => '',
                    'entidade_relacionada' => ''
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
