<?php
namespace stic\Demo\ProxyPayPHP;
require '../vendor/autoload.php';
use stic\ProxyPayPHP\ProxyPay;

// var_dump(openssl_get_cert_locations());

$proxyPay = new ProxyPay([
    'apikey' => '', // trocar com uma key válida
    'certificate' => 'cacert-2020-07-22.pem' // trocar com um pem válido
]);

$data = ([
	"reference" => [
		"amount"=> "100.00",
		"expiry_date" => "2020-08-15",
		"custom_fields"=> [
			"invoice"=> "2020/002",
			"name"=> "Joao Nzango",
            "email"=> "", // trocar com um email válido
            "description"=> "",
            "cellphone"=> "" // trocar com um telefoneválido
        ]
    ]
]);  

// $proxyPay->GenerateReference($data);
// $proxyPay->GetOneReference("9y1mI8fv6kaxUag6Wu");
$proxyPay->GetAllReferences()
->then(function ($response) {
    "Here " . $response->getBody();
}, function (ClientException $e) {
    $response = [];
    echo $e->getMessage();
    // $response->data = $e->getMessage();
    return $response;
});
// $proxyPay->FetchNewPayments();
// $proxyPay->AcknowledgePayments();