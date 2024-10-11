<?php
use Adianti\Database\TRecord;

class Pedido extends TRecord
{
    const TABLENAME = 'pedidos';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'serial';

    private $produtos;

    public function __construct($id = NULL)
    {
        parent::__construct($id);

        parent::addAttribute('cliente_id');
        parent::addAttribute('data_pedido');
        parent::addAttribute('total');
        parent::addAttribute('status');
    }

    public function getProdutos()
    {
        return $this->produtos;
    }

    public function setProdutos($produtos)
    {
        $this->produtos = $produtos;
    }

}
