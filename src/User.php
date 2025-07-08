<?php

namespace Auth;

class User {
	use UsesDB;

	/** @var int $id The unique identifier for the user */
	public $id;
	/** @var int $provider_id The identifier for the provider */
	public $provider_id;
	/** @var string $login The login name of the user */
	public $login;
	/** @var string|null $email The email address of the user */
	public $email;
	/** @var string|null $name The name of the user */
	public $name;
	/** @var string|null $password The password of the user */
	public $password;
	/** @var string|null $status The status of the user */
	public $status = '1';
	/** @var string|null $extra Additional information about the user */
	public $extra;
	/** @var string $created_at The timestamp when the user was created */
	public $created_at;
	/** @var string $updated_at The timestamp when the user was last updated */
	public $updated_at;
	public $_token;

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
		$this->provider_id = $data['provider_id'] ?? null;
		$this->login = $data['login'] ?? null;
		$this->email = $data['email'] ?? null;
		$this->name = $data['name'] ?? null;
		$this->password = $data['password'] ?? null;
		$this->status = $data['status'] ?? 1;
		$this->extra = $data['extra'] ?? null;
		$this->created_at = $data['created_at'] ?? date('Y-m-d H:i:s');
		$this->updated_at = $data['updated_at'] ?? date('Y-m-d H:i:s');
		return $this;
	}
	function getToken() {
		if (!isset($this->_token)) {
			$this->setToken($_COOKIE['token'] ?? $_SESSION['token'] ?? $this->generateToken());
		}
		return $this->_token;
	}
	function setToken($token) {
		$this->_token = $token;
		setcookie('token', $token, time() + 60 * 60 * 24 * 30, '/', '', false, true); // Secure and HttpOnly
		$_SESSION['token'] = $token; // Store token in session
	}
	function generateToken() {
		return bin2hex(random_bytes(32));
	}
	function create() {
		$result = self::insert('user', $this->newData());
		return $result; // Return the new user ID
	}
    function fetchUser() {
        $SQL[] = "SELECT id FROM user";
        $SQL[] = "WHERE login = ?";
        $SQL[] = "AND provider_id = ?";
        $SQL = implode(' ', $SQL);
        $data = [
            $this->login, 
            $this->provider_id,
        ];
        $result = self::execQuery($SQL, $data)->fetch();
        if ($result) {
            $this->id = $result->id;
        }
        return $result;
    }
    function getUserId() {
        $result = $this->fetchUser();
        if (!$result) {
            return null;
        }
        return $result->id;
    }

    function getOrCreateUser() {
        $user = $this->fetchUser();
        if (!$user) {
            $this->createUser();
        }
        return $this->id;
    }

    function newData() {
        $result = [
            'provider_id' => $this->provider_id ?: 0,
            'login' => $this->login,
            'email' => $this->email ?? null,
            'name' => $this->name ?? null,
            'password' => $this->password ?? null,
            'extra' => json_encode($this->extra ?? []),
        ];
        return $result;
    }

    function newTokenData() {
        $result = [
            'user_id' => $this->id,
            'client_id' => 1,
            'access_token' => $this->getToken(),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')), // Assuming token expires in 1 hour
        ];
        return $result;
    }
    function createUser() {
        $data = $this->newData();
        $id = $this->insert('user', $data);
        if (!$id) {
            throw new \Exception("Failed to create user");
        }
        $this->id = $id; // Set the user ID
        return $id; // Return the new user ID
    }

    function updateToken() {
        $this->insert('access_token', $this->newTokenData());
    }
    function findAppByKey($app_key) {
        $SQL = "SELECT * FROM app WHERE app_key = ?";
        $stmt = $this->getStmt($SQL, [$app_key]);
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
