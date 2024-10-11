<?php
class Produto extends TRecord
{
    const TABLENAME = 'produtos';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'serial'; // Incrementa automaticamente o ID

    public function __construct($id = NULL)
    {
        parent::__construct($id);

        // Adicionando os atributos da tabela `produtos`
        parent::addAttribute('nome');
        parent::addAttribute('descricao');
        parent::addAttribute('preco');
        parent::addAttribute('quantidade'); // Novo campo de quantidade
        parent::addAttribute('validade'); // Novo campo de validade
    }
}
?>
