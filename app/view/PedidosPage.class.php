<?php

require 'vendor/autoload.php';

use Adianti\Control\TPage;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Database\TTransaction;
use Adianti\Database\TRepository;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TInputDialog;

class PedidosPage extends TPage
{
    private $form;
    private $dataGrid;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('page_pedidos');
        $this->form->setFormTitle('Pedidos');

        $this->dataGrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->dataGrid->addColumn(new TDataGridColumn('id', 'ID', 'left', '5%'));
        $this->dataGrid->addColumn(new TDataGridColumn('nome_cliente', 'Nome do Cliente', 'left', '45%'));
        $this->dataGrid->addColumn(new TDataGridColumn('total', 'Preço', 'left', '20%'));
        $this->dataGrid->addColumn(new TDataGridColumn('status', 'Status', 'left', '30%'));

        $action_view_address = new TDataGridAction([$this, 'onViewEndereco'], ['id' => '{id}']);
        $action_view_address->setLabel('Ver Endereço');
        $action_view_address->setImage('fas:eye green');

        $action_view_product = new TDataGridAction([$this, 'onViewProdutos'], ['id' => '{id}']);
        $action_view_product->setLabel('Ver Produtos');
        $action_view_product->setImage('fas:info green');

        $action_geration_pdf = new TDataGridAction([$this, 'generatePDF'], ['id' => '{id}']);
        $action_geration_pdf->setLabel('Gerar Relatório');
        $action_geration_pdf->setImage('fa:file');

        $this->dataGrid->addAction($action_view_product);
        $this->dataGrid->addAction($action_view_address);
        $this->dataGrid->addAction($action_geration_pdf);

        $this->dataGrid->createModel();
        $this->form->addContent([$this->dataGrid]);

        
        $this->form->addAction('Gerar Relatório', new TAction([$this, 'generatePDF'], ['id' => '{id}']), 'fas:plus');

        parent::add($this->form);

        $this->loadDataGrid();
    }

    public function loadDataGrid()
    {
        try {
            TTransaction::open('development');
            $repository = new TRepository('Pedido');
            $pedidos = $repository->load();

            foreach ($pedidos as $pedido) {
                $cliente = $this->loadCliente($pedido->cliente_id);
                $pedido->nome_cliente = $cliente->nome ?? 'Desconhecido';
            }

            if ($pedidos) {
                $this->dataGrid->addItems($pedidos);
            }

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    private function loadCliente($cliente_id)
    {
        $clienteRepository = new TRepository('Cliente');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id', '=', $cliente_id));
        return $clienteRepository->load($criteria)[0] ?? null;
    }

    private function loadEndereco($cliente_id)
    {
        $cliente = $this->loadCliente($cliente_id);

        if ($cliente) {
            $enderecoRepository = new TRepository('Endereco');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('idEndereco', '=', $cliente->endereco_id));
            return $enderecoRepository->load($criteria)[0] ?? null;
        }

        return null;
    }

    public static function onViewEndereco($param)
    {
        try {
            TTransaction::open('development');
            $pedido_id = $param['id'] ?? null;

            if ($pedido_id) {
                $repository = new TRepository('Pedido');
                $pedido = $repository->load(new TCriteria([new TFilter('id', '=', $pedido_id)]))[0] ?? null;

                if ($pedido) {
                    $endereco = (new self)->loadEndereco($pedido->cliente_id);
                    $localizacao = ($endereco->numero && $endereco->numero != 'S/N')
                        ? "{$endereco->logradouro}, {$endereco->numero} - {$endereco->bairro}, {$endereco->cidade} - {$endereco->estado}, {$endereco->cep}"
                        : $endereco->cep;

                    $url = 'https://www.google.com.br/maps/place/' . urlencode($localizacao);
                    echo "<script>window.open('{$url}');</script>";
                }
            }

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onViewProdutos($param)
    {
        try {
            TTransaction::open('development');
            $pedido_id = $param['id'] ?? null;

            if ($pedido_id) {
                $pedidosProdutos = $this->loadProdutosPedido($pedido_id);

                if ($pedidosProdutos) {
                    $dialogForm = new BootstrapFormBuilder('view_produtos');
                    $dialogForm->setFieldSizes('100%');
                    
                    $estoqueTable = new TTable;
                    $estoqueTable->style = 'width: 100%; text-align: center';
                    $estoqueTable->addRowSet('Nome do Produto', 'Quantidade');

                    foreach ($pedidosProdutos as $pedidoProduto) {
                        $produto = $this->loadProduto($pedidoProduto->produto_id);
                        $estoqueTable->addRowSet($produto->nome ?? 'Desconhecido', $pedidoProduto->quantidade ?? '0');
                    }

                    $dialogForm->add($estoqueTable);
                    new TInputDialog('Produtos do Pedido', $dialogForm);
                } else {
                    new TMessage('info', 'Nenhum produto encontrado para este pedido.');
                }
            }

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    private function loadProdutosPedido($pedido_id)
    {
        $pedidoProdutoRepository = new TRepository('PedidoProduto');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('pedido_id', '=', $pedido_id));
        return $pedidoProdutoRepository->load($criteria);
    }

    private function loadProduto($produto_id)
    {
        $produtoRepository = new TRepository('Produto');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id', '=', $produto_id));
        return $produtoRepository->load($criteria)[0] ?? null;
    }

    public function generatePDF($param)
    {
        // TTransaction::open('development');

        // // Obter a conexão do banco de dados
        // $conn = TTransaction::get(); // Obtenha o objeto de conexão aqui
        // if (!is_object($conn)) {
        //     throw new Exception('Conexão com o banco de dados não estabelecida.');
        // }

        // // Obter o pedido pelo ID
        // $pedido = new Pedido($param['id']);
        // if (!$pedido->id) {
        //     throw new Exception('Pedido não encontrado');
        // }

        // // Obter nome do cliente
        // $cliente = new Cliente($pedido->cliente_id);
        // if (!$cliente->id) {
        //     throw new Exception('Cliente não encontrado');
        // }
        // $clienteNome = $cliente->nome;

        // // Definir a consulta para obter os produtos do pedido
        // $query = 'SELECT p.id as "id",
        //                  p.nome as "produto",
        //                  pp.quantidade as "quantidade",
        //                  p.preco as "preco_unitario"
        //           FROM pedido_produto pp
        //           INNER JOIN produtos p ON pp.produto_id = p.id
        //           WHERE pp.pedido_id = :pedido_id';

        // // Obter os dados do banco, passando o objeto de conexão
        // $rows = TDatabase::getData($conn, $query, null, ['pedido_id' => $pedido->id]);

        // if (empty($rows)) {
        //     throw new Exception('Nenhum produto encontrado para este pedido');
        // }

        // // Criar um novo documento PDF
        // $pdf = new TCPDF();

        // // Configurar o documento
        // $pdf->SetCreator(PDF_CREATOR);
        // $pdf->SetAuthor('Seu Nome');
        // $pdf->SetTitle("Pedido - " . $pedido->id);
        // $pdf->SetMargins(15, 15, 15);
        // $pdf->SetAutoPageBreak(TRUE, 10);
        // $pdf->AddPage();

        // // Definir fonte
        // $pdf->SetFont('helvetica', 'B', 16);
        // $pdf->Write(0, "Pedido de $clienteNome", '', 0, 'C', true, 0, false, false, 0);
        // $pdf->Ln(10); // Linha em branco

        // // Criar tabela
        // $pdf->SetFont('helvetica', 'B', 12);
        // $html = "<table border=\"1\" cellpadding=\"5\">
        //             <tr>
        //                 <th>ID</th>
        //                 <th>Produto</th>
        //                 <th>Quantidade</th>
        //                 <th>Preço Unitário</th>
        //             </tr>";
        
        // // Adicionar produtos à tabela
        // foreach ($rows as $row) {
        //     $html .= "<tr>
        //                 <td>{$row['id']}</td>
        //                 <td>{$row['produto']}</td>
        //                 <td>{$row['quantidade']}</td>
        //                 <td>R$ {$row['preco_unitario']}</td>
        //               </tr>";
        // }
        
        // $html .= "</table>";

        // // Adicionar HTML ao PDF
        // $pdf->SetFont('helvetica', '', 12);
        // $pdf->writeHTML($html, true, false, true, false, '');

        // header('Content-Type: application/pdf');
        // header('Content-Disposition: inline; filename="pedido_' . $pedido->id . '.pdf"');
        // header('Cache-Control: private, max-age=0, must-revalidate');
        // header('Pragma: public');
        
        // // Limpar o buffer para evitar conteúdo indesejado antes do PDF
        // ob_end_clean();

        // // Fechar e gerar o PDF
        // $pdf->Output('pedido_'.$pedido->id.'.pdf', 'I');

        // // Fechar a transação
        // TTransaction::close();
    }



}
