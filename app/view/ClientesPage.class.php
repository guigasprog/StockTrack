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

class ClientesPage extends TPage
{
    private $form;
    private $dataGrid;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_clientes');
        $this->form->setFormTitle('Clientes');

        $this->dataGrid = $this->createDataGrid();
        $this->form->addContent([$this->dataGrid]);

        $this->form->addHeaderAction('Adicionar', new TAction([$this, 'onAdd']), 'fas:plus');

        parent::add($this->form);
        $this->loadDataGrid();
    }

    private function createDataGrid()
    {
        $dataGrid = new BootstrapDatagridWrapper(new TDataGrid);

        $columns = [
            ['id', 'ID', 'left', '5%'],
            ['nome', 'Nome', 'left', '40%'],
            ['cpf', 'CPF', 'left', '15%'],
            ['email', 'Email', 'left', '20%'],
            ['telefone', 'Telefone', 'left', '20%'],
        ];

        foreach ($columns as [$name, $label, $align, $width]) {
            $dataGrid->addColumn(new TDataGridColumn($name, $label, $align, $width));
        }

        $actions = [
            ['onViewAddress', 'Ver Endereço', 'fas:eye green'],
            ['onEdit', 'Editar', 'fas:edit blue'],
            ['onDelete', 'Excluir', 'fas:trash-alt red'],
        ];

        foreach ($actions as [$method, $label, $icon]) {
            $action = new TDataGridAction([$this, $method], ['id' => '{id}']);
            $action->setLabel($label);
            $action->setImage($icon);
            $dataGrid->addAction($action);
        }

        $dataGrid->createModel();

        return $dataGrid;
    }

    public function loadDataGrid()
    {
        TTransaction::open('development');
        $repository = new TRepository('Cliente');
        $clientes = $repository->load();
        $this->dataGrid->clear();

        if ($clientes) {
            $this->dataGrid->addItems($clientes);
        }

        TTransaction::close();
    }

    public function onAdd()
    {
        TApplication::gotoPage('ClienteForm');
    }

    public function onEdit($param)
    {
        AdiantiCoreApplication::loadPage('ClienteForm', 'onEdit', ['id' => $param['id']]);
    }

    public function onDelete($param)
    {
        TTransaction::open('development');
        
        $pedidoRepository = new TRepository('Pedido');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('cliente_id', '=', $param['id']));
        $criteria->add(new TFilter('status', '=', 'pendente'));
        $pedidosPendentes = $pedidoRepository->count($criteria);
        
        if ($pedidosPendentes > 0) {
            new TMessage('error', 'O cliente possui pedidos pendentes e não pode ser excluído.');
            TTransaction::close();
            return;
        }

        TTransaction::close();

        $action = new TAction([$this, 'deleteCliente']);
        $action->setParameters($param);

        new TQuestion('Deseja realmente excluir este cliente?', $action);
    }


    public function deleteCliente($param)
    {
        try {
            TTransaction::open('development');
            $cliente = new Cliente($param['id']);
            $cliente->delete();
            TTransaction::close();

            $this->loadDataGrid();
            new TMessage('info', 'Cliente excluído com sucesso!');
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onViewAddress($param)
    {
        TTransaction::open('development');
        $cliente = new Cliente($param['id']);

        if ($cliente->endereco_id) {
            $endereco = new Endereco($cliente->endereco_id);
            $dialogForm = $this->createAddressDialogForm($endereco);
            new TInputDialog('Endereço do Cliente', $dialogForm);
        } else {
            new TMessage('info', 'Endereço não cadastrado para este cliente.');
        }

        TTransaction::close();
    }

    private function createAddressDialogForm($endereco)
    {
        $dialogForm = new BootstrapFormBuilder('form_view_address');
        $dialogForm->setFieldSizes('100%');

        $cep = new TEntry('cep');
        $cep->setValue($endereco->cep);
        $cep->setEditable(false);
        
        $logradouro = new TEntry('logradouro');
        $logradouro->setValue($endereco->logradouro);
        $logradouro->setEditable(false);
        
        $numero = new TEntry('numero');
        $numero->setValue($endereco->numero);
        $numero->setEditable(false);

        $dialogForm->addFields(
            [new TLabel('CEP'), $cep],
            [new TLabel('Logradouro'), $logradouro],
            [new TLabel('Número'), $numero]
        )->layout = ['col-sm-4', 'col-sm-5', 'col-sm-3'];

        $bairro = new TEntry('bairro');
        $bairro->setValue($endereco->bairro);
        $bairro->setEditable(false);

        $cidade = new TEntry('cidade');
        $cidade->setValue($endereco->cidade);
        $cidade->setEditable(false);

        $estado = new TEntry('estado');
        $estado->setValue($endereco->estado);
        $estado->setEditable(false);

        $dialogForm->addFields(
            [new TLabel('Bairro'), $bairro],
            [new TLabel('Cidade'), $cidade],
            [new TLabel('Estado'), $estado]
        )->layout = ['col-sm-5', 'col-sm-4', 'col-sm-3'];

        $complemento = new TEntry('complemento');
        $complemento->setValue($endereco->complemento);
        $complemento->setEditable(false);

        $dialogForm->addFields(
            [new TLabel('Complemento'), $complemento]
        )->layout = ['col-sm-12'];

        return $dialogForm;
    }

}
