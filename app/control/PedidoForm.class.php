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

    public function __construct()
    {
        parent::__construct();
        
        $this->form = new BootstrapFormBuilder('form_pedido');
        $this->form->addContent( ['<h4>Cadastro de Pedidos</h4><hr>'] );
        $this->form->setFieldSizes('100%');

        $this->initializeFormFields();
        $this->addFormActions();

        parent::add($this->form);

        $this->loadAvailableProducts();
    }

    private function initializeFormFields()
    {
        $id         = new TEntry('id');
        $cliente_id = new TDBCombo('cliente_id', 'development', 'Cliente', 'id', 'nome', 'nome');
        $this->produto_id = new TDBCombo('produto_id', 'development', 'Produto', 'id', 'nome', 'nome');
        $quantidade = new TEntry('quantidade');

        $id->setEditable(FALSE);
        $quantidade->setSize('100%');
        $quantidade->setValue(1);

        $this->form->addFields([new TLabel('ID')], [$id]);
        $this->form->addFields([new TLabel('Cliente<span style="color: #a00000">*</span>')], [$cliente_id]);
        $this->form->addFields([new TLabel('Produto<span style="color: #a00000">*</span>')], [$this->produto_id]);
        $this->form->addFields([new TLabel('Quantidade<span style="color: #a00000">*</span>')], [$quantidade]);
    }

    private function addFormActions()
    {
        $btn_save = new TButton('save');
        $btn_save->setLabel('Salvar Pedido');
        $btn_save->setImage('fas:save');
        $btn_save->setAction(new TAction([$this, 'onSave']), 'Salvar');

        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fas:save');
    }

    public function loadAvailableProducts()
    {
        try {
            TTransaction::open('development');
            $produtos_disponiveis = $this->getAvailableProducts();
            TTransaction::close();

            if ($this->produto_id && $produtos_disponiveis) {
                $this->produto_id->addItems($produtos_disponiveis);
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    private function getAvailableProducts()
    {
        $repository = new TRepository('Estoque');
        $criteria = new TCriteria();
        $criteria->add(new TFilter('quantidade', '>', 0));

        $produtos_disponiveis = [];
        $estoques = $repository->load($criteria);

        if ($estoques) {
            foreach ($estoques as $estoque) {
                $produto = new Produto($estoque->produto_id);
                $produtos_disponiveis[$produto->id] = $produto->nome;
            }
        }
        return $produtos_disponiveis;
    }

    public function onSave()
    {
        try {
            TTransaction::open('development');

            $data = $this->form->getData();
            $this->validateFormData($data);

            $hoje = date('Y-m-d');

            $pedidoExistente = $this->getPedidoPendenteHoje($data->cliente_id, $hoje);

            if ($pedidoExistente) {
                $this->addPedidoProduto($pedidoExistente, $data);
            } else {
                $pedido = $this->createPedido($data);
                $this->addPedidoProduto($pedido, $data);
            }

            TTransaction::close();

            new TMessage('info', 'Pedido salvo com sucesso');
            $this->form->clear();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    private function getPedidoPendenteHoje($cliente_id, $dataHoje)
    {
        $repository = new TRepository('Pedido');
        $criteria = new TCriteria();
        $criteria->add(new TFilter('cliente_id', '=', $cliente_id));
        $criteria->add(new TFilter("DATE(data_pedido)", '=', $dataHoje));  // Extrai apenas a data para comparar
        $criteria->add(new TFilter('status', '=', 'PENDENTE'));  // Verifique o status como "PENDENTE"

        $pedidos = $repository->load($criteria);
        return $pedidos ? $pedidos[0] : null;
    }



    private function validateFormData($data)
    {
        if (empty($data->cliente_id) || empty($data->produto_id) || empty($data->quantidade)) {
            throw new Exception('Todos os campos são obrigatórios.');
        }
    }

    private function createPedido($data)
    {
        $pedido = new Pedido();
        $pedido->cliente_id = $data->cliente_id;
        $pedido->total = 0;
        $pedido->store();

        return $pedido;
    }

    private function addPedidoProduto($pedido, $data)
    {

        $pedidoProduto = new PedidoProduto();
        $pedidoProduto->pedido_id = $pedido->id;
        $pedidoProduto->produto_id = $data->produto_id;
        $pedidoProduto->quantidade = $data->quantidade;

        $produto = new Produto($data->produto_id);
        $pedidoProduto->store();

        $pedido->total += $produto->preco * $data->quantidade;
        $pedido->store();
    }
}
