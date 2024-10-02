<?php

use Adianti\Widget\Form\TForm;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TButton;
use Adianti\Core\AdiantiCoreApplication;
use App\Model\ItemPedido; // Importa o modelo de ItemPedido

class ItemPedidoForm extends AdiantiCoreApplication
{
    private $form;

    public function __construct()
    {
        parent::__construct();

        // Cria o formulário
        $this->form = new BootstrapFormBuilder('form_item_pedido');
        $this->form->setTitle('Cadastro de Item de Pedido');

        // Cria os campos do formulário
        $id = new TEntry('id');
        $pedido_id = new TEntry('pedido_id');
        $produto_id = new TEntry('produto_id');
        $quantidade = new TEntry('quantidade');
        $preco = new TEntry('preco');

        // Adiciona os campos ao formulário
        $this->form->addFields(['ID'], [$id]);
        $this->form->addFields(['Pedido ID'], [$pedido_id]);
        $this->form->addFields(['Produto ID'], [$produto_id]);
        $this->form->addFields(['Quantidade'], [$quantidade]);
        $this->form->addFields(['Preço'], [$preco]);

        // Cria o botão de ação para salvar
        $btn_save = new TButton('save');
        $btn_save->setLabel('Salvar');
        $btn_save->setImage('fas:save');
        $btn_save->setAction(new TAction([$this, 'onSave']), 'Salvar');

        // Adiciona o botão ao formulário
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fas:save');

        parent::run();
    }

}