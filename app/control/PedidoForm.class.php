<?php

use Adianti\Control\TPage;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TButton;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Database\TTransaction;
use Adianti\Control\TAction;

class PedidoForm extends TPage
{
    private $form;

    public function __construct()
    {
        parent::__construct();
        
        $this->form = new BootstrapFormBuilder('form_pedido');
        $this->form->setFormTitle('Cadastro de Pedido');

        // Criação dos campos do formulário
        $id          = new TEntry('id');
        $cliente_id  = new TDBCombo('cliente_id', 'development', 'Cliente', 'id', 'nome', 'nome');
        $produto_id  = new TDBCombo('produto_id', 'development', 'Produto', 'id', 'nome', 'nome');
        $quantidade  = new TEntry('quantidade');
        
        $id->setEditable(FALSE);
        $quantidade->setSize('100%');
        $quantidade->setValue(1); // Definir um valor padrão para a quantidade

        // Adicionando os campos ao formulário
        $this->form->addFields( [new TLabel('ID')], [$id] );
        $this->form->addFields( [new TLabel('Cliente')], [$cliente_id] );
        $this->form->addFields( [new TLabel('Produto')], [$produto_id] );
        $this->form->addFields( [new TLabel('Quantidade')], [$quantidade] );

        // Botão de ação para salvar o pedido
        $btn_save = new TButton('save');
        $btn_save->setLabel('Salvar Pedido');
        $btn_save->setImage('fas:save');
        $btn_save->setAction(new TAction([$this, 'onSave']), 'Salvar');

        // Adicionando os botões ao formulário
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fas:save');
        
        parent::add($this->form);
        
        // Carregar os produtos disponíveis
        $this->loadAvailableProducts();
    }

    // Método para carregar produtos disponíveis
    public function loadAvailableProducts()
    {
        try {
            TTransaction::open('development');

            $repository = new TRepository('Produto');
            $criteria = new TCriteria();
            $criteria->add(new TFilter('quantidade', '>', 0)); // Filtrar produtos com quantidade maior que 0

            $produtos = $repository->load($criteria);

            TTransaction::close();

            // Você pode querer manipular os produtos aqui se necessário
            // Como adicionar as opções no dropdown, isso será tratado automaticamente pelo TDBCombo

        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    // Método para salvar o pedido e os itens
    public function onSave()
    {
        try {
            TTransaction::open('development');

            // Coletar os dados do formulário
            $data = $this->form->getData();
            
            // Criar o pedido
            $pedido = new Pedido();
            $pedido->cliente_id = $data->cliente_id;
            $pedido->total = 0; // Total inicial do pedido
            $pedido->store(); // Salvar o pedido

            // Criar um item de pedido
            $itemPedido = new ItemPedido();
            $itemPedido->pedido_id = $pedido->id;
            $itemPedido->produto_id = $data->produto_id;
            $itemPedido->quantidade = $data->quantidade;

            // Calcular o preço do item
            $produto = new Produto($data->produto_id);
            $itemPedido->preco = $produto->preco * $data->quantidade;

            $itemPedido->store(); // Salvar o item de pedido
            
            // Atualizar o total do pedido
            $pedido->total += $itemPedido->preco;
            $pedido->store();

            TTransaction::close();

            new TMessage('info', 'Pedido salvo com sucesso');
            $this->form->clear();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}


