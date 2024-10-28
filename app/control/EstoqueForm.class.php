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
        $this->form->setFormTitle('Atualizar Estoque');
        $this->form->setFieldSizes('100%');

        $this->addFieldsToForm();
        $this->addActionsToForm();

        parent::add($this->form);
    }

    /**
     * Configura e adiciona os campos ao formulário
     */
    private function addFieldsToForm()
    {
        $produto_id = $this->createProdutoField();
        $quantidade = $this->createQuantidadeField();

        $this->form->addFields([new TLabel('Produto')], [$produto_id]);
        $this->form->addFields([new TLabel('Quantidade')], [$quantidade]);
    }

    /**
     * Cria o campo de seleção de produtos
     * @return TDBCombo
     */
    private function createProdutoField()
    {
        return new TDBCombo('produto_id', 'development', 'Produto', 'id', 'nome');
    }

    /**
     * Cria o campo de entrada para quantidade com máscara
     * @return TEntry
     */
    private function createQuantidadeField()
    {
        $quantidade = new TEntry('quantidade');
        $quantidade->setMask('99999');
        return $quantidade;
    }

    /**
     * Configura e adiciona as ações ao formulário
     */
    private function addActionsToForm()
    {
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save');
    }

    /**
     * Método para salvar os dados no banco
     */
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
