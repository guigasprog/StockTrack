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
        $this->form->setFieldSizes('100%');

        $this->dataGrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->dataGrid->addColumn(new TDataGridColumn('id', 'ID', 'left', '5%'));
        $this->dataGrid->addColumn(new TDataGridColumn('nome_cliente', 'Nome do Cliente', 'left', '45%'));
        $this->dataGrid->addColumn(new TDataGridColumn('total', 'Preço', 'left', '20%'));
        $status = new TDataGridColumn('status',       'Status',       'left', '30%');
        $status->setDataProperty('style','font-weight: bold');
        $status->setTransformer(array($this, 'formatStatus'));
        $this->dataGrid->addColumn($status);

        $status = new TCombo('status');
        $status->addItems([
            '' => 'Remover Filtro',
            'pendente' => 'Pendente',
            'pagamento efetuado' => 'Pagamento Efetuado',
            'enviado para entrega' => 'Enviado para Entrega',
            'concluído' => 'Concluído',
            'cancelado' => 'Cancelado'
        ]);
        $status->setChangeAction(new TAction([$this, 'onFilterChange']));
        $row = $this->form->addFields([new TLabel('Status'), $status]);
        $row->layout = ['col-sm-3'];

        $action_view_address = new TDataGridAction([$this, 'onViewEndereco'], ['id' => '{id}']);
        $action_view_address->setLabel('Ver Endereço');
        $action_view_address->setImage('fas:route green');

        $action_view_product = new TDataGridAction([$this, 'onViewProdutos'], ['id' => '{id}']);
        $action_view_product->setLabel('Ver Produtos');
        $action_view_product->setImage('fas:info green');

        $action_atualizar_status_pedido = new TDataGridAction([$this, 'atualizarStatusPedido'], ['id' => '{id}', 'acao' => 'atualizar_status']);
        $action_atualizar_status_pedido->setLabel('Atualizar Status');
        $action_atualizar_status_pedido->setImage('fa:sync-alt blue');

        $action_cancelar_status_pedido = new TDataGridAction([$this, 'atualizarStatusPedido'], ['id' => '{id}', 'acao' => 'cancelar']);
        $action_cancelar_status_pedido->setLabel('Cancelar Pedido');
        $action_cancelar_status_pedido->setImage('fas:ban red');

        $action_export_product_pdf = new TDataGridAction([$this, 'onExportProdutosPedidoPDF'], ['id' => '{id}']);
        $action_export_product_pdf->setLabel('Exportar Produtos para PDF');
        $action_export_product_pdf->setImage('far:file-pdf blue');
    
        // Adiciona as ações na DataGrid
        $this->dataGrid->addAction($action_view_product);
        $this->dataGrid->addAction($action_view_address);
        $this->dataGrid->addAction($action_atualizar_status_pedido);
        $this->dataGrid->addAction($action_cancelar_status_pedido);
        $this->dataGrid->addAction($action_export_product_pdf);

        $this->dataGrid->createModel();
        $this->form->addContent([$this->dataGrid]);

        $this->form->addHeaderAction('Gerar PDF de Todos Pedidos', new TAction([$this, 'onExportAllPedidosPDF']), 'far:file-pdf red');

        parent::add($this->form);

        $this->loadDataGrid();
    }

    public static function onFilterChange($param)
    {
        $status = $param['status'];
        TSession::setValue('filtro_status', $status);
        TApplication::loadPage(__CLASS__, 'onReload');
    }


    public function formatStatus($status, $object, $row)
    {
        if ($status == "pendente")
        {
            return "<span style='background-color: #ffb41e; padding: 5px; border-radius: 5px'>$status</span>";
        }
        else if($status == "cancelado")
        {
            return "<span style='background-color: #ff5046; padding: 5px; border-radius: 5px'>$status</span>";
        }
        else if($status == "concluído")
        {
            return "<span style='background-color: #64ff5a; padding: 5px; border-radius: 5px'>$status</span>";
        }
        else if($status == "pagamento efetuado")
        {
            return "<span style='background-color: #64ff5a; padding: 5px; border-radius: 5px'>$status</span>";
        }
        else if($status == "enviado para entrega")
        {
            return "<span style='background-color: #ffb41e; padding: 5px; border-radius: 5px'>$status</span>";
        }
    }
    
    public static function atualizarStatusPedido($param)
    {
        try {
            TTransaction::open('development');  
            $pedido_id = $param['id'];
            $acao = $param['acao'];

            $pedido = new Pedido($pedido_id);

            if ($pedido->status == 'pendente') {
                if ($acao == 'cancelar') {
                    $pedido->status = 'cancelado';
                    $pedido->store();
                } elseif ($acao == 'atualizar_status') {
                    $pedido->status = 'pagamento efetuado';
                    $pedido->store();
                }
            } elseif ($pedido->status == 'pagamento efetuado' && $acao == 'atualizar_status') {
                $pedidosProdutos = PedidoProduto::where('pedido_id', '=', $pedido->id)->get();

                foreach ($pedidosProdutos as $pedidoProduto) {
                    $produto_id = $pedidoProduto->produto_id;
                    $quantidade_pedido = $pedidoProduto->quantidade;
                
                    $estoque = Estoque::where('produto_id', '=', $produto_id)->orderBy('data_entrada', 'asc')->first();

                    if ($estoque && $estoque->quantidade >= $quantidade_pedido) {
                        
                        $estoque->quantidade -= $quantidade_pedido;
                        $estoque->store();
                
                        $pedido->status = 'enviado para entrega';
                        $pedido->store();
                        
                    } else {
                        throw new TMessage('error', "Estoque insuficiente para o produto $produto_id. Quantidade em estoque: " . ($estoque ? $estoque->quantidade_disponivel : 0));
                    }
                }
                
            } elseif ($pedido->status == 'enviado para entrega' && $acao == 'atualizar_status') {
                $pedido->status = 'concluído';
                $pedido->store();
            } else {
                new TMessage('error', "Ação ou status atual inválido.");
            }
            TTransaction::close();
            TApplication::loadPage('PedidosPage');
        } catch (Exception $e) {
            TTransaction::rollback();  // Em caso de erro, desfaz a transação
            new TMessage('error', $e->getMessage());
        }
    }

    public function loadDataGrid()
    {
        try {
            TTransaction::open('development');
            $repository = new TRepository('Pedido');

            $status = TSession::getValue('filtro_status');
            $repository = new TRepository('Pedido');
            $criteria = new TCriteria;

            if ($status) {
                $criteria->add(new TFilter('status', '=', $status));
            }
            $pedidos = $repository->load($criteria);

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

    public function onExportAllPedidosPDF($param)
    {
        try
        {
            TTransaction::open('development');
            
            // Carrega todos os pedidos
            $repository = new TRepository('Pedido');
            $status = TSession::getValue('filtro_status');
            $criteria = new TCriteria;

            if ($status) {
                $criteria->add(new TFilter('status', '=', $status));
            }

            $pedidos = $repository->load($criteria);
            if (empty($pedidos)) {
                new TMessage('info', 'Não há pedidos para exportar.');
                TTransaction::rollback();
                return; 
            }
            
            $html = '<h3 style="text-align: center; font-family: Arial, sans-serif;">Lista de Pedidos</h3>';
            $html .= '<table style="border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; margin: 20px 0;">';
            $html .= '<tr style="background-color: #f2f2f2; color: #333; text-align: left;">
                        <th style="border: 1px solid #dddddd; padding: 8px;">ID</th>
                        <th style="border: 1px solid #dddddd; padding: 8px;">Nome do Cliente</th>
                        <th style="border: 1px solid #dddddd; padding: 8px;">Total</th>
                        <th style="border: 1px solid #dddddd; padding: 8px;">Status</th>
                    </tr>';
            
            foreach ($pedidos as $pedido) {
                $cliente = $this->loadCliente($pedido->cliente_id);
                $nomeCliente = $cliente->nome ?? 'Desconhecido';
                $html .= "<tr>
                            <td style='border: 1px solid #dddddd; padding: 8px;'>{$pedido->id}</td>
                            <td style='border: 1px solid #dddddd; padding: 8px;'>{$nomeCliente}</td>
                            <td style='border: 1px solid #dddddd; padding: 8px;'>R$ {$pedido->total}</td>
                            <td style='border: 1px solid #dddddd; padding: 8px;'>{$pedido->status}</td>
                        </tr>";
            }
            
            $html .= '</table>';
            
            TTransaction::close();
            
            // Configura e gera o PDF
            $this->generatePDF($html, 'lista_pedidos.pdf', 'Lista de Pedidos');
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onExportProdutosPedidoPDF($param)
    {
        try
        {
            $pedido_id = $param['id'] ?? null;
            if (!$pedido_id) {
                throw new Exception('Pedido não especificado.');
            }

            TTransaction::open('development');

            // Carrega os produtos do pedido específico
            $produtosPedido = $this->loadProdutosPedido($pedido_id);

            if (empty($produtosPedido)) {
                new TMessage('info', 'Não há produtos para este pedido.');
                TTransaction::rollback();
                return;
            }

            $html = '<h3 style="text-align: center; font-family: Arial, sans-serif;">Produtos do Pedido ID: ' . $pedido_id . '</h3>';
            $html .= '<table style="border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; margin: 20px 0;">';
            $html .= '<tr style="background-color: #f2f2f2; color: #333; text-align: left;">
                        <th style="border: 1px solid #dddddd; padding: 8px;">Nome do Produto</th>
                        <th style="border: 1px solid #dddddd; padding: 8px;">Quantidade</th>
                    </tr>';
            
            foreach ($produtosPedido as $pedidoProduto) {
                $produto = $this->loadProduto($pedidoProduto->produto_id);
                $nomeProduto = $produto->nome ?? 'Desconhecido';
                $quantidade = $pedidoProduto->quantidade ?? '0';
                $html .= "<tr>
                            <td style='border: 1px solid #dddddd; padding: 8px;'>{$nomeProduto}</td>
                            <td style='border: 1px solid #dddddd; padding: 8px;'>{$quantidade}</td>
                        </tr>";
            }
            
            $html .= '</table>';

            TTransaction::close();

            // Configura e gera o PDF
            $this->generatePDF($html, 'produtos_pedido_' . $pedido_id . '.pdf', 'Produtos do Pedido');
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }


    private function generatePDF($htmlContent, $fileName, $windowTitle)
    {
        $options = new \Dompdf\Options();
        $options->setChroot(getcwd());

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($htmlContent);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $file = 'app/output/' . $fileName;
        file_put_contents($file, $dompdf->output());

        $window = TWindow::create($windowTitle, 0.8, 0.8);
        $object = new TElement('object');
        $object->data  = $file;
        $object->type  = 'application/pdf';
        $object->style = "width: 100%; height:calc(100% - 10px)";
        $object->add('O navegador não suporta a exibição deste conteúdo, <a style="color:#007bff;" target=_newwindow href="'.$object->data.'"> clique aqui para baixar</a>...');

        $window->add($object);
        $window->show();
    }




}
