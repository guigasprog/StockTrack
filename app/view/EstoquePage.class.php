<?php

require('fpdf.php');

use Adianti\Control\TPage;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Database\TTransaction;
use Adianti\Database\TRepository;
use Adianti\Export\TExportPDF;

class EstoquePage extends TPage
{
    private $form;
    private $dataGrid;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('estoque_page');
        $this->form->setFormTitle('Gestão de Estoque');

        $this->dataGrid = new BootstrapDatagridWrapper(new TDataGrid);

        $this->createColumns();

        $this->dataGrid->createModel();

        $this->form->addContent([$this->dataGrid]);

        $this->addActions();

        $this->loadDataGrid();

        parent::add($this->form);
    }

    private function createColumns()
    {
        $this->dataGrid->addColumn(new TDataGridColumn('id', 'ID', 'left', '5%'));
        $this->dataGrid->addColumn(new TDataGridColumn('produto_nome', 'Produto', 'left', '45%'));
        $this->dataGrid->addColumn(new TDataGridColumn('quantidade', 'Quantidade', 'center', '15%'));
        $this->dataGrid->addColumn(new TDataGridColumn('data_entrada', 'Data de Entrada', 'center', '35%'));
    }

    private function addActions()
    {
        
        $this->form->addHeaderAction('Adicionar', new TAction([$this, 'onAdd']), 'fas:plus');

        $this->form->addHeaderAction('Gerar PDF', new TAction([$this, 'onGeneratePDF']), 'far:file-pdf red');

        $this->form->addHeaderAction('Gerar PDF com Pivot e Agrupado', new TAction([$this, 'onGeneratePDFPivotAgrupado']), 'far:file-pdf red');
        
    }

    public function onAdd()
    {
        TApplication::gotoPage('EstoqueForm');
    }

    public function loadDataGrid()
    {
        $this->dataGrid->clear();
        TTransaction::open('development');

        $repository = new TRepository('Estoque');
        $estoques = $repository->orderBy('data_entrada', 'asc')->load();

        if ($estoques) {
            $estoquesDTO = [];

            foreach ($estoques as $estoque) {
                $produtoRepository = new TRepository('Produto');
                $produto = $produtoRepository->where('id', '=', $estoque->produto_id)->load();

                if ($produto) {
                    $dto = new EstoqueDTO();
                    $dto->id = $estoque->id;
                    $dto->produto_nome = $produto[0]->nome;
                    $dto->quantidade = $estoque->quantidade;
                    $dto->data_entrada = $estoque->data_entrada;

                    $estoquesDTO[] = $dto;
                }
            }

            $this->dataGrid->addItems($estoquesDTO);
        }

        TTransaction::close();
    }

    public function onGeneratePDF()
    {
        try {
            TTransaction::open('development');

            // Carregar os estoques
            $repository = new TRepository('Estoque');
            $estoques = $repository->orderBy('data_entrada', 'asc')->load();

            // Verifica se a lista de estoques está vazia
            if (empty($estoques)) {
                new TMessage('info', 'Não há itens em estoque para gerar o PDF.');
                TTransaction::rollback();
                return; // Finaliza a execução da função
            }

            // Gera HTML para a tabela de estoque
            $html = '<h3 style="text-align: center; font-family: Arial, sans-serif;">Relatório de Estoque</h3>';
            $html .= '<table style="border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; margin: 20px 0;">';
            $html .= '<tr style="background-color: #f2f2f2; color: #333; text-align: left;">
                        <th style="border: 1px solid #dddddd; padding: 8px;">ID</th>
                        <th style="border: 1px solid #dddddd; padding: 8px;">Produto</th>
                        <th style="border: 1px solid #dddddd; padding: 8px;">Quantidade</th>
                        <th style="border: 1px solid #dddddd; padding: 8px;">Data de Entrada</th>
                    </tr>';

            // Corpo da tabela
            foreach ($estoques as $estoque) {
                $produtoRepository = new TRepository('Produto');
                $produto = $produtoRepository->where('id', '=', $estoque->produto_id)->load();

                if ($produto) {
                    $html .= "<tr>
                                <td style='border: 1px solid #ddd; padding: 8px;'>{$estoque->id}</td>
                                <td style='border: 1px solid #ddd; padding: 8px;'>{$produto[0]->nome}</td>
                                <td style='border: 1px solid #ddd; padding: 8px;'>{$estoque->quantidade}</td>
                                <td style='border: 1px solid #ddd; padding: 8px;'>" . date('d/m/Y', strtotime($estoque->data_entrada)) . "</td>
                            </tr>";
                }
            }

            $html .= '</table>';

            TTransaction::close();

            // Gera o PDF utilizando o método generatePDF
            $this->generatePDF($html, 'relatorio_estoque.pdf', 'Relatório de Estoque');
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onGeneratePDFPivotAgrupado()
    {
        try {
            TTransaction::open('development');

            $repository = new TRepository('Estoque');
            $estoques = $repository->orderBy('data_entrada', 'asc')->load();

            if (empty($estoques)) {
                new TMessage('info', 'Não há itens em estoque para gerar o PDF.');
                TTransaction::rollback();
                return; // Finaliza a execução da função
            }

            $estoquesPorMes = $this->groupByMonth($estoques);

            $html = '<h3 style="text-align: center; font-family: Arial, sans-serif;">Relatório de Estoque por Mês</h3>';
            $html .= '<table style="border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; margin: 20px 0;">';
            $html .= '<tr style="background-color: #f2f2f2; color: #333; text-align: left;">
                        <th style="border: 1px solid #dddddd; padding: 8px;">Mês</th>
                        <th style="border: 1px solid #dddddd; padding: 8px;">Quantidade - Produto</th>
                    </tr>';

            foreach ($estoquesPorMes as $mes => $dados) {
                $html .= "<tr>
                            <td style='border: 1px solid #ddd; padding: 8px;'>$mes</td>";
                foreach ($dados as $produtoNome => $quantidadeTotal) {
                    $html .= "<td style='border: 1px solid #ddd; padding: 8px;'>$quantidadeTotal - $produtoNome</td>";
                }
                $html .= "</tr>";
            }

            $html .= '</table>';

            TTransaction::close();

            $this->generatePDF($html, 'relatorio_estoque_pivot_agrupado.pdf', 'Relatório de Estoque por Mês', 'landscape');
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    private function groupByMonth($estoques)
    {
        $result = [];

        foreach ($estoques as $estoque) {
            $mes = date('m/Y', strtotime($estoque->data_entrada)); // Agrupar por mês e ano
            $produtoRepository = new TRepository('Produto');
            $produto = $produtoRepository->where('id', '=', $estoque->produto_id)->load();

            if ($produto) {
                $produtoNome = $produto[0]->nome;
                if (!isset($result[$mes])) {
                    $result[$mes] = [];
                }

                if (!isset($result[$mes][$produtoNome])) {
                    $result[$mes][$produtoNome] = 0;
                }

                $result[$mes][$produtoNome] += $estoque->quantidade;
            }
        }

        return $result;
    }

    private function generatePDF($htmlContent, $fileName, $windowTitle, $scale = "portrait")
    {
        $options = new \Dompdf\Options();
        $options->setChroot(getcwd());

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($htmlContent);
        $dompdf->setPaper('A4', $scale);
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

class EstoqueDTO {
    public $id;
    public $produto_nome;
    public $quantidade;
    public $data_entrada;
}
