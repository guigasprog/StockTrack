<?php
use Adianti\Database\TRecord;

class Estoque extends TRecord
{
    const TABLENAME = 'estoque';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'serial';

    public function __construct($id = NULL)
    {
        parent::__construct($id);

        parent::addAttribute('produto_id');
        parent::addAttribute('quantidade');
        parent::addAttribute('data_entrada');
    }
}
