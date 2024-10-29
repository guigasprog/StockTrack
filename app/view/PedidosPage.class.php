<?php

use Adianti\Control\TPage;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Database\TTransaction;
use Adianti\Database\TRepository;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TInputDialog;

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
        $this->dataGrid->addColumn(new TDataGridColumn('id', 'ID', 'left', '5%'));
        $this->dataGrid->addColumn(new TDataGridColumn('nome_cliente', 'Nome do Cliente', 'left', '45%'));
        $this->dataGrid->addColumn(new TDataGridColumn('total', 'Preço', 'left', '20%'));
        $this->dataGrid->addColumn(new TDataGridColumn('status', 'Status', 'left', '30%'));

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
        try {
            TTransaction::open('development');
            $repository = new TRepository('Pedido');
            $pedidos = $repository->load();

            foreach ($pedidos as $pedido) {
                $cliente = $this->loadCliente($pedido->cliente_id);
                $pedido->nome_cliente = $cliente->nome ?? 'Desconhecido';
            }

            if ($pedidos) {
                $this->dataGrid->addItems($pedidos);
            }

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    private function loadCliente($cliente_id)
    {
        $clienteRepository = new TRepository('Cliente');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id', '=', $cliente_id));
        return $clienteRepository->load($criteria)[0] ?? null;
    }

    private function loadEndereco($cliente_id)
    {
        $cliente = $this->loadCliente($cliente_id);

        if ($cliente) {
            $enderecoRepository = new TRepository('Endereco');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('idEndereco', '=', $cliente->endereco_id));
            return $enderecoRepository->load($criteria)[0] ?? null;
        }

        return null;
    }

    public static function onViewEndereco($param)
    {
        try {
            TTransaction::open('development');
            $pedido_id = $param['id'] ?? null;

            if ($pedido_id) {
                $repository = new TRepository('Pedido');
                $pedido = $repository->load(new TCriteria([new TFilter('id', '=', $pedido_id)]))[0] ?? null;

                if ($pedido) {
                    $endereco = (new self)->loadEndereco($pedido->cliente_id);
                    $localizacao = ($endereco->numero && $endereco->numero != 'S/N')
                        ? "{$endereco->logradouro}, {$endereco->numero} - {$endereco->bairro}, {$endereco->cidade} - {$endereco->estado}, {$endereco->cep}"
                        : $endereco->cep;

                    $url = 'https://www.google.com.br/maps/place/' . urlencode($localizacao);
                    echo "<script>window.open('{$url}');</script>";
                }
            }

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onViewProdutos($param)
    {
        try {
            TTransaction::open('development');
            $pedido_id = $param['id'] ?? null;

            if ($pedido_id) {
                $pedidosProdutos = $this->loadProdutosPedido($pedido_id);

                if ($pedidosProdutos) {
                    $dialogForm = new BootstrapFormBuilder('view_produtos');
                    $dialogForm->setFieldSizes('100%');
                    
                    $estoqueTable = new TTable;
                    $estoqueTable->style = 'width: 100%; text-align: center';
                    $estoqueTable->addRowSet('Nome do Produto', 'Quantidade');

                    foreach ($pedidosProdutos as $pedidoProduto) {
                        $produto = $this->loadProduto($pedidoProduto->produto_id);
                        $estoqueTable->addRowSet($produto->nome ?? 'Desconhecido', $pedidoProduto->quantidade ?? '0');
                    }

                    $dialogForm->add($estoqueTable);
                    new TInputDialog('Produtos do Pedido', $dialogForm);
                } else {
                    new TMessage('info', 'Nenhum produto encontrado para este pedido.');
                }
            }

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    private function loadProdutosPedido($pedido_id)
    {
        $pedidoProdutoRepository = new TRepository('PedidoProduto');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('pedido_id', '=', $pedido_id));
        return $pedidoProdutoRepository->load($criteria);
    }

    private function loadProduto($produto_id)
    {
        $produtoRepository = new TRepository('Produto');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id', '=', $produto_id));
        return $produtoRepository->load($criteria)[0] ?? null;
    }
}
