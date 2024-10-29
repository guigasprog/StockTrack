<?php

use Adianti\Control\TPage;
use Adianti\Widget\Form\TFile;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TButton;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Database\TTransaction;
use Adianti\Control\TAction;

class ImagensProdutosForm extends TPage
{
    private $form;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_imagens_produtos');
        $this->form->addContent( ['<h4>Cadastro de Imagens nos Produtos</h4><hr>'] );
        $this->form->setFieldSizes('100%');

        $this->createFormFields();

        $this->addSaveButton();

        parent::add($this->form);
    }

    private function createFormFields()
    {
        $produto_id = new TDBCombo('produto_id', 'development', 'Produto', 'id', 'nome', 'nome');
        $imagem = new TFile('imagem');
        $descricao = new TEntry('descricao');

        $imagem->setAllowedExtensions(['png', 'jpg', 'jpeg']);
        $imagem->enableImageGallery();

        $this->form->addFields([new TLabel('Produto')], [$produto_id]);
        $this->form->addFields([new TLabel('Imagem')], [$imagem]);
        $this->form->addFields([new TLabel('Descrição')], [$descricao]);
    }

    private function addSaveButton()
    {
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'Salvar', 'fas:save');
    }

    public function onSave()
    {
        try {
            TTransaction::open('development');

            $data = $this->form->getData();
            $imagemProduto = new ImagensProduto();

            $imagemProduto->descricao = $data->descricao;
            $imagemProduto->imagem = base64_encode(file_get_contents('C:/xampp/htdocs/stocktrack/tmp/'.$data->imagem));
            $imagemProduto->produto_id = $data->produto_id;
            $imagemProduto->store();

            TTransaction::close();

            new TMessage('info', 'Imagem salva com sucesso!');
            $this->form->clear();
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}
