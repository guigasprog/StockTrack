<?php
class Endereco extends TRecord
{
    const TABLENAME = 'enderecos';
    const PRIMARYKEY = 'idEndereco';
    const IDPOLICY = 'serial'; // Incrementa automaticamente o ID


    public function __construct($id = NULL)
    {
        parent::__construct($id);
        
        parent::addAttribute('logradouro');
        parent::addAttribute('numero');
        parent::addAttribute('complemento');
        parent::addAttribute('bairro');
        parent::addAttribute('cidade');
        parent::addAttribute('estado');
        parent::addAttribute('cep');
    }

}
?>
