<?php

use Adianti\Control\TPage;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TButton;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Database\TTransaction;
use Adianti\Control\TAction;

class CategoriaForm extends TPage
{
    private $form;

    public function __construct()
    {
        parent::__construct();
        
        $this->form = new BootstrapFormBuilder('form_categoria');
        $this->form->setFormTitle('Cadastro de Categoria');
        
        $id   = new TEntry('id');
        $nome = new TEntry('nome');

        $id->setEditable(FALSE);
        $nome->setSize('100%');

        $this->form->addFields( [new TLabel('ID')],    [$id] );
        $this->form->addFields( [new TLabel('Nome')],  [$nome] );

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
            
            $categoria = new Categoria();
            $categoria->fromArray((array) $data);
            $categoria->store();
            
            TTransaction::close();
            new TMessage('info', 'Categoria salva com sucesso');
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
                $categoria = new Categoria($id);
                $this->form->setData($categoria);
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