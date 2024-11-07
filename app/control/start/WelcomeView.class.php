<?php

use Adianti\Control\TPage;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Control\TAction;
use Adianti\Widget\Container\TVBox;
use Adianti\Wrapper\BootstrapFormBuilder;

class WelcomeView extends TPage
{
    public function __construct()
    {
        parent::__construct();

        // Criação do formulário para encapsular os botões
        $form = new BootstrapFormBuilder('form_welcome');
        $form->setFormTitle('Menu Principal');

        // Container para centralizar os cards
        $vbox = new TVBox();
        $vbox->style = 'width: 100%; max-width: 800px; 
        margin: auto; padding: 20px; display: flex; 
        flex-warp: warp; gap: 30px; justify-content: center;
        align-items: center;';

        // Títulos e descrições das funcionalidades
        $items = [
            ['Produto', 'Exibe todos os produtos cadastrados', 'ProdutosPage'],
            ['Cliente', 'Exibe todos os clientes cadastrados', 'ClientesPage'],
            ['Estoque', 'Exibe todas atualizações do estoque cadastrados', 'EstoquePage'],
            ['Pedidos', 'Exibe todos os pedidos cadastrados', 'PedidosPage']
        ];

        // Array para armazenar os botões a serem registrados no formulário
        $buttons = [];

        // Criação dos cards para cada item
        foreach ($items as $item) {
            $card = $this->createCard($item[0], $item[1], $item[2], $buttons);
            $vbox->add($card);
        }

        // Adiciona o container de cards ao formulário
        $form->addContent([$vbox]);

        // Define os botões como campos do formulário
        $form->setFields($buttons);

        // Adiciona o formulário à página
        parent::add($form);
    }

    /**
     * Cria um card para exibir informações e um botão de ação
     */
    private function createCard($title, $description, $pageName, &$buttons)
    {
        // Criação do card com título
        $card = new TPanelGroup($title);
        $card->style = 'margin-bottom: 20px; height: 240px';

        // Descrição da funcionalidade
        $label = new TLabel($description);
        $label->style = 'display: block; margin-top: 10px;';

        // Criação do botão com uma ação associada
        $button = new TButton($pageName);
        $button->setLabel('Ir para ' . $title);
        $button->setAction(new TAction([$this, 'onView'.$pageName]), 'Ir para ' . $title);
        $button->setImage('fa:eye green');

        // Adiciona o botão à lista de botões para ser registrado no formulário
        $buttons[] = $button;

        // Adiciona a descrição e o botão ao card
        $card->add($label);
        $card->addFooter($button);

        return $card;
    }

    /**
     * Ações de navegação para cada funcionalidade
     */
    public function onViewProdutosPage()
    {
        AdiantiCoreApplication::gotoPage('ProdutosPage');
    }

    public function onViewClientesPage()
    {
        AdiantiCoreApplication::gotoPage('ClientesPage');
    }

    public function onViewEstoquePage()
    {
        AdiantiCoreApplication::gotoPage('EstoquePage');
    }

    public function onViewPedidosPage()
    {
        AdiantiCoreApplication::gotoPage('PedidosPage');
    }
}
