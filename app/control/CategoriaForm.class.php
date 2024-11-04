<?php

use Adianti\Control\TPage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Database\TTransaction;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Control\TAction;

class CategoriaForm extends TPage
{
    private $form;

    public function __construct()
    {
        parent::__construct();
        
        $this->form = new BootstrapFormBuilder('form_categoria');
        $this->form->addContent( ['<h4>Cadastro de Categoria</h4><hr>'] );
        $this->form->setFieldSizes('100%');
        
        $this->initializeFormFields();
        $this->addFormActions();

        parent::add($this->form);
    }
    
    private function initializeFormFields()
    {
        $id = new TEntry('idCategoria');
        $nome = new TEntry('nome');
        $descricao = new TEntry('descricao');

        $id->setEditable(false);
        $nome->setSize('100%');
        $descricao->setSize('100%');

        $this->form->addFields([new TLabel('ID')], [$id]);
        $this->form->addFields([new TLabel('Nome<span style="color: #a00000">*</span>')], [$nome]);
        $this->form->addFields([new TLabel('Descrição')], [$descricao]);
    }

    private function addFormActions()
    {
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fas:save');
        $this->form->addActionLink('Limpar', new TAction([$this, 'onClear']), 'fas:eraser red');
    }

    public function onSave()
    {
        try {
            TTransaction::open('development');

            $data = $this->form->getData();
            $this->validateData($data);

            $categoria = new Categoria();
            $categoria->fromArray((array) $data);
            $categoria->store();

            TTransaction::close();

            new TMessage('info', 'Categoria salva com sucesso');
            $this->form->clear();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    private function validateData($data)
    {
        if (empty($data->nome)) {
            throw new Exception('O campo "Nome" é obrigatório.');
        }
    }

    public function onClear()
    {
        $this->form->clear();
    }

    public function onEdit($param)
    {
        try {
            if (!empty($param['id'])) {
                TTransaction::open('development');
                $categoria = new Categoria($param['id']);
                $this->form->setData($categoria);
                TTransaction::close();
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
