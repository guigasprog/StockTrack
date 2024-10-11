<?php
use Adianti\Database\TRecord;

class PedidoProduto extends TRecord
{
    const TABLENAME = 'pedido_produto';
    const PRIMARYKEY = 'pedido_id,produto_id';

    public function __construct($id = null)
    {
        parent::__construct($id);
    }
}
