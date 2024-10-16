<?php
use Adianti\Database\TRecord;

class Estoque extends TRecord
{
    const TABLENAME = 'estoque';
    const PRIMARYKEY = 'id'; // A chave primária será o produto_id
    const IDPOLICY = 'serial'; // Ou manual, depende de como está o seu controle

    public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('produto_id');
        parent::addAttribute('quantidade');
        parent::addAttribute('data_entrada');
    }
}

