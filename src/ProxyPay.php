<?php
namespace stic\ProxyPayPHP;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\ClientException;
use Exception;

class ReferenceParams {
	public $limit;
	public $offset;
	public $status;
	public $q;

	public function __construct($limit = 20, $offset = 0, $status = "", $q = "") {
		if(!is_int($limit)) {
            throw new \InvalidArgumentException('Limit only accepts integers. Input was: '.$limit);
		}

		if(!is_int($offset)) {
            throw new \InvalidArgumentException('Offset only accepts integers. Input was: '.$limit);
		}
		
		$this->limit = $limit;
		$this->offset = $offset;
		$this->status = $offset;
		$this->q = $q;
	}
}

class PaymentParams {
	public $limit;
	public $offset;

	public function __construct($limit = 100, $offset = 0) {
        if(!is_int($limit)) {
            throw new \InvalidArgumentException('Limit only accepts integers. Input was: '.$limit);
		}

		if(!is_int($offset)) {
            throw new \InvalidArgumentException('Offset only accepts integers. Input was: '.$limit);
		}
		
		$this->limit = $limit;
		$this->offset = $offset;
	}
}

class ProxyPay {
	protected $config;
	// ProxyPay constructor
	public function __construct($config) {
		$caFile = __DIR__.'/../res/cacert.pem';
		if(!array_key_exists('producao', $config)) {
			$config['producao'] = false;
		}
		$apiUrl = $config['producao'] 
		? "https://api.proxypay.co.ao" 
		: "https://api.sandbox.proxypay.co.ao";
		$this->config = (object) [
			"host" =>  $apiUrl,
			"apikey" => base64_encode("api:" . $config['apikey']),
			"certificate" =>  $caFile ?? $config['certificate']
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
			return $response->getBody()->getContents();
		}, function (Exception $e) {
			return $e->getMessage();
		});
		return $promise->wait();
	}

	/**
	* Method name: GetAll.
	* Description: This method returns all references.
	* @var params: N/A
	*/
	public function GetAllReferences($params=null){
		if(!$params) {
			$params = new ReferenceParams();
		}else{
			if(!is_array($params)) {
				throw new \InvalidArgumentException('Function only accepts an array. Input was: '.$params);
			}

			if(gettype($params) === 'array' ) {
				$params = new ReferenceParams(...$params);
			}
		}

		// Reference to the ProxyPay this
		$that = $this;
		$query = "?limit=" .   $params->limit;
		$query .= "&offset=" . $params->offset;
		$query .= "&status=" . $params->status;
		$query .= "&q=" . $params->q;
		// Send an asynchronous request.
		$request = new Request('GET', $this->config->host . "/references" . $query, $this->create_headers([]));
		$client = new Client(['verify' => $this->config->certificate]);
		$promise = $client->sendAsync($request)
		->then(function ($response) {
			return $response->getBody()->getContents();
		}, function (Exception $e) {
			return $e->getMessage();
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
			return $response->getBody()->getContents();
		},function (Exception $e) {
			return $e->getMessage();
		});
		return $promise->wait();
	}

	/*
	* Method name: FetchNewPayments.
	* Description: This method returns and object with all payments made.
	* params: N / A
	*/
	public function FetchNewPayments($params=null) {
		if(!$params) {
			$params = new PaymentParams();
		}else{
			if(!is_array($params)) {
				throw new \InvalidArgumentException('Function only accepts an array. Input was: '.$params);
			}
			if(gettype($params) === 'array' ) {
				$params = new PaymentParams(...$params);
			}
		}

		$query = "?n=" .  $params->limit;
		$query .= "&offset=" . $params->offset;

		// Send an asynchronous request.
		$request = new Request('GET', $this->config->host . "/events/payments" . $query, $this->create_headers([]));
		$client = new Client(['verify' => $this->config->certificate]);
		$promise = $client->sendAsync($request)
		->then(function ($response) {
			return $response->getBody()->getContents();
		}, function (Exception $e) {
			return $e->getMessage();
		});
		return $promise->wait();
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
		$promise = $client->sendAsync($request)
		->then(function ($response) {
			return $response->getBody()->getContents();
		}, function (Exception $e) {
			return $e->getMessage();
		});
		return $promise->wait();

	}
}