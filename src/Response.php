<?php

namespace Auth;

class Response {
	static $json_options = ['UNESCAPED_SLASHES', 'UNESCAPED_UNICODE'];
	static $default_headers = [
		'Access-Control-Expose-Headers' => 'x-http-method-override',
		'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS, PUT, DELETE',
        'Access-Control-Allow-Origin' => 'http://localhost:8888',
        // 'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Credentials' => 'true', // Required for cookies
		'Content-Type' => 'application/json; charset=utf-8',
	];
	public static $HTTP = [
		200 => [
			'status' => 'Success',
			'code' => 200,
			'message' => 'OK',

		],
		201 => [
			'status' => 'Success',
			'code' => 201,
			'message' => 'Created',
		],
		204 => [
			'status' => 'Error',
			'code' => 204,
			'message' => 'No Content',
		],
		400 => [
			'status' => 'Error',
			'code' => 400,
			'message' => 'Bad Request',
		],
		403 => [
			'status' => 'Error',
			'code' => 403,
			'message' => 'Forbidden',
		],
		404 => [
			'status' => 'Error',
			'code' => 404,
			'message' => 'Not Found',
		],
		409 => [
			'status' => 'Error',
			'code' => 409,
			'message' => 'Conflict',
		],
		503 => [
			'status' => 'Error',
			'code' => 503,
			'message' => 'Service Unavailable',
		],
	];
	private $_code;
	public $status;
	public $message;
	public $content;
	public $options;
	public $contentType;
	public $sent = false;
	public $headers;
	public $empty = false;
	public function __construct($content = null, $code = null, $headers = []) {
		$this->content = $content;
		if (is_array($content) && array_key_exists('status', $content) && array_key_exists('code', $content)) {
			$code = $code ?? $content['code'];
		}
		$this->code = $code ?? 200;
		$this->headers = array_merge(self::$default_headers, $headers);

		$this->contentType = 'application/json';
		$this->options = self::$json_options;
	}
	public function __get($name) {
		$get_name = 'get_' . $name;
		if (method_exists($this, $get_name)) {
			return $this->$get_name();
		}
	}
	public function __set($name, $value) {
		$set_name = 'set_' . $name;
		if (method_exists($this, $set_name)) {
			return $this->$set_name($value);
		}
	}
	public function get_code() {
		return $this->_code;
	}
	public function set_code($value) {
		$this->_code = $value;
		$http = self::$HTTP[$value] ?? null;
		if (!empty($http)) {
			$this->status = $http['status'];
			$this->message = $http['message'];
		} else {
			$this->status = 'error';
			$this->message = '';
		}
	}
	public function header($name, $value) {
		$name = explode('-', $name);
		$name = array_map('ucfirst', $name);
		$name = implode('-', $name);
		$this->headers[$name] = $value;
	}
	public static function reply($data, $http_code = null) {
		if ($data instanceof self) {
			$result = $data;
			if (!empty($http_code)) {
				$result->code = $http_code;
			}
		} else {
			$result = new self($data, $http_code);
		}

		$result->send();
	}
	public static function replyCode($code, $message = null) {
		$content = self::$HTTP[$code] ?? [
			'status' => 'Error',
			'code' => $code,
			'message' => 'Unknown',
		];

		return new self($content, $code);
	}
	public function send($die = true) {
		if ($this->empty) return;
		$result = $this->toJson();
		
		if (!headers_sent()) {
			$this->sendHeaders();
		}
		
		if ($die) {
			exit($result);
		}
	}

	static public function json_encode($content = null, $options = []) {
		$bitmask = 0;

		if (empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
			$options[] = 'PRETTY_PRINT';
		}

		foreach ($options as $option) {
			$bitmask |= defined('JSON_' . $option) ? constant('JSON_' . $option) : 0;
		}
		$result = json_encode($content, $bitmask);
		return $result;
	}
	public function __toString() {
		return $this->toJson();
	}
	public function toJson($content = null, $options = []) {
		$content = $content ?? $this->content;
		$options = $this->options + $options;

		return self::json_encode($content, $options);
	}

	public function sendHeaders() {
		if ($this->sent) return;
		$this->sent = true;

		if (headers_sent()) return;

		http_response_code($this->code);
		foreach ($this->headers as $name => $val) {
			header("{$name}: {$val}");
		}
		return $this;
	}
	static public function empty() {
		$result = new self();
		$result->empty = true;
		return $result;
	}
	static public function redirect($location, $http_code = 302) {
		$result = new self();
		$result->header('Location', $location);
		$result->code = $http_code;
		return $result;
	}

}
