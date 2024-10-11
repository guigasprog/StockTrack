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

        // Criação do formulário
        $this->form = new BootstrapFormBuilder('form_clientes');
        $this->form->setFormTitle('Clientes');

        // Criar DataGrid para Clientes
        $this->dataGrid = new TDataGrid;
        $this->dataGrid->addColumn(new TDataGridColumn('id', 'ID', 'right', 50));
        $this->dataGrid->addColumn(new TDataGridColumn('nome', 'Nome', 'left'));
        $this->dataGrid->addColumn(new TDataGridColumn('cpf', 'CPF', 'left'));
        $this->dataGrid->addColumn(new TDataGridColumn('email', 'Email', 'left'));
        $this->dataGrid->addColumn(new TDataGridColumn('telefone', 'Telefone', 'left'));

        // Coluna de ações
        $action_edit = new TDataGridAction([$this, 'onEdit'], ['id' => '{id}']);
        $action_edit->setLabel('Editar');
        $action_edit->setImage('fas:edit blue');

        $action_delete = new TDataGridAction([$this, 'onDelete'], ['id' => '{id}']);
        $action_delete->setLabel('Excluir');
        $action_delete->setImage('fas:trash-alt red');

        // Adicionar ação de visualizar endereço
        $action_view_address = new TDataGridAction([$this, 'onViewAddress'], ['id' => '{id}']);
        $action_view_address->setLabel('Ver Endereço');
        $action_view_address->setImage('fas:eye green');

        // Adicionar ações à DataGrid
        $this->dataGrid->addAction($action_view_address);
        $this->dataGrid->addAction($action_edit);
        $this->dataGrid->addAction($action_delete);

        // Criar o modelo da DataGrid
        $this->dataGrid->createModel();

        // Adiciona o DataGrid ao formulário
        $this->form->addContent([$this->dataGrid]);

        // Botão para adicionar novo cliente
        $btn_add = new TButton('add');
        $btn_add->setLabel('Adicionar Cliente');
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
        // Iniciar transação com o banco de dados
        TTransaction::open('development');

        // Carregar os clientes
        $repository = new TRepository('Cliente');
        $clientes = $repository->load();

        // Adicionar os itens à DataGrid
        if ($clientes) {
            $this->dataGrid->addItems($clientes);
        }

        // Fechar a transação
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
        $action = new TAction([$this, 'Delete']);
        $action->setParameters($param);

        new TQuestion('Deseja realmente excluir este cliente?', $action);
    }

    public function Delete($param)
    {
        try {
            TTransaction::open('development');

            // Carregar cliente
            $cliente = new Cliente($param['id']);

            // Excluir cliente
            $cliente->delete();

            // Fechar transação
            TTransaction::close();

            // Recarregar o DataGrid
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
            // Carregar endereço
            $endereco = new Endereco($cliente->endereco_id);

            // Criar um novo formulário para o diálogo
            $dialogForm = new BootstrapFormBuilder('form_view_address');
            $dialogForm->setFieldSizes('100%');

            // Adicionar campos ao formulário do diálogo
            $logradouro = new TEntry('logradouro');
            $numero = new TEntry('numero');
            $complemento = new TEntry('complemento');
            $bairro = new TEntry('bairro');
            $cidade = new TEntry('cidade');
            $estado = new TEntry('estado');
            $cep = new TEntry('cep');

            // Definindo valores e bloqueando os campos
            $logradouro->setValue($endereco->logradouro);
            $numero->setValue($endereco->numero);
            $complemento->setValue($endereco->complemento);
            $bairro->setValue($endereco->bairro);
            $cidade->setValue($endereco->cidade);
            $estado->setValue($endereco->estado);
            $cep->setValue($endereco->cep);

            // Bloquear os campos para edição
            $logradouro->setEditable(false);
            $numero->setEditable(false);
            $complemento->setEditable(false);
            $bairro->setEditable(false);
            $cidade->setEditable(false);
            $estado->setEditable(false);
            $cep->setEditable(false);

            // Adicionar campos ao formulário com o layout desejado
            $row = $dialogForm->addFields([new TLabel('CEP'), $cep],
                                        [new TLabel('Logradouro'), $logradouro],
                                        [new TLabel('Número'), $numero]);
            $row->layout = ['col-sm-3', 'col-sm-6', 'col-sm-3'];

            $row = $dialogForm->addFields([new TLabel('Cidade'), $cidade],
                                        [new TLabel('Estado'), $estado],
                                        [new TLabel('Bairro'), $bairro]);
            $row->layout = ['col-sm-4', 'col-sm-4', 'col-sm-4'];

            $row = $dialogForm->addFields([new TLabel('Complemento'), $complemento]);
            $row->layout = ['col-sm-12'];

            $dialog = new TInputDialog('Endereço do Cliente', $dialogForm);
        } else {
            new TMessage('info', 'Endereço não cadastrado para este cliente.');
        }

        TTransaction::close();
    }

}
