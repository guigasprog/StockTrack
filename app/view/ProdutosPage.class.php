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

        $this->setupForm();
        $this->setupDataGrid();
        $this->form->addContent([$this->dataGrid]);
        
        $this->form->addHeaderAction('Adicionar', new TAction([$this, 'onAdd']), 'fas:plus');

        parent::add($this->form);

        $this->loadDataGrid();
    }

    private function setupForm()
    {
        $this->form = new BootstrapFormBuilder('form_produtos');
        $this->form->setFormTitle('Produtos');
    }

    private function setupDataGrid()
    {
        $this->dataGrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->dataGrid->addColumn(new TDataGridColumn('id', 'ID', 'left', '5%'));
        $this->dataGrid->addColumn(new TDataGridColumn('nome', 'Nome', 'left', '45%'));
        $this->dataGrid->addColumn(new TDataGridColumn('preco', 'Preço', 'left', '25%'));
        $this->dataGrid->addColumn(new TDataGridColumn('quantidade', 'Quantidade em Estoque', 'left', '25%'));

        $this->addDataGridActions();
        $this->dataGrid->createModel();
    }

    private function addDataGridActions()
    {
        $action_view_more = new TDataGridAction([$this, 'onViewDetails'], ['id' => '{id}']);
        $action_view_more->setLabel('Ver Mais');
        $action_view_more->setImage('fas:info green');

        $action_view_estoque = new TDataGridAction([$this, 'onViewEstoque'], ['id' => '{id}']);
        $action_view_estoque->setLabel('Ver Estoque');
        $action_view_estoque->setImage('fas:eye green');

        $action_edit = new TDataGridAction([$this, 'onEdit'], ['id' => '{id}']);
        $action_edit->setLabel('Editar');
        $action_edit->setImage('fas:edit blue');
        
        $action_delete = new TDataGridAction([$this, 'onDelete'], ['id' => '{id}']);
        $action_delete->setLabel('Excluir');
        $action_delete->setImage('fas:trash-alt red');
        
        $this->dataGrid->addAction($action_view_estoque);
        $this->dataGrid->addAction($action_view_more);
        $this->dataGrid->addAction($action_edit);
        $this->dataGrid->addAction($action_delete);

    }

    private function loadDataGrid()
    {
        $this->dataGrid->clear();
        TTransaction::open('development');

        $repository = new TRepository('Produto');
        $produtos = $repository->load();

        foreach ($produtos as $produto) {
            $produto->quantidade = $this->getEstoqueQuantidade($produto->id);
        }

        if ($produtos) {
            $this->dataGrid->addItems($produtos);
        }

        TTransaction::close();
    }

    private function getEstoqueQuantidade($produtoId)
    {
        $estoqueRepository = new TRepository('Estoque');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('produto_id', '=', $produtoId));

        $estoques = $estoqueRepository->load($criteria);
        $qtde = array_reduce($estoques, fn($sum, $estoque) => $sum + $estoque->quantidade, 0);

        return $qtde;
    }

    public function onAdd()
    {
        TApplication::gotoPage('ProdutoForm');
    }

    public function onEdit($param)
    {
        TTransaction::open('development');
        
        $pedidoProdutoRepository = new TRepository('PedidoProduto');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('produto_id', '=', $param['id']));
        $pedidosProdutos = $pedidoProdutoRepository->load($criteria);
        
        if (count($pedidosProdutos) > 0) {
            $pedidoRepository = new TRepository('Pedido');
            $criteria = new TCriteria;
            foreach ($pedidosProdutos as $pedidoProduto) {
                $criteria->add(new TFilter('id', '=', $pedidoProduto->pedido_id));
                $criteria->add(new TFilter('status', '=', 'pendente'));
                $pedidos = $pedidoRepository->count($criteria);
                if($pedidos > 0) {
                    new TMessage('error', 'O Produto possui pedidos pendentes e não pode ser editado.');
                    TTransaction::close();
                    return;
                }
            }
        }

        TTransaction::close();

        AdiantiCoreApplication::loadPage('ProdutoForm', 'onEdit', ['id' => $param['id']]);
    }

    public function onDelete($param)
    {
        TTransaction::open('development');
        
        $pedidoProdutoRepository = new TRepository('PedidoProduto');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('produto_id', '=', $param['id']));
        $pedidosProdutos = $pedidoProdutoRepository->load($criteria);
        
        if (count($pedidosProdutos) > 0) {
            $pedidoRepository = new TRepository('Pedido');
            $criteria = new TCriteria;
            foreach ($pedidosProdutos as $pedidoProduto) {
                $criteria->add(new TFilter('id', '=', $pedidoProduto->pedido_id));
                $criteria->add(new TFilter('status', '=', 'pendente'));
                $pedidos = $pedidoRepository->count($criteria);
                if($pedidos > 0) {
                    new TMessage('error', 'O Produto possui pedidos pendentes e não pode ser excluído ate o pagamento e entrega.');
                    TTransaction::close();
                    return;
                }
            }
        }

        TTransaction::close();

        $action = new TAction([$this, 'Delete']);
        $action->setParameters($param);
        new TQuestion('Deseja realmente excluir este Produto?', $action);
    }

    public function Delete($param)
    {
        try {
            TTransaction::open('development');
            $produto = new Produto($param['id']);
            $produto->delete();
            TTransaction::close();

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
        $categoria = $produto->categoria_id ? new Categoria($produto->categoria_id) : null;

        if ($categoria) {
            $dialogForm = $this->createDetailsDialogForm($produto, $categoria);
            $this->addImagensToDialog($dialogForm, $produto->id);
            new TInputDialog('Detalhes do Produto', $dialogForm);
        } else {
            new TMessage('info', 'Categoria não cadastrada para este Produto.');
        }

        TTransaction::close();
    }

    private function createDetailsDialogForm($produto, $categoria)
    {
        $dialogForm = new BootstrapFormBuilder('form_view_produto_categoria');
        $dialogForm->setFieldSizes('100%');

        $descricao = new TText('descricao');
        $descricao->setValue($produto->descricao);

        $categoria_nome = new TEntry('categoria_nome');
        $categoria_nome->setValue($categoria->nome);

        $categoria_descricao = new TText('categoria_descricao');
        $categoria_descricao->setValue($categoria->descricao);

        foreach ([$categoria_nome, $descricao, $categoria_descricao] as $field) {
            $field->setEditable(false);
        }

        $categoria_nome->setSize(300);
        $descricao->setSize(300, 200);
        $categoria_descricao->setSize(300);

        $row = $dialogForm->addFields(
            [new TLabel('Categoria'), $categoria_nome],
            [new TLabel('Detalhes da Categoria'), $categoria_descricao],
            [new TLabel('Descrição'), $descricao]
        );
        $row->layout = ['col-sm-4', 'col-sm-8', 'col-sm-12'];

        return $dialogForm;
    }


    private function addImagensToDialog($dialogForm, $produtoId)
    {
        $imagemRepository = new TRepository('ImagensProduto');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('produto_id', '=', $produtoId));
        $imagens = $imagemRepository->load($criteria);

        if ($imagens) {
            $imagePanel = new TPanelGroup('Imagens do Produto');
            $imageTable = new TTable;
            $imageTable->style = 'width: 100%;';

            $row = $imageTable->addRow();
            $row->style = 'display: flex; flex-wrap: wrap; justify-content: center; align-items: center';

            foreach ($imagens as $imagem) {
                $div = new TElement('div');
                $div->id = 'image_' . $imagem->id;
                $div->style = 'width: 100px; height: 100px; background-image: url("data:image/*;base64,' . $imagem->imagem . '"); background-size: cover; background-position: center; background-repeat: no-repeat;';
                $row->addCell($div)->style = 'width: 150px; display: flex; justify-content: center; align-items: center';
            }

            $imagePanel->add($imageTable);
            $dialogForm->add($imagePanel);
        } else {
            $noImageMessage = new TLabel('Não há imagens cadastradas para este produto.');
            $noImageMessage->style = 'width: 100%; text-align: center;';
            $dialogForm->add($noImageMessage);
        }
    }

    public function onViewEstoque($param)
    {
        try {
            TTransaction::open('development');
            $dialogForm = new BootstrapFormBuilder('form_view_estoque');
            $dialogForm->setFieldSizes('100%');

            $estoqueRepository = new TRepository('Estoque');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('produto_id', '=', $param['id']));
            $estoques = $estoqueRepository->load($criteria);

            if ($estoques) {
                $estoqueTable = new TTable;
                $estoqueTable->style = 'width: 100%; text-align: center';
                $estoqueTable->addRowSet('Quantidade', 'Data de Entrada');

                foreach ($estoques as $estoque) {
                    $quantidade = $estoque->quantidade ?? 'N/A';
                    $data_entrada = $estoque->data_entrada ?? 'N/A';
                    $estoqueTable->addRowSet($quantidade, $data_entrada);
                }

                $dialogForm->add($estoqueTable);
            } else {
                $label = new TLabel('Não há registros de estoque para este produto.');
                $label->style = 'width: 100%; text-align: center;';
                $dialogForm->add($label);
            }

            TTransaction::close();
            new TInputDialog('Estoque do Produto', $dialogForm);
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
}
