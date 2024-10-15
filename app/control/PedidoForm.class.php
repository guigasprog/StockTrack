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
    private $produto_id; // Definir como propriedade da classe

    public function __construct()
    {
        parent::__construct();
        
        $this->form = new BootstrapFormBuilder('form_pedido');
        $this->form->setFormTitle('Cadastro de Pedido');

        // Criação dos campos do formulário
        $id         = new TEntry('id');
        $cliente_id = new TDBCombo('cliente_id', 'development', 'Cliente', 'id', 'nome', 'nome');
        $this->produto_id = new TDBCombo('produto_id', 'development', 'Produto', 'id', 'nome', 'nome'); // Definir a variável produto_id
        $quantidade = new TEntry('quantidade');
        
        $id->setEditable(FALSE);
        $quantidade->setSize('100%');
        $quantidade->setValue(1);

        // Adicionando os campos ao formulário
        $this->form->addFields( [new TLabel('ID')], [$id] );
        $this->form->addFields( [new TLabel('Cliente')], [$cliente_id] );
        $this->form->addFields( [new TLabel('Produto')], [$this->produto_id] ); // Adicionando a instância correta
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
            TTransaction::open('development'); // Abrir a transação

            $repository = new TRepository('Estoque');
            $criteria = new TCriteria();
            $criteria->add(new TFilter('quantidade', '>', 0)); // Filtrar produtos com quantidade maior que 0

            $estoques = $repository->load($criteria);

            // Atualizar o TDBCombo de produtos com base nos estoques disponíveis
            if ($estoques) {
                $produtos_disponiveis = [];
                foreach ($estoques as $estoque) {
                    $produto = new Produto($estoque->produto_id); // Carregar produto dentro da transação
                    $produtos_disponiveis[$produto->id] = $produto->nome;
                }

                // Verificar se o campo produto_id está corretamente instanciado antes de usar addItems
                if ($this->produto_id) {
                    $this->produto_id->addItems($produtos_disponiveis);
                }
            }

            TTransaction::close(); // Fechar a transação
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback(); // Fazer rollback em caso de erro
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
            $pedidoProduto = new PedidoProduto();
            $pedidoProduto->pedido_id = $pedido->id;
            $pedidoProduto->produto_id = $data->produto_id;
            $pedidoProduto->quantidade = $data->quantidade;

            // Calcular o preço do item
            $produto = new Produto($data->produto_id);

            $pedidoProduto->store(); // Salvar o item de pedido
            
            // Atualizar o total do pedido
            $pedido->total += $produto->preco * $data->quantidade;
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


