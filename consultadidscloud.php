<?php

//desativar erros para não poluir o .JSON
error_reporting(0); // Desativa todos os erros
ini_set('display_errors', 0); // Não exibir erros na tela

$empresas = array();
$valorBase = 0;
$resultadoPorPagina = 20;

do {
//array que realiza a consulta
$data = array(
    'autenticacao' => array(
        'usuario' => 'TOKEN-NAME-HERE',
        'token' => 'TOKEN-HERE'
    ),
    'acao' => 'listar_clientes',
    'cliente_id' => "",
    'nome' => "",
    'pos_registro_inicial' => $valorBase,
);


$response = fazerRequisicao($data);

    if ($response !== false) {
    // Decodifique a resposta JSON em um array
    $responseData = json_decode($response, true);
    
    $empresas[] = $response;
    
    // Atualize o valor base para a próxima página
    $valorBase += $resultadoPorPagina;
} else {
    echo "Erro na solicitação à API.";
    break;
}

} while ($valorBase < $responseData['qtd_total_resultados']);

//faz a requisição na API
function fazerRequisicao($data){

$url = 'URL-HERE/pabx/api.php';

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true); 
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); 

// Executar a solicitação e armazenar a resposta em $response
$response = curl_exec($ch);

if ($response === false) {
    echo "Erro cURL: " . curl_error($ch);
}


curl_close($ch);

return $response;

}

//Requisição da API para consultar DIDs

function fazerRequisicaoDids($dataDids){

    $url = 'URL-HERE/pabx/api.php';
    
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url); // Defina a URL
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Receba a resposta como uma string
    curl_setopt($ch, CURLOPT_POST, true); // Usa o método POST
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dataDids)); // Envie os dados como JSON
    
    // Executar a solicitação e armazenar a resposta em $response
    $responseDids = curl_exec($ch);
    
    if ($responseDids === false) {
        echo "Erro cURL: " . curl_error($ch);
    }

    curl_close($ch);

    return $responseDids;

}

//Tratamento de dados Empresas-->

$empresasCombinadas = [];

foreach ($empresas as $empresaJson) {
    $data = json_decode($empresaJson);

    
    $empresasCombinadas = array_merge($empresasCombinadas, $data->dados);
}

//cria um novo objeto da empresa

$newObj = new stdClass();
$newObj->dados = $empresasCombinadas;

// Codifica o novo objeto em JSON
$newJson = json_encode($newObj, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$dataTreat = json_decode($newJson, true);

if ($dataTreat && isset($dataTreat['dados'])) {
    $clienteIds = array(); 

    foreach ($dataTreat['dados'] as $item) {
        if (isset($item['cliente_id'])) {
            $clienteIds[] = $item['cliente_id'];
        }
    }
}

//Tratamento de Dados dos Dids-->

for($i = 0; $i <= $responseData['qtd_total_resultados']; $i++){
    $dataDids = array(
        'autenticacao' => array(
            'usuario' => 'TOKEN-NAME-HERE',
            'token' => 'TOKEN-HERE'
        ),
        'acao' => 'listar_dids',
        'cliente_id' => "$clienteIds[$i]",
     );

     $finalResponse[] = fazerRequisicaoDids($dataDids);
}


   $didsCombinados = [];

   foreach($finalResponse as $didsJson){
    $dataDids = json_decode($didsJson);
    if (is_array($dataDids->dados)) {
        $didsCombinados = array_merge($didsCombinados, $dataDids->dados);
    }
   }

   $DidsObj = new stdClass();
   $DidsObj->dados = $didsCombinados;

   $filename = "data_" . date("YmdHis") . ".json";

// Set the response headers to indicate a JSON file download
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="' . $filename . '"');

   $newDidsJson = json_encode($DidsObj, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);   

   print_r($newDidsJson);

?>