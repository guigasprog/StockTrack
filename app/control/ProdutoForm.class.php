<?php

use Adianti\Control\TPage;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TButton;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Database\TTransaction;
use Adianti\Control\TAction;
use Adianti\Widget\Wrapper\TDBCombo;

class ProdutoForm extends TPage
{
    private $form;
    private $categoriasAdicionadas; 

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_produto');
        $this->form->setFormTitle('Cadastro de Produto');
        $this->form->setFieldSizes('100%');

        // Campos do formulário
        $id         = new TEntry('id');
        $nome       = new TEntry('nome');
        $descricao  = new TEntry('descricao');
        $preco      = new TEntry('preco');
        $validade   = new TDate('validade');

        // ComboBox para selecionar categorias
        $categorias = new TDBCombo('categoria_id', 'development', 'Categoria', 'idCategoria', 'nome', 'nome');

        $id->setEditable(FALSE);
        $preco->setNumericMask(2, ',', '.', true);

        // Adicionando os campos ao formulário
        $row = $this->form->addFields([new TLabel('ID'), $id],
                                      [new TLabel('Nome'), $nome],
                                      [new TLabel('Preço por unidade'), $preco]);
        $row->layout = ['col-sm-4', 'col-sm-4', 'col-sm-4'];

        $row = $this->form->addFields([new TLabel('Descrição'), $descricao],
                                       [new TLabel('Validade(se tiver)'), $validade]);
        $row->layout = ['col-sm-6', 'col-sm-6'];

        // Adicionando a seleção de categorias
        $row = $this->form->addFields(  [new TLabel('Categorias'), $categorias]);
        $row->layout = ['col-sm-12'];

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

                // Verifica se há uma categoria associada ao produto
                if (!empty($produto->categoria_id)) {
                    $categoria = new Categoria($produto->categoria_id);
                    $produto->categoria_id = $categoria->idCategoria;
                }

                // Preenche o formulário com os dados do produto e da categoria
                $this->form->setData($produto);

                TTransaction::close(); // Fecha a transação
            }
        }
        catch (Exception $e)
        {
            // Exibe mensagem de erro e faz rollback da transação
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

}