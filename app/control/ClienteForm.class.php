<?php

use Adianti\Control\TPage;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TButton;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Database\TTransaction;
use Adianti\Control\TAction;
use App\Service\ConsultaCepService;

class ClienteForm extends TPage
{
    private $form;

    public function __construct()
    {
        parent::__construct();
        
        $this->form = new BootstrapFormBuilder('form_cliente');
        $this->form->addContent( ['<h4>Cadastro de Cliente</h4><hr>'] );
        $this->form->setFieldSizes('100%');
        
        $this->criarCampos();
        $this->criarAcoes();

        parent::add($this->form);
    }

    private function criarCampos()
    {
        $id        = new TEntry('id');
        $nome      = new TEntry('nome');
        $email     = new TEntry('email');
        $telefone  = new TEntry('telefone');
        $cpf       = new TEntry('cpf');
        $logradouro = new TEntry('logradouro');
        $cep     = new TEntry('cep');
        $numero     = new TEntry('numero');
        $bairro     = new TEntry('bairro');
        $cidade     = new TEntry('cidade');
        $estado     = new TEntry('estado');
        $complemento = new TEntry('complemento');
        
        $id->setEditable(FALSE);
        $cpf->setMask('999.999.999-99');
        
        $this->form->addFields([new TLabel('ID'), $id],
                               [new TLabel('Nome<span style="color: #a00000">*</span>'), $nome],
                               [new TLabel('CPF<span style="color: #a00000">*</span>'), $cpf])->layout = ['col-sm-4', 'col-sm-4', 'col-sm-4'];
        
        $this->form->addContent(['<h4 style="margin-top: 1%">Contatos do Cliente</h4>']);
        $this->form->addFields([new TLabel('Telefone<span style="color: #a00000">*</span>'), $telefone],
                               [new TLabel('Email<span style="color: #a00000">*</span>'), $email])->layout = ['col-sm-6', 'col-sm-6'];
        
        $this->form->addContent(['<h4 style="margin-top: 1%">Endereço do Cliente</h4>']);
        $this->form->addFields([new TLabel('CEP<span style="color: #a00000">*</span>'), $cep],
                               [new TLabel('Logradouro<span style="color: #a00000">*</span>'), $logradouro],
                               [new TLabel('Número'), $numero])->layout = ['col-sm-3', 'col-sm-6', 'col-sm-3'];
        
        $this->form->addFields([new TLabel('Cidade<span style="color: #a00000">*</span>'), $cidade],
                               [new TLabel('Estado<span style="color: #a00000">*</span>'), $estado],
                               [new TLabel('Bairro<span style="color: #a00000">*</span>'), $bairro])->layout = ['col-sm-4', 'col-sm-4', 'col-sm-4'];
        
        $this->form->addFields([new TLabel('Complemento'), $complemento])->layout = ['col-sm-12'];
    }

    private function criarAcoes()
    {
        $this->form->addAction('Buscar Endereco Via CEP', new TAction([$this, 'onBuscarCep'], ['cep' => 'cep']), '');
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fas:save');
        $this->form->addActionLink('Limpar', new TAction([$this, 'onClear']), 'fas:eraser red');
    }

    public function onEdit($param)
    {
        try {
            TTransaction::open('development');

            if (empty($param['id'])) {
                throw new Exception('Cliente não encontrado.');
            }

            $cliente = new Cliente($param['id']);
            $this->form->setData($cliente);

            if ($cliente->endereco_id > 0) {
                $endereco = new Endereco($cliente->endereco_id);

                if (!$endereco->idEndereco) {
                    throw new Exception('Endereço não encontrado.');
                }

                $data = (object) array_merge((array) $cliente->toArray(), (array) $endereco->toArray());
                $this->form->setData($data);
            } else {
                new TMessage('info', 'Cliente não possui endereço cadastrado.');
            }

            TTransaction::close();
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onSave()
    {
        try {
            TTransaction::open('development');

            $data = $this->form->getData();

            if ($clienteExistente = Cliente::where('email', '=', $data->email)->first() && $clienteExistente->id != $data->id) {
                throw new Exception('O email já está cadastrado para outro cliente.');
            }

            $cliente = $data->id ? new Cliente($data->id) : new Cliente;
            $endereco = $cliente->endereco_id ? new Endereco($cliente->endereco_id) : new Endereco;

            $endereco->fromArray((array) $data);
            $endereco->store();

            $cliente->endereco_id = $endereco->idEndereco;
            $cliente->fromArray((array) $data);
            $cliente->store();

            TTransaction::close();

            new TMessage('info', 'Cliente e endereço salvos com sucesso!');
            $this->form->clear();
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onClear()
    {
        $this->form->clear();
    }

    public static function onBuscarCep($param) 
    {
        try {
            if (empty($param['cep'])) {
                throw new Exception('Por favor, informe o CEP.');
            }

            $endereco = ConsultaCepService::getCep($param['cep'], 'json');

            if (isset($endereco->erro)) {
                throw new Exception('CEP não encontrado.');
            }

            $object = new stdClass();
            $object->logradouro  = $endereco->logradouro;
            $object->bairro      = $endereco->bairro;
            $object->cidade      = $endereco->localidade;
            $object->estado      = $endereco->uf;
            $object->complemento = $endereco->complemento;

            TForm::sendData('form_cliente', $object);

        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
}
