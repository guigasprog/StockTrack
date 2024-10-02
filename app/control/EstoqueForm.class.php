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

class EstoqueForm extends TPage
{
    private $form;

    public function __construct()
    {
        parent::__construct();
        
        $this->form = new BootstrapFormBuilder('form_estoque');
        $this->form->setFormTitle('Cadastro de Estoque');
        
        $id          = new TEntry('id');
        $produto_id  = new TDBCombo('produto_id', 'development', 'Produto', 'id', 'nome', 'nome');
        $quantidade  = new TEntry('quantidade');
        
        $id->setEditable(FALSE);
        $produto_id->setSize('100%');
        $quantidade->setSize('100%');

        $this->form->addFields( [new TLabel('ID')],          [$id] );
        $this->form->addFields( [new TLabel('Produto')],      [$produto_id] );
        $this->form->addFields( [new TLabel('Quantidade')],   [$quantidade] );

        // Save button
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fas:save');
        $this->form->addActionLink('Limpar', new TAction([$this, 'onClear']), 'fas:eraser red');

        parent::add($this->form);
    }
    
    public function onSave()
    {
        try
        {
            TTransaction::open('development');
            $data = $this->form->getData();
            
            $estoque = new Estoque();
            $estoque->fromArray((array) $data);
            $estoque->store();
            
            TTransaction::close();
            new TMessage('info', 'Estoque salvo com sucesso');
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
                $estoque = new Estoque($id);
                $this->form->setData($estoque);
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