<?php

use Adianti\Control\TPage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TButton;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Database\TTransaction;
use Adianti\Control\TAction;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Form\TDate;

class ProdutoForm extends TPage
{
    private $form;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_produto');
        $this->form->setFormTitle('Cadastro de Produto');
        $this->form->setFieldSizes('100%');

        // Criação dos campos do formulário
        $this->createFormFields();

        // Adicionando ações ao formulário
        $this->addActions();

        parent::add($this->form);
    }

    private function createFormFields()
    {
        // Campos do formulário
        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $descricao = new TEntry('descricao');
        $preco = new TEntry('preco');
        $validade = new TDate('validade');

        // ComboBox para selecionar categorias
        $categorias = new TDBCombo('categoria_id', 'development', 'Categoria', 'idCategoria', 'nome', 'nome');

        // Configurações dos campos
        $id->setEditable(false);
        $preco->setNumericMask(2, ',', '.', true);
        $validade->setMask('dd/mm/yyyy');

        // Adicionando os campos ao formulário
        $row = $this->form->addFields([new TLabel('ID'), $id],
                                       [new TLabel('Nome'), $nome],
                                       [new TLabel('Preço por unidade'), $preco]);
        $row->layout = ['col-sm-4', 'col-sm-4', 'col-sm-4'];

        $row = $this->form->addFields([new TLabel('Descrição'), $descricao],
                                       [new TLabel('Validade (se tiver)'), $validade]);
        $row->layout = ['col-sm-6', 'col-sm-6'];

        // Adicionando a seleção de categorias
        $row = $this->form->addFields([new TLabel('Categorias'), $categorias]);
        $row->layout = ['col-sm-12'];
    }

    private function addActions()
    {
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fas:save');
        $this->form->addActionLink('Limpar', new TAction([$this, 'onClear']), 'fas:eraser red');
    }

    public function onSave()
    {
        try
        {
            TTransaction::open('development');
            $data = $this->form->getData();
            
            $produto = new Produto();
            $produto->fromArray((array) $data);
            
            // Verifica se há uma categoria selecionada
            if (!empty($data->categoria_id)) {
                $produto->set_categoria(new Categoria($data->categoria_id));
            }
            
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
            // Verifica se há um ID no parâmetro
            if (isset($param['id']))
            {
                $id = $param['id'];
                TTransaction::open('development'); // Abre a transação com o banco

                // Carrega o produto pelo ID
                $produto = new Produto($id);

                // Preenche o formulário com os dados do produto
                $this->form->setData($produto);

                TTransaction::close(); // Fecha a transação
            }
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback(); // Reverte a transação em caso de erro
        }
    }
}
