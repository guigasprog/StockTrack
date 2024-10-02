<?php
use Adianti\Database\TRecord;

class Categoria extends TRecord
{
    const TABLENAME = 'categorias';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'serial';

    public function __construct($id = NULL)
    {
        parent::__construct($id);

        parent::addAttribute('nome');
        parent::addAttribute('descricao');
        parent::addAttribute('created_at');
    }

    public function get_produtos()
    {
        return Produto::where('categoria_id', '=', $this->id)->load();
    }
}
