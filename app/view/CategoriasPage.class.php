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

class CategoriasPage extends TPage
{
    private $form;
    private $dataGrid;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_categorias');
        $this->form->setFormTitle('Categorias');

        $this->dataGrid = new TDataGrid;
        $this->dataGrid->addColumn(new TDataGridColumn('idCategoria', 'ID', 'left', '5%'));
        $this->dataGrid->addColumn(new TDataGridColumn('nome', 'Nome', 'left', '70%'));
        $this->dataGrid->addColumn(new TDataGridColumn('descricao', 'Descrição', 'left', '25%'));

        // Coluna de ações
        $action_edit = new TDataGridAction([$this, 'onEdit'], ['id' => '{idCategoria}']);
        $action_edit->setLabel('Editar');
        $action_edit->setImage('fas:edit blue');

        $action_delete = new TDataGridAction([$this, 'onDelete'], ['id' => '{idCategoria}']);
        $action_delete->setLabel('Excluir');
        $action_delete->setImage('fas:trash-alt red');

        $this->dataGrid->addAction($action_edit);
        $this->dataGrid->addAction($action_delete);

        $this->dataGrid->createModel();

        $this->form->addContent([$this->dataGrid]);

        $btn_add = new TButton('add');
        $btn_add->setLabel('Adicionar Categoria');
        $btn_add->setAction(new TAction([$this, 'onAdd']), 'Adicionar');
        $this->form->addAction('Adicionar', new TAction([$this, 'onAdd']), 'fas:plus');

        parent::add($this->form);

        $this->loadDataGrid();
    }

    public function loadDataGrid()
    {
        $this->dataGrid->clear();

        TTransaction::open('development');

        $repository = new TRepository('Categoria');
        $categorias = $repository->load();

        if ($categorias) {
            $this->dataGrid->addItems($categorias);
        }

        TTransaction::close();
    }

    public function onAdd()
    {
        TApplication::gotoPage('CategoriaForm');
    }

    public function onEdit($param)
    {
        AdiantiCoreApplication::loadPage('CategoriaForm', 'onEdit', ['id' => $param['id']]);
    }

    public function onDelete($param)
    {
        TTransaction::open('development');
        $produtoRepository = new TRepository('Produto');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('categoria_id', '=', $param['id']));
        $produto = $produtoRepository->load($criteria);
        if($produto) {
            new TMessage('error', 'Algum produto ja possui está categoria.
            Apague o produto para apagar essa categoria');
            TTransaction::rollback();
            return;
        }
        TTransaction::close();
        $action = new TAction([$this, 'Delete']);
        $action->setParameters($param);
        new TQuestion('Deseja realmente excluir esta categoria?', $action);
    }

    public function Delete($param)
    {
        try {
            TTransaction::open('development');

            $categoria = new Categoria($param['id']);

            $categoria->delete();

            TTransaction::close();

            $this->loadDataGrid();
            new TMessage('info', 'Categoria excluída com sucesso!');
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

}
