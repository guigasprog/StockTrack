<?php
class Produto extends TRecord
{
    const TABLENAME = 'produtos';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'serial'; // Incrementa automaticamente o ID

    private $categoria;

    
    public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('nome');
        parent::addAttribute('descricao');
        parent::addAttribute('validade');
        parent::addAttribute('preco');
        parent::addAttribute('categoria_id');
    }
    
    public function set_categoria(Categoria $categoria)
    {
        $this->categoria = $categoria;
        $this->categoria_id = $categoria->idCategoria; // Atribui o ID da categoria
    }

    public function get_categoria()
    {
        if (empty($this->categoria) && !empty($this->categoria_id)) {
            $this->categoria = new Categoria($this->categoria_id);
        }
        return $this->categoria;
    }

}
?>
