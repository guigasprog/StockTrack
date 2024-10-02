<?php
use Adianti\Database\TRecord;

class Pedido extends TRecord
{
    const TABLENAME = 'pedidos';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'serial';

    public function __construct($id = NULL)
    {
        parent::__construct($id);

        parent::addAttribute('cliente_id');
        parent::addAttribute('data_pedido');
        parent::addAttribute('total');
        parent::addAttribute('status');
    }
}
