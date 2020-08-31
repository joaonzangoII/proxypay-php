<?php
namespace stic\ProxyPayPHP;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\ClientException;

class ProxyPay {
	protected $config;
	// ProxyPay constructor
	public function __construct($config) {
		$caFile = __DIR__.'/../res/cacert.pem';

		$this->config = (object) [
			"host" =>  "https://api.proxypay.co.ao",
			"apikey" => base64_encode("api:" . $config['apikey']),
			"certificate" =>  $config['certificate'] || $caFile
		];
	}

	/** 
	* Create the headers 
	* @var $data
	*/
	function create_headers($data) {
		return [
			'Authorization'  => 'Basic '. $this->config->apikey,
			'Accept'         => 'application/vnd.proxypay.v1+json',
			'Content-Type'   => 'application/json',
			// 'Content-Length' => strlen($data),
			"json" => true,
		];
	}

	/*
	* Method name: Generate.
	* Description: It generates a new reference.
	* params: data - An object containing all information needed to generate a new reference.
	*/
	public function GenerateReference($data) {
		// Send an asynchronous request.
		$request = new Request('POST', 
			$this->config->host . "/references", 
			$this->create_headers($data), 
			json_encode($data)
		);
		$client = new Client([
			'verify' => $this->config->certificate,
		]);

		$promise = $client->sendAsync($request)
		->then(function ($response) {
			echo $response->getBody();
		}, function (ClientException $e) {
			$response = [];
			echo $e->getMessage();
			return $response;
		});
		$promise->wait();

		return $promise;
	}

	/**
	* Method name: GetAll.
	* Description: This method returns all references.
	* @var params: N/A
	*/
	public function GetAllReferences($params=null){
		if(!$params) {
			$params = (object) [
				"limit" => 20,
				"offset" => 0,
				"status" => "",
				"q" => ""
			];
		}

		$this->params = (object)[
			"limit" =>  $params->limit || 20,
			"offset" => $params->offset || 0,
			"status" => $params->offset || "",
			"q" => $params->q || ""
		];

		// Reference to the ProxyPay this
		$that = $this;

		$query = "?limit=" .   $this->params->limit;
		$query .= "&offset=" . $this->params->offset;
		$query .= "&status=" . $this->params->status;
		$query .= "&q=" . $this->params->q;

		// Send an asynchronous request.
		$request = new Request('GET', $this->config->host . "/references" . $query, $this->create_headers([]));
		$client = new Client(['verify' => $this->config->certificate]);
		$promise = $client->sendAsync($request)
		->then(function ($response) {
			echo $response->getBody();
		}, function (ClientException $e) {
			$response = [];
			echo $e->getMessage();
		});
		return $promise->wait();
	}

	/*
	* Method name: GetAll.
	* Description: This method returns and object with the specified reference.
	* params: id - A string
	*/
	public function GetOneReference($id) {
		// Reference to the ProxyPay this
		$that = $this;
		// Send an asynchronous request.
		$request = new Request('GET', $this->config->host . "/references/" . $id, $this->create_headers([]));
		$client = new Client(['verify' => $this->config->certificate]);

		$promise = $client->sendAsync($request)
		->then(function ($response) {
			echo $response->getBody();
		},function (ClientException $e) {
			$response = [];
			return $response;
		});
		$promise->wait();
		return $promise;
	}

	/*
	* Method name: FetchNewPayments.
	* Description: This method returns and object with all payments made.
	* params: N / A
	*/
	public function FetchNewPayments($params=null) {
		if(!$params) {
			$params = (object) [
				"max" => 100,
				"offset" => 0,
			];
		}

		$this->params = (object)[
			"limit" =>  $params->max || 100,
			"offset" => $params->offset || 0,
		];

		$query = "?n=" .   $this->params->limit;
		$query .= "&offset=" . $this->params->offset;

		// Send an asynchronous request.
		$request = new Request('GET', $this->config->host . "/events/payments" . $query, $this->create_headers([]));
		$client = new Client(['verify' => $this->config->certificate]);
		$promise = $client->sendAsync($request)
		->then(function ($response) {
			echo $response->getBody();
		}, function (ClientException $e) {
			$response = [];
			echo $e->getMessage();
			return $response;
		});
		$promise->wait();
		return $promise;
	}	

	/*
	* Method name: Acknowledge Payment.
	* Description: This method acknowledges that a specific payment has been processed..
	* params: paymentid - This parameter can be a string or an array with all ids that need to be
	* acknowledged
	*/
	public function AcknowledgePayments($paymentId=null) {
		// Check if paymentid is a string for just a single payment or an array for 
		// multiple payments
		$multipleIds = null;
		if(gettype($paymentId) === "string"){
			$multipleIds = null;
		}else if(gettype($paymentId) === "object" && count($paymentId) > 0){
			$multipleIds = ["ids" => $paymentid];
		}

		$request = new Request('DELETE', 
			$this->config->host . "/events/payments/" . $paymentId, 
			$this->create_headers([]),
			json_encode($multipleIds)
		);
		$client = new Client(['verify' => $this->config->certificate]);
		$promise = $client->sendAsync($request)->then(function ($response) {
			$response->getBody();
		}, function (ClientException $e) {
			$response = [];
			echo $e->getMessage();
			return $response;
		});
		$promise->wait();		

		return $promise;

	}
}