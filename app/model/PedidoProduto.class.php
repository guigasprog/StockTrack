<?php
use Adianti\Database\TRecord;

class PedidoProduto extends TRecord
{
    const TABLENAME = 'pedido_produto';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'serial';

    public function __construct($id = NULL)
    {
        parent::__construct($id);

        parent::addAttribute('pedido_id');
        parent::addAttribute('produto_id');
        parent::addAttribute('quantidade');
    }
}
