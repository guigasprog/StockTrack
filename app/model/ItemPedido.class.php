<?php
use Adianti\Database\TRecord;

class ItemPedido extends TRecord
{
    const TABLENAME = 'itens_pedido';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'serial';

    public function __construct($id = NULL)
    {
        parent::__construct($id);

        parent::addAttribute('pedido_id');
        parent::addAttribute('produto_id');
        parent::addAttribute('quantidade');
        parent::addAttribute('preco');
    }
}
