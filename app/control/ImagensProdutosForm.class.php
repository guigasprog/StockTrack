<?php

use Adianti\Control\TPage;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TFile;
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
        $this->form->setFormTitle('Cadastro de Imagens do Produto');
        $this->form->setFieldSizes('100%');

        // Criação dos campos do formulário
        $produto_id = new TDBCombo('produto_id', 'development', 'Produto', 'id', 'nome', 'nome');
        $imagem = new TFile('imagem');
        $descricao = new TEntry('descricao');

        $imagem->setAllowedExtensions( ['png', 'jpg', 'jpeg'] );
        $imagem->enableImageGallery();
        
        // Adicionando os campos ao formulário
        $this->form->addFields([new TLabel('Produto')], [$produto_id]);
        $this->form->addFields([new TLabel('Imagem')], [$imagem]);
        $this->form->addFields([new TLabel('Descrição')], [$descricao]);

        // Botão de ação para salvar a imagem
        $btn_save = new TButton('save');
        $btn_save->setLabel('Salvar Imagem');
        $btn_save->setImage('fas:save');
        $btn_save->setAction(new TAction([$this, 'onSave']), 'Salvar');

        // Adicionando o botão ao formulário
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fas:save');

        parent::add($this->form);
    }

    // Método para salvar a imagem do produto
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
            $this->form->clear(); // Limpa o formulário
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback(); // Reverte a transação em caso de erro
        }
    }
}
