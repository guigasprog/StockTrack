<?php

use Adianti\Control\TPage;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TQuestion;
use Adianti\Database\TTransaction;
use Adianti\Database\TRepository;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Control\TAction;

class CategoriasPage extends TPage
{
    private $form;
    private $dataGrid;

    public function __construct()
    {
        parent::__construct();

        $this->setupForm();
        $this->setupDataGrid();
        
        $this->form->addContent([$this->dataGrid]);
        parent::add($this->form);

        $this->loadDataGrid();
    }

    private function setupForm()
    {
        $this->form = new BootstrapFormBuilder('form_categorias');
        $this->form->setFormTitle('Categorias');
        $this->form->addHeaderAction('Adicionar', new TAction([$this, 'onAdd']), 'fas:plus');
    }

    private function setupDataGrid()
    {
        $this->dataGrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->dataGrid->width = '100%';
        $this->dataGrid->addColumn(new TDataGridColumn('idCategoria', 'ID', 'left', '5%'));
        $this->dataGrid->addColumn(new TDataGridColumn('nome', 'Nome', 'left', '50%'));
        $this->dataGrid->addColumn(new TDataGridColumn('descricao', 'Descrição', 'left', '45%'));

        $this->dataGrid->addAction($this->createEditAction());
        $this->dataGrid->addAction($this->createDeleteAction());

        $this->dataGrid->createModel();
    }

    private function createEditAction()
    {
        $action_edit = new TDataGridAction([$this, 'onEdit'], ['id' => '{idCategoria}']);
        $action_edit->setLabel('Editar');
        $action_edit->setImage('fas:edit blue');
        return $action_edit;
    }

    private function createDeleteAction()
    {
        $action_delete = new TDataGridAction([$this, 'onDelete'], ['id' => '{idCategoria}']);
        $action_delete->setLabel('Excluir');
        $action_delete->setImage('fas:trash-alt red');
        return $action_delete;
    }

    public function loadDataGrid()
    {
        try {
            TTransaction::open('development');

            $repository = new TRepository('Categoria');
            $categorias = $repository->load();

            $this->dataGrid->clear();
            if ($categorias) {
                $this->dataGrid->addItems($categorias);
            }

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
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
        try {
            TTransaction::open('development');

            if ($this->hasAssociatedProducts($param['id'])) {
                new TMessage('error', 'Algum produto já possui esta categoria. Apague o produto para apagar essa categoria');
                TTransaction::rollback();
                return;
            }

            TTransaction::close();

            $action = new TAction([$this, 'confirmDelete']);
            $action->setParameters($param);
            new TQuestion('Deseja realmente excluir esta categoria?', $action);
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function confirmDelete($param)
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

    private function hasAssociatedProducts($categoria_id)
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('categoria_id', '=', $categoria_id));
        
        $produtoRepository = new TRepository('Produto');
        $produtos = $produtoRepository->load($criteria);
        
        return !empty($produtos);
    }
}
