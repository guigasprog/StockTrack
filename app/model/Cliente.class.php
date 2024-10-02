<?php
use Adianti\Database\TRecord;

class Cliente extends TRecord
{
    const TABLENAME = 'clientes';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'serial';

    private $id;
    private $nome;
    private $email;
    private $telefone;
    private $endereco;
    private $created_at;

    public function __construct($id = NULL)
    {
        parent::__construct($id);

        parent::addAttribute('nome');
        parent::addAttribute('email');
        parent::addAttribute('telefone');
        parent::addAttribute('endereco');
        parent::addAttribute('created_at');
    }
}
