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
        
        $id          = new TEntry('id');
        $nome        = new TEntry('nome');
        $descricao   = new TEntry('descricao');
        $preco       = new TEntry('preco');
        $quantidade  = new TEntry('quantidade');
        $categoria_id = new TDBCombo('categoria_id', 'development', 'Categoria', 'id', 'nome', 'nome');

        $id->setEditable(FALSE);
        $nome->setSize('100%');
        $descricao->setSize('100%');
        $preco->setSize('100%');
        $quantidade->setSize('100%');
        $categoria_id->setSize('100%');

        $this->form->addFields( [new TLabel('ID')],          [$id] );
        $this->form->addFields( [new TLabel('Nome')],        [$nome] );
        $this->form->addFields( [new TLabel('Descrição')],   [$descricao] );
        $this->form->addFields( [new TLabel('Preço')],       [$preco] );
        $this->form->addFields( [new TLabel('Quantidade')],   [$quantidade] );
        $this->form->addFields( [new TLabel('Categoria')],    [$categoria_id] );

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