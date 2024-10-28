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

class EstoquePage extends TPage
{
    private $form;
    private $dataGrid;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('estoque_page');
        $this->form->setFormTitle('Estoque');

        $this->dataGrid = new TDataGrid;
        $this->dataGrid->addColumn(new TDataGridColumn('id', 'ID', 'left', '5%'));
        $this->dataGrid->addColumn(new TDataGridColumn('produto_nome', 'Produto', 'left', '45%'));
        $this->dataGrid->addColumn(new TDataGridColumn('quantidade', 'Quantidade', 'left', '10%'));
        $this->dataGrid->addColumn(new TDataGridColumn('data_entrada', 'Data de Entrada', 'left', '40%'));

        $this->dataGrid->createModel();

        $this->form->addContent([$this->dataGrid]);

        $this->form->addAction('Atualizar', new TAction([$this, 'onAdd']), 'fas:plus');

        parent::add($this->form);

        $this->loadDataGrid();
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
                // Carregar o nome do produto a partir do repositório de Produto
                $produtoRepository = new TRepository('Produto');
                $produto = $produtoRepository->load(new TCriteria(new TFilter('id', '=', $estoque->produto_id)));
                
                if ($produto) {
                    $dto = new EstoqueDTO();
                    $dto->id = $estoque->id;
                    $dto->produto_nome = $produto[0]->nome;
                    $dto->quantidade = $estoque->quantidade;
                    $dto->data_entrada = $estoque->data_entrada;

                    $estoquesDTO[] = $dto;
                }
            }
            
            // Adiciona os estoquesDTO ao datagrid
            $this->dataGrid->addItems($estoquesDTO);
        }

        TTransaction::close();
    }

    public function onAdd()
    {
        TApplication::gotoPage('EstoqueForm');
    }

}


class EstoqueDTO {
    public $id;
    public $produto_nome;
    public $quantidade;
    public $data_entrada;
}
