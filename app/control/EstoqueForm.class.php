<?php

use Adianti\Control\TPage;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Database\TTransaction;
use Adianti\Control\TAction;

class EstoqueForm extends TPage
{
    private $form;

    public function __construct()
    {
        parent::__construct();
        $this->form = new BootstrapFormBuilder('form_estoque');
        $this->form->addContent( ['<h4>Atualizar Estoque</h4><hr>'] );
        $this->form->setFieldSizes('100%');

        $this->addFieldsToForm();
        $this->addActionsToForm();

        parent::add($this->form);
    }

    private function addFieldsToForm()
    {
        $produto_id = $this->createProdutoField();
        $quantidade = $this->createQuantidadeField();

        $this->form->addFields([new TLabel('Produto<span style="color: #a00000">*</span>')], [$produto_id]);
        $this->form->addFields([new TLabel('Quantidade<span style="color: #a00000">*</span>')], [$quantidade]);
    }

    private function createProdutoField()
    {
        return new TDBCombo('produto_id', 'development', 'Produto', 'id', 'nome');
    }

    private function createQuantidadeField()
    {
        $quantidade = new TEntry('quantidade');
        $quantidade->setMask('99999');
        return $quantidade;
    }

    private function addActionsToForm()
    {
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save');
    }

    public function onSave($param)
    {
        try {
            TTransaction::open('development');
            
            $data = $this->form->getData();
            
            $estoque = Estoque::where('produto_id', '=', $data->produto_id)->first();
            $estoque = new Estoque();

            $estoque->produto_id = $data->produto_id;
            $estoque->quantidade = $data->quantidade;
            $estoque->data_entrada = date("Y-m-d");
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
