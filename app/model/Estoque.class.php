<?php
use Adianti\Database\TRecord;

class Estoque extends TRecord
{
    const TABLENAME = 'estoque';
    const PRIMARYKEY = 'produto_id'; // A chave primária será o produto_id
    const IDPOLICY = 'serial'; // Ou manual, depende de como está o seu controle

    public function __construct($produto_id = NULL)
    {
        parent::__construct($produto_id);
        parent::addAttribute('quantidade');
        parent::addAttribute('data_entrada');
    }
}

