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

class ProdutoForm extends TPage
{
    private $form;

    public function __construct()
{
    parent::__construct();

    $this->form = new BootstrapFormBuilder('form_produto');
    $this->form->setFormTitle('Cadastro de Produto');
    $this->form->setFieldSizes('100%');

    // Definindo os campos
    $id         = new TEntry('id');
    $nome       = new TEntry('nome');
    $descricao  = new TEntry('descricao');
    $preco      = new TEntry('preco');
    $quantidade = new TEntry('quantidade'); // Campo para quantidade
    $validade   = new TDate('validade'); // Campo para validade

    $id->setEditable(FALSE);

    // Adicionando os campos ao formulário
    $row = $this->form->addFields([new TLabel('ID'), $id],
                                   [new TLabel('Nome'), $nome],
                                   [new TLabel('Preço'), $preco]);
    $row->layout = ['col-sm-4', 'col-sm-4', 'col-sm-4'];

    $row = $this->form->addFields([new TLabel('Descrição'), $descricao],
                                   [new TLabel('Quantidade'), $quantidade],
                                   [new TLabel('Validade'), $validade]);
    $row->layout = ['col-sm-6', 'col-sm-3', 'col-sm-3'];

    // Adiciona botão para limpar o formulário
    $this->form->addActionLink('Salvar', new TAction([$this, 'onSave']), 'fa:save');
    $this->form->addActionLink('Limpar', new TAction([$this, 'onClear']), 'fas:eraser red');

    parent::add($this->form);
}

    
    public function onSave()
    {
        try
        {
            TTransaction::open('development');
            $data = $this->form->getData();
            
            $produto = new Produto();
            $produto->fromArray((array) $data);
            $produto->store();
            
            TTransaction::close();
            new TMessage('info', 'Produto salvo com sucesso');
            $this->form->clear();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    public function onClear()
    {
        $this->form->clear();
    }
    
    public function onEdit($param)
    {
        try
        {
            if (isset($param['id']))
            {
                $id = $param['id'];
                TTransaction::open('development');
                $produto = new Produto($id);
                $this->form->setData($produto);
                TTransaction::close();
            }
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}