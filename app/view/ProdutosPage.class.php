<?php

use Adianti\Control\TPage;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TInputDialog;
use Adianti\Database\TTransaction;
use Adianti\Database\TRepository;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TDataGridAction;

class ProdutosPage extends TPage
{
    private $form;
    private $dataGrid;

    public function __construct()
    {
        parent::__construct();

        // Criação do formulário
        $this->form = new BootstrapFormBuilder('form_produtos');
        $this->form->setFormTitle('Produtos');

        $this->dataGrid = new TDataGrid;
        $this->dataGrid->addColumn(new TDataGridColumn('id', 'ID', 'right', 50));
        $this->dataGrid->addColumn(new TDataGridColumn('nome', 'Nome', 'left'));
        $this->dataGrid->addColumn(new TDataGridColumn('validade', 'Validade', 'left', 100));
        $this->dataGrid->addColumn(new TDataGridColumn('preco', 'Preço', 'left'));

        $action_edit = new TDataGridAction([$this, 'onEdit'], ['id' => '{id}']);
        $action_edit->setLabel('Editar');
        $action_edit->setImage('fas:edit blue');

        $action_delete = new TDataGridAction([$this, 'onDelete'], ['id' => '{id}']);
        $action_delete->setLabel('Excluir');
        $action_delete->setImage('fas:trash-alt red');

        $action_view_address = new TDataGridAction([$this, 'onViewDetails'], ['id' => '{id}']);
        $action_view_address->setLabel('Ver Mais');
        $action_view_address->setImage('fas:eye green');

        $this->dataGrid->addAction($action_view_address);
        $this->dataGrid->addAction($action_edit);
        $this->dataGrid->addAction($action_delete);

        $this->dataGrid->createModel();

        $this->form->addContent([$this->dataGrid]);

        $btn_add = new TButton('add');
        $btn_add->setLabel('Adicionar Produtos');
        $btn_add->setAction(new TAction([$this, 'onAdd']), 'Adicionar');
        $this->form->addAction('Adicionar', new TAction([$this, 'onAdd']), 'fas:plus');

        // Adiciona o formulário à página
        parent::add($this->form);

        // Carregar os dados no DataGrid
        $this->loadDataGrid();
    }

    public function loadDataGrid()
    {
        $this->dataGrid->clear();
        TTransaction::open('development');

        $repository = new TRepository('Produto');
        $produtos = $repository->load();

        // Adicionar os itens à DataGrid
        if ($produtos) {
            $this->dataGrid->addItems($produtos);
        }

        // Fechar a transação
        TTransaction::close();
    }

    public function onAdd()
    {
        TApplication::gotoPage('ProdutoForm');
    }

    public function onEdit($param)
    {
        AdiantiCoreApplication::loadPage('ProdutoForm', 'onEdit', ['id' => $param['id']]);
    }

    public function onDelete($param)
    {
        $action = new TAction([$this, 'Delete']);
        $action->setParameters($param);

        new TQuestion('Deseja realmente excluir este cliente?', $action);
    }

    public function Delete($param)
    {
        try {
            TTransaction::open('development');

            $produto = new Produto($param['id']);

            // Excluir cliente
            $produto->delete();

            // Fechar transação
            TTransaction::close();

            // Recarregar o DataGrid
            $this->loadDataGrid();
            new TMessage('info', 'Produto excluído com sucesso!');
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onViewDetails($param)
    {
        TTransaction::open('development');
        $produto = new Produto($param['id']);

        if ($produto->categoria_id) {
            $categoria = new Categoria($produto->categoria_id);

            $dialogForm = new BootstrapFormBuilder('form_view_address');
            $dialogForm->setFieldSizes('100%');

            $descricao = new TText('descricao');
            $categoria_nome = new TEntry('categoria_nome');
            $categoria_descricao = new TText('categoria_descricao');

            $descricao->setValue($produto->descricao);
            $categoria_nome->setValue($categoria->nome);
            $categoria_descricao->setValue($categoria->descricao);

            $categoria_nome->setEditable(false);
            $descricao->setEditable(false);
            $categoria_descricao->setEditable(false);

            $categoria_nome->setSize(300); // Defina a largura desejada
            $descricao->setSize(300, 200); // Defina a largura e altura desejadas
            $categoria_descricao->setSize(300); // Defina a largura e altura desejadas

            $row = $dialogForm->addFields([new TLabel('Categoria'), $categoria_nome],
                                        [new TLabel('Detalhes da Categoria'), $categoria_descricao],
                                        [new TLabel('Descrição'), $descricao]);
            $row->layout = ['col-sm-4', 'col-sm-8', 'col-sm-12'];

            $imagemRepository = new TRepository('ImagensProduto');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('produto_id', '=', $produto->id));
            $imagens = $imagemRepository->load($criteria);

            if ($imagens) {
                $imagePanel = new TPanelGroup('Imagens do Produto');
                $imageTable = new TTable;
                $imageTable->style = 'width: 100%;';
                $row = $imageTable->addRow();
                $row->style = '
                display: flex;
                flex-wrap: wrap;
                justify-content: center; 
                align-items: center
            ';
                foreach ($imagens as $imagem) {
                    $div = new TElement('div');

                    $div->id = 'image_'.$imagem->id;

                    $div->style = '
                        width: 100px; 
                        height: 100px; 
                        background-image: url("data:image/png;base64,'.$imagem->imagem.'"); 
                        background-size: cover; 
                        background-position: center; 
                        background-repeat: no-repeat;
                    ';
            
                    $row->addCell($div)->style = '
                        width: 150px;
                        display: flex; 
                        justify-content: center; 
                        align-items: center
                    ';
                }
            
                $imagePanel->add($imageTable); // Adiciona a tabela de imagens ao painel
                $dialogForm->add($imagePanel); // Adiciona o painel de imagens ao formulário
            } else {
                $noImageMessage = new TLabel('Não há imagens cadastradas para este produto.');
                $noImageMessage->style = 'width: 100%; text-align: center;';
                $dialogForm->add($noImageMessage);
            }

            $dialog = new TInputDialog('Detalhes do Produto', $dialogForm);
        } else {
            new TMessage('info', 'Categoria não cadastrado para este Produto.');
        }

        TTransaction::close();
    }

}
