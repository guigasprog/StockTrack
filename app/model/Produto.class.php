<?php
use Adianti\Database\TRecord;

class Produto extends TRecord
{
    const TABLENAME = 'produtos';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'serial';

    public function __construct($id = NULL)
    {
        parent::__construct($id);

        parent::addAttribute('nome');
        parent::addAttribute('descricao');
        parent::addAttribute('preco');
        parent::addAttribute('quantidade');
        parent::addAttribute('created_at');
        parent::addAttribute('categoria_id');
    }

    public function get_categoria()
    {
        return Categoria::find($this->categoria_id);
    }
}
