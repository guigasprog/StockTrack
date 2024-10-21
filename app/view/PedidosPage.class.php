<?php

use Adianti\Control\TPage;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TInputDialog;
use Adianti\Database\TTransaction;
use Adianti\Database\TRepository;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TDataGridAction;

class PedidosPage extends TPage
{
    private $form;
    private $dataGrid;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('page_pedidos');
        $this->form->setFormTitle('Pedidos');

        $this->dataGrid = new TDataGrid;
        $this->dataGrid->addColumn(new TDataGridColumn('id', 'ID', 'right', 50));
        $this->dataGrid->addColumn(new TDataGridColumn('nome_cliente', 'Nome do Cliente', 'left'));
        $this->dataGrid->addColumn(new TDataGridColumn('total', 'Preço', 'left'));
        $this->dataGrid->addColumn(new TDataGridColumn('status', 'Status', 'center'));

        $action_view_address = new TDataGridAction([$this, 'onViewEndereco'], ['id' => '{id}']);
        $action_view_address->setLabel('Ver Endereço');
        $action_view_address->setImage('fas:eye green');

        $action_view_product = new TDataGridAction([$this, 'onViewProdutos'], ['id' => '{id}']);
        $action_view_product->setLabel('Ver Produtos');
        $action_view_product->setImage('fas:info green');
        
        $this->dataGrid->addAction($action_view_product);
        $this->dataGrid->addAction($action_view_address);

        $this->dataGrid->createModel();

        $this->form->addContent([$this->dataGrid]);

        parent::add($this->form);

        $this->loadDataGrid();
    }

    public function loadDataGrid()
    {
        $this->dataGrid->clear();
        TTransaction::open('development');

        $repository = new TRepository('Pedido');
        $pedidos = $repository->load();

        foreach($pedidos as $pedido) {
            $clienteRepository = new TRepository('Cliente');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('id', '=', $pedido->cliente_id));
            $cliente = $clienteRepository->load($criteria)[0];

            $pedido->nome_cliente = $cliente->nome;
        }

        if ($pedidos) {
            $this->dataGrid->addItems($pedidos);
        }

        TTransaction::close();
    }

    public static function onViewEndereco($param)
    {
        try {
            TTransaction::open('development');
            $repository = new TRepository('Pedido');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('id', '=', $param['id']));
            $pedido = $repository->load($criteria)[0];
            if($pedido) {
                $clienteRepository = new TRepository('Cliente');
                $criteria = new TCriteria;
                $criteria->add(new TFilter('id', '=', $pedido->cliente_id));
                $cliente = $clienteRepository->load($criteria)[0];
                if($cliente) {
                    $enderecoRepository = new TRepository('Endereco');
                    $criteria = new TCriteria;
                    $criteria->add(new TFilter('idEndereco', '=', $cliente->endereco_id));
                    $endereco = $enderecoRepository->load($criteria)[0];
                    if($endereco->numero && $endereco->numero != 'S/N') {
                        $localizacao = $endereco->logradouro.', '.$endereco->numero." - ".$endereco->bairro.", ".$endereco->cidade." - ".$endereco->estado.", ".$endereco->cep;
                    } else {
                        $localizacao = $endereco->cep;
                    }
                    
                    $url = 'https://www.google.com.br/maps/place/'.$localizacao;

                    echo "<script>window.open('{$url}');</script>";
                }

            }
            TTransaction::close();
        }
        catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onViewProdutos($param)
    {
        try {
            TTransaction::open('development');
                
            $dialogForm = new BootstrapFormBuilder('view_produtos');
            $dialogForm->setFieldSizes('100%');

            $pedidoProdutoRepository = new TRepository('PedidoProduto');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('pedido_id', '=', $param['id']));
            $pedidosProdutos = $pedidoProdutoRepository->load($criteria);

            if ($pedidosProdutos) {
                    
                $estoqueTable = new TTable;
                $estoqueTable->style = 'width: 100%; text-align: center';
                $estoqueTable->addRowSet('Nome do Produto', 'Quantidade');

                foreach ($pedidosProdutos as $pedidoProduto) {
                    $quantidade = $pedidoProduto->quantidade ?? '0';
                    $produtoRepository = new TRepository('Produto');
                    $criteria = new TCriteria;
                    $criteria->add(new TFilter('id', '=', $pedidoProduto->produto_id));
                    $produto = $produtoRepository->load($criteria)[0];
                    $nome = $produto->nome ?? "";
                    $estoqueTable->addRowSet($nome, $quantidade);
                }
                
                $dialogForm->add($estoqueTable);

                $dialog = new TInputDialog('Produtos do Pedido', $dialogForm);
            } else {
                new TMessage('info', 'Nenhum produto encontrado para este pedido.');
            }

            TTransaction::close();
        }
        catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

}
