<?php
use Adianti\Database\TRecord;

class Cliente extends TRecord
{
    const TABLENAME = 'clientes';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'serial';

    private $endereco;

    public function __construct($id = NULL)
    {
        parent::__construct($id);

        parent::addAttribute('endereco_id');
        parent::addAttribute('nome');
        parent::addAttribute('email');
        parent::addAttribute('cpf');
        parent::addAttribute('telefone');
    }

    public function get_endereco()
    {
        if (empty($this->endereco)) {
            $this->endereco = new Endereco($this->endereco_id);
        }
        return $this->endereco;
    }
}
