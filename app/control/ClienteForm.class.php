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

class ClienteForm extends TPage
{
    private $form;

    public function __construct()
    {
        parent::__construct();
        
        $this->form = new BootstrapFormBuilder('form_cliente');
        $this->form->addContent( ['<h4>Cadastro de Cliente</h4><hr>'] );
        $this->form->setFieldSizes('100%');
        
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
        $complemento     = new TEntry('complemento');
        
        $id->setEditable(FALSE);
    
        $cpf->setMask('999.999.999-99');
        
        // Organizando os campos no formulário
        $row = $this->form->addFields([new TLabel('ID'), $id],
                                      [new TLabel('Nome'), $nome],
                                      [new TLabel('CPF'), $cpf]);
        $row->layout = ['col-sm-4', 'col-sm-4', 'col-sm-4'];
        $this->form->addContent( ['<h4 style="margin-top: 1%">Contatos do Cliente</h4>'] );
        $row = $this->form->addFields(  [new TLabel('Telefone'), $telefone],
                                        [new TLabel('Email'), $email]);
        $row->layout = ['col-sm-6','col-sm-6'];
    
        $this->form->addContent( ['<h4 style="margin-top: 1%">Endereço do Cliente</h4>'] );
        
        $row = $this->form->addFields([new TLabel('CEP'), $cep],
                                      [new TLabel('Logradouro'), $logradouro],
                                      [new TLabel('Número'), $numero]
                                      );
        $row->layout = ['col-sm-3', 'col-sm-6', 'col-sm-3'];
    
        $row = $this->form->addFields([new TLabel('Cidade'), $cidade],
                                      [new TLabel('Estado'), $estado],
                                      [new TLabel('Bairro'), $bairro]);
        $row->layout = ['col-sm-4', 'col-sm-4', 'col-sm-4'];
        $row = $this->form->addFields([new TLabel('Complemento'), $complemento]);
        $row->layout = ['col-sm-12'];
    
        $btn_save = new TButton('save');
        $btn_save->setLabel('Salvar');
        $btn_save->setImage('fas:save');
        $btn_save->setAction(new TAction([$this, 'onSave']), 'Salvar');

        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fas:save');
        $this->form->addActionLink('Limpar', new TAction([$this, 'onClear']), 'fas:eraser red');
        
        parent::add($this->form);
    }
    
    public function onEdit($param)
    {
        try {
            TTransaction::open('development');

            if (isset($param['id'])) {
                $cliente = new Cliente($param['id']);
                $this->form->setData($cliente);
                if ($cliente->endereco_id > 0) {
                    $endereco = new Endereco($cliente->endereco_id);
                    
                    if (!$endereco->idEndereco) {
                        throw new Exception('Endereço não encontrado.');
                    }
    
                    $data = (object) array_merge(
                        (array) $cliente->toArray(),
                        (array) $endereco->toArray()
                    );
                    $this->form->setData($data);
                } else {
                    new TMessage('info', 'Cliente não possui endereço cadastrado.');
                }
            } else {
                throw new Exception('Cliente não encontrado.');
            }

            TTransaction::close();
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }


    public function onSave()
    {
        try
        {
            TTransaction::open('development');

            $data = $this->form->getData();
            
            // Validar CPF
            if ($this->isValidCPF($data->cpf)) {
                throw new Exception('CPF inválido');
            }
            $clienteExistente = Cliente::where('email', '=', $data->email)->first();

            if ($clienteExistente && $clienteExistente->id != $data->id) {
                throw new Exception('O email já está cadastrado para outro cliente.');
            }
            
            $cliente = new Cliente;
            if ($data->id) {
                $cliente->load($data->id); // Carrega o cliente existente
            }

            $endereco = new Endereco;
            if ($cliente->endereco_id) {
                $endereco->load($cliente->endereco_id); // Carrega o endereço existente
            }
            $endereco->logradouro = $data->logradouro;
            $endereco->numero = $data->numero;
            $endereco->bairro = $data->bairro;
            $endereco->cidade = $data->cidade;
            $endereco->estado = $data->estado;
            $endereco->complemento = $data->complemento;
            $endereco->cep = $data->cep;
            $endereco->store();
            
            $cliente->endereco_id = $endereco->idEndereco;
            $cliente->fromArray((array) $data);
            $cliente->store();
            
            TTransaction::close();
            
            new TMessage('info', 'Cliente e endereço salvos com sucesso!');
            $this->form->clear(); // Limpa o formulário após salvar
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    
    
    public function onClear()
    {
        $this->form->clear();
    }
    


    public function isValidCPF($cpf) {
            $cpf = preg_replace('/[^0-9]/', '', $cpf);
            error_log($cpf);
            // Verifica se o CPF tem 11 dígitos
            if (strlen($cpf) != 11) {
                return false;
            }
        
            // Verifica se todos os dígitos são iguais (ex.: 111.111.111-11)
            if (preg_match('/^(\d)\1{10}$/', $cpf)) {
                return false;
            }
        
            // Cálculo do primeiro dígito verificador
            $soma = 0;
            for ($i = 0; $i < 9; $i++) {
                $soma += $cpf[$i] * (10 - $i);
            }
            $resto = $soma % 11;
            $digito1 = $resto < 2 ? 0 : 11 - $resto;
        
            // Cálculo do segundo dígito verificador
            $soma = 0;
            for ($i = 0; $i < 10; $i++) {
                $soma += $cpf[$i] * (11 - $i);
            }
            $resto = $soma % 11;
            $digito2 = $resto < 2 ? 0 : 11 - $resto;
        
            // Verifica se os dígitos verificadores são iguais aos calculados
            return ($cpf[9] == $digito1 && $cpf[10] == $digito2);
        }

}
