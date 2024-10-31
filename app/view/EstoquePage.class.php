<?php

require('fpdf.php');

use Adianti\Control\TPage;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Database\TTransaction;
use Adianti\Database\TRepository;
use Adianti\Export\TExportPDF;

class EstoquePage extends TPage
{
    private $form;
    private $dataGrid;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('estoque_page');
        $this->form->setFormTitle('Gestão de Estoque');

        $this->dataGrid = new BootstrapDatagridWrapper(new TDataGrid);

        $this->createColumns();

        $this->dataGrid->createModel();

        $this->form->addContent([$this->dataGrid]);

        $this->addActions();

        $this->loadDataGrid();

        parent::add($this->form);
    }

    private function createColumns()
    {
        $this->dataGrid->addColumn(new TDataGridColumn('id', 'ID', 'left', '5%'));
        $this->dataGrid->addColumn(new TDataGridColumn('produto_nome', 'Produto', 'left', '45%'));
        $this->dataGrid->addColumn(new TDataGridColumn('quantidade', 'Quantidade', 'center', '15%'));
        $this->dataGrid->addColumn(new TDataGridColumn('data_entrada', 'Data de Entrada', 'center', '35%'));
    }

    private function addActions()
    {
        $this->form->addAction('Atualizar Estoque', new TAction([$this, 'onAdd']), 'fas:sync-alt');

        $this->form->addAction('Gerar PDF', new TAction([$this, 'onGeneratePDF']), 'fas:file-pdf');
    }

    public function onAdd()
    {
        TApplication::gotoPage('EstoqueForm');
    }

    public function loadDataGrid()
    {
        $this->dataGrid->clear();
        TTransaction::open('development');

        $repository = new TRepository('Estoque');
        $estoques = $repository->orderBy('data_entrada', 'asc')->load();

        if ($estoques) {
            $estoquesDTO = [];

            foreach ($estoques as $estoque) {
                $produtoRepository = new TRepository('Produto');
                $produto = $produtoRepository->where('id', '=', $estoque->produto_id)->load();

                if ($produto) {
                    $dto = new EstoqueDTO();
                    $dto->id = $estoque->id;
                    $dto->produto_nome = $produto[0]->nome;
                    $dto->quantidade = $estoque->quantidade;
                    $dto->data_entrada = $estoque->data_entrada;

                    $estoquesDTO[] = $dto;
                }
            }

            $this->dataGrid->addItems($estoquesDTO);
        }

        TTransaction::close();
    }

    public function onGeneratePDF()
    {
        
    }

}

class EstoqueDTO {
    public $id;
    public $produto_nome;
    public $quantidade;
    public $data_entrada;
}
