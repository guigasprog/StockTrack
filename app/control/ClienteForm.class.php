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

class ClienteForm extends TPage
{
    private $form;

    public function __construct()
    {
        parent::__construct();
        
        $this->form = new BootstrapFormBuilder('form_cliente');
        $this->form->setFormTitle('Cadastro de Cliente');
        
        $id        = new TEntry('id');
        $nome      = new TEntry('nome');
        $email     = new TEntry('email');
        $telefone  = new TEntry('telefone');
        $endereco  = new TEntry('endereco');
        
        $id->setEditable(FALSE);
        $nome->setSize('100%');
        $email->setSize('100%');
        $telefone->setSize('100%');
        $endereco->setSize('100%');

        $this->form->addFields( [new TLabel('ID')],        [$id] );
        $this->form->addFields( [new TLabel('Nome')],      [$nome] );
        $this->form->addFields( [new TLabel('Email')],     [$email] );
        $this->form->addFields( [new TLabel('Telefone')],  [$telefone] );
        $this->form->addFields( [new TLabel('Endereço')],  [$endereco] );
        
        // Botão de ação para salvar o cliente
        $btn_save = new TButton('save');
        $btn_save->setLabel('Salvar');
        $btn_save->setImage('fas:save');
        $btn_save->setAction(new TAction([$this, 'onSave']), 'Salvar');

        // Adiciona os botões ao formulário corretamente
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
            
            $cliente = new Cliente();
            $cliente->fromArray((array) $data);
            $cliente->store();
            
            TTransaction::close();
            
            new TMessage('info', 'Cliente salvo com sucesso');
            
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
                
                TTransaction::open('your_database');
                $cliente = new Cliente($id);
                $this->form->setData($cliente);
                
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
