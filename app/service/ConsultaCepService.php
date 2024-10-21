<?php
namespace App\Service;

class ConsultaCepService
{
    
    public static function getCep($cep, $formato = 'json')
    {
        try
        {                                        
            if( isset($cep) )
            {
                $formatos = ['json', 'xml', 'piped', 'querty'];
                if( !in_array($formato, $formatos) )
                {
                    $retorno             = new stdClass;
                    $retorno->erro         = TRUE;
                    $retorno->mensagem     = "Formato <b>{$formato}</b> não suportado!";
                    return $retorno;
                }
                
                $cep = preg_replace("/[^0-9]/", "", $cep);
                
                if( strlen($cep) != 8 )
                {
                    $retorno             = new stdClass;
                    $retorno->erro         = TRUE;
                    $retorno->mensagem     = "CEP: <b>{$cep}</b> não possui 8 digitos!";    
                    return $retorno;                                    
                }
                
                switch($formato)
                {
                    case 'json':                        
                        $retorno = json_decode( file_get_contents("https://viacep.com.br/ws/{$cep}/{$formato}") );
                        break;
                        
                    case 'xml':                        
                        $retorno = htmlentities(file_get_contents("https://viacep.com.br/ws/{$cep}/{$formato}"));
                        break;
                
                    case 'piped':                        
                        $retorno = file_get_contents("https://viacep.com.br/ws/{$cep}/{$formato}");
                        break;
                
                    case 'querty':                        
                        $retorno = file_get_contents("https://viacep.com.br/ws/{$cep}/{$formato}");
                        break;
                }
                if( isset($retorno->erro) )
                {
                    $retorno->mensagem = "CEP: <b>{$cep}</b> não existe na base de dados!";
                }
                
                return $retorno;
            }
        }
        catch (Exception $e)
        {
            echo 'Error: ' . $e->getMessage();
        }
    }
    
}