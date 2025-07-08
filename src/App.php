<?php

namespace Auth;

use RestInPeace\HasAccessors;

class App {
	use UsesDB;
	use HasAccessors;

	/** @var int $id The unique identifier for the app */
	public $id;
	/** @var string $name The name of the app */
	public $name;
	/** @var string $app_key The unique key for the app */
	public $app_key;
	/** @var string|null $app_secret The secret key for the app */
	public $app_secret;
	/** @var string|null $description A brief description of the app */
	public $description;
	/** @var string|null $contact_email The contact email for the app */
	public $contact_email;
	/** @var array|null $_providers The providers associated with the app */
	public $_providers;
	/** @var array|null $_databases The databases associated with the app */
	public $_databases;
	/** @var array|null $_allowed_referers The allowed referer for the app */
	public $_allowed_referers = [];
	/** @var array|null $_allowed_ips The allowed IPs for the app */
	public $_allowed_ips = [];
	/** @var bool $is_active Indicates if the app is active */
	public $is_active = true;
	/** @var string $created_at The timestamp when the app was created */
	public $created_at;
	/** @var string $updated_at The timestamp when the app was last updated */
	public $updated_at;


	/**
	 * Constructor to initialize the User object with given data.
	 *
	 * @param array $data The data to initialize the User object.
	 */
	public function __construct($data = null) {
		if ($data) {
			$this->fill($data);
		} else {
			$this->created_at = date('Y-m-d H:i:s');
			$this->updated_at = date('Y-m-d H:i:s');
		}
	}
	public function fill(array $data = []) {
		$this->id = $data['id'] ?? null;
		$this->name = $data['name'] ?? null;
		$this->app_key = $data['app_key'] ?? null;
		$this->app_secret = $data['app_secret'] ?? null;
		$this->description = $data['description'] ?? null;
		$this->contact_email = $data['contact_email'] ?? null;

		$this->databases = $data['database'] ?? [];
		$this->allowed_referers = $data['allowed_referer'] ?? [];
		$this->allowed_ips = $data['allowed_ips'] ?? [];
		$this->is_active = isset($data['is_active']) ? (bool)$data['is_active'] : true;
		$this->created_at = $data['created_at'] ?? date('Y-m-d H:i:s');
		$this->updated_at = $data['updated_at'] ?? date('Y-m-d H:i:s');
		return $this;
	}
	public function get_providers() {
		return $this->_providers;
	}
	public function set_providers($value) {
		$this->_providers = $this->explode($value);
		return $this;	
	}
	public function get_databases() {
		return $this->_databases;
	}
	public function set_databases($value) {
		$this->_databases = $this->explode($value);
		return $this;	
	}

	public function get_allowed_referer() {
		return $this->_allowed_referers;
	}
	public function set_allowed_referer($value) {
		$this->_allowed_referers = $this->explode($value);
		return $this;	
	}
	public function get_allowed_ips() {
		return $this->_allowed_ips;
	}
	public function set_allowed_ips($value) {
		$this->_allowed_ips = $this->explode($value);
		return $this;	
	}
	public function explode($value, $delimiter = "|", $remove_empty = true) {
		if (empty($value)) {
			return [];
		}
		$result = $value;
		
		if (is_string($value)) {
			$result = explode($delimiter, $value);
		}
		if ($remove_empty) {
			$result = array_filter($result, fn($item) => !empty($item));
		}
		return $result;
	}
	static function fromKey($app_key) {
		$SQL = "SELECT * FROM app WHERE app_key = ?";
		$stmt = self::execQuery($SQL, [$app_key]);
		$result = $stmt->fetch();
		if (!$result) {
			throw new \Exception("App not found for key: $app_key");
		}
		if (empty($result->id)) {
			throw new \Exception("App ID is empty for key: $app_key");
		}
		return $result;
	}
}
