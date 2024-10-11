<?php

use Adianti\Control\TPage;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TButton;
use Adianti\Database\TTransaction;
use Adianti\Control\TAction;

class EstoqueForm extends TPage
{
    private $form;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_estoque');
        $this->form->setFormTitle('Atualizar Estoque');

        // Campos
        $produto_id = new TDBCombo('produto_id', 'development', 'Produto', 'id', 'nome');
        $quantidade = new TEntry('quantidade');

        $quantidade->setMask('99999');

        // Adicionar os campos ao formulário
        $this->form->addFields([new TLabel('Produto')], [$produto_id]);
        $this->form->addFields([new TLabel('Quantidade')], [$quantidade]);

        // Botões de ação
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save');

        parent::add($this->form);
    }

    public function onSave($param)
    {
        try {
            TTransaction::open('development');
            
            $data = $this->form->getData();
            
            // Verifica se já existe o produto no estoque
            $estoque = Estoque::where('produto_id', '=', $data->produto_id)->first();
            if (!$estoque) {
                $estoque = new Estoque();
            }

            $estoque->produto_id = $data->produto_id;
            $estoque->quantidade = $data->quantidade;
            $estoque->store();

            TTransaction::close();

            new TMessage('info', 'Estoque atualizado com sucesso');
            $this->form->clear();
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}

