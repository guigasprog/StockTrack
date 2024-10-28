<?php

use Adianti\Control\TPage;
use Adianti\Widget\Wrapper\TTable;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\TLabel;
use Adianti\Control\TAction;
use Adianti\Widget\Container\TPanel;

class WelcomeView extends TPage
{
    public function __construct()
    {
        parent::__construct();

        // Cria um painel para organizar os itens do menu
        $panel = new TPanel();
        $panel->setTitle('Menu Principal');
        $panel->setWidth(600);

        // Criação da tabela para exibir os itens do menu
        $table = new TTable();
        $table->border = 1;

        // Adiciona o cabeçalho da tabela
        $table->addRowSet(new TLabel('Tela'), new TLabel('Descrição'));

        // Adiciona os itens do menu
        $this->addMenuItem($table, 'Produto', 'Exibe todos os produtos cadastrados.', 'onViewProduct');
        $this->addMenuItem($table, 'Categoria', 'Exibe todas as categorias cadastradas.', 'onViewCategory');
        $this->addMenuItem($table, 'Fornecedor', 'Exibe todos os fornecedores cadastrados.', 'onViewSupplier');

        // Adiciona a tabela ao painel
        $panel->add($table);
        parent::add($panel);
    }

    private function addMenuItem($table, $tela, $descricao, $action)
    {
        $row = $table->addRow();
        $row->addCell(new TLabel($tela));
        $row->addCell(new TLabel($descricao));

        // Botão para acessar a tela de visualização correspondente
        $button = new TButton($action);
        $button->setLabel('Ver');
        $button->setAction(new TAction([$this, $action]), 'Ver ' . $tela);
        
        $row->addCell($button);
    }

    public function onViewProduct()
    {
        // Aqui você pode definir a lógica para exibir a tela de produtos
        AdiantiCoreApplication::gotoPage('ProdutoView');
    }

    public function onViewCategory()
    {
        // Aqui você pode definir a lógica para exibir a tela de categorias
        AdiantiCoreApplication::gotoPage('CategoriaView');
    }

    public function onViewSupplier()
    {
        // Aqui você pode definir a lógica para exibir a tela de fornecedores
        AdiantiCoreApplication::gotoPage('FornecedorView');
    }
}
