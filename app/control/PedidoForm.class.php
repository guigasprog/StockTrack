<?php

use Adianti\Control\TPage;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TButton;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Database\TTransaction;
use Adianti\Database\TRepository;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Control\TAction;

class PedidoForm extends TPage
{
    private $form;
    private $produto_id;
    private $produtos = [];
    private $estoqueTable;

    public function __construct()
    {
        parent::__construct();
        
        $this->form = new BootstrapFormBuilder('form_pedido');
        $this->form->setFormTitle('Cadastro de Pedido');

        // Criação dos campos do formulário
        $id         = new TEntry('id');
        $cliente_id = new TDBCombo('cliente_id', 'development', 'Cliente', 'id', 'nome', 'nome');
        $this->produto_id = new TDBCombo('produto_id', 'development', 'Produto', 'id', 'nome', 'nome');
        $quantidade = new TEntry('quantidade');
        
        $id->setEditable(FALSE);
        $quantidade->setSize('100%');
        $quantidade->setValue(1);

        $this->form->addFields([new TLabel('ID')], [$id]);
        $this->form->addFields([new TLabel('Cliente')], [$cliente_id]);
        $this->form->addFields([new TLabel('Produto')], [$this->produto_id]);
        $this->form->addFields([new TLabel('Quantidade')], [$quantidade]);

        $this->estoqueTable = new TTable;
        $this->estoqueTable->style = 'width: 100%; text-align: center';
        $this->estoqueTable->addRowSet('Nome do Produto', 'Quantidade');
        $this->updateProductTable();

        $this->form->addAction('Adicionar Produto à Lista', new TAction([$this, 'addProdutos']), 'fas:plus');
        $this->form->addAction('Salvar Pedido', new TAction([$this, 'onSave']), 'fas:save');
        
        parent::add($this->form);

        $this->loadAvailableProducts();
    }

    public function loadAvailableProducts()
    {
        try {
            TTransaction::open('development');

            $repository = new TRepository('Estoque');
            $criteria = new TCriteria();
            $criteria->add(new TFilter('quantidade', '>', 0));

            $estoques = $repository->load($criteria);

            if ($estoques) {
                $produtos_disponiveis = [];
                foreach ($estoques as $estoque) {
                    $produto = new Produto($estoque->produto_id);
                    $produtos_disponiveis[$produto->id] = $produto->nome;
                }

                if ($this->produto_id) {
                    $this->produto_id->addItems($produtos_disponiveis);
                }
            }

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function addProdutos($param)
    {
        try {
            TTransaction::open('development');
            
            $data = $this->form->getData();
            $produto = new Produto($data->produto_id);
            $quantidade = $data->quantidade;
            
            $item = new stdClass;
            $item->id = $produto->id;
            $item->nome = $produto->nome;
            $item->quantidade = $quantidade;

            $this->produtos[] = $item;

            TTransaction::close();
            
            $this->updateProductTable();

            new TMessage('info', 'Produto adicionado à lista');
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    private function updateProductTable()
    {
        foreach ($this->produtos as $produto) {
            $this->estoqueTable->addRowSet($produto->nome, $produto->quantidade);
        }

        $this->form->add($this->estoqueTable);
    }

    public function onSave()
    {
        try {
            TTransaction::open('development');

            $data = $this->form->getData();
            
            $pedido = new Pedido();
            $pedido->cliente_id = $data->cliente_id;
            $pedido->total = 0;
            $pedido->store();

            foreach ($this->produtos as $produto) {
                $pedidoProduto = new PedidoProduto();
                $pedidoProduto->pedido_id = $pedido->id;
                $pedidoProduto->produto_id = $produto->id;
                $pedidoProduto->quantidade = $produto->quantidade;
                
                $produtoEntity = new Produto($produto->id);
                $pedidoProduto->store();

                $pedido->total += $produtoEntity->preco * $produto->quantidade;
            }

            $pedido->store();
            TTransaction::close();

            new TMessage('info', 'Pedido salvo com sucesso');
            $this->form->clear();
            $this->produtos = []; // Limpa a lista de produtos após salvar
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
