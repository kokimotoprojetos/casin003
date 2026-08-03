<?php
/**
 * CSRF protection class.
 *
 * Uses PHP sessions for token storage. Tokens are generated with
 * cryptographically secure random bytes and compared with hash_equals
 * (timing-safe). verifyRequest() returns bool and logs failures instead
 * of die()ing, so callers can decide how to react.
 */
class CSRF_Protect
{
	/** @var string */
	private $namespace;

	public function __construct($namespace = '_csrf')
	{
		$this->namespace = $namespace;

		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}

		$this->setToken();
	}

	/**
	 * @return string
	 */
	public function getToken()
	{
		return $this->readTokenFromStorage();
	}

	/**
	 * Timing-safe token comparison.
	 *
	 * @param string $userToken
	 * @return bool
	 */
	public function isTokenValid($userToken)
	{
		$stored = $this->readTokenFromStorage();
		if ($stored === '' || !is_string($userToken) || $userToken === '') {
			return false;
		}
		return hash_equals($stored, $userToken);
	}

	/**
	 * Echoes the HTML hidden input with the token. Value is HTML-escaped.
	 */
	public function echoInputField()
	{
		$token = $this->getToken();
		$ns = htmlspecialchars($this->namespace, ENT_QUOTES, 'UTF-8');
		$val = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
		echo "<input type=\"hidden\" name=\"{$ns}\" value=\"{$val}\" />";
	}

	/**
	 * Returns the HTML hidden input as a string ( handy for templates ).
	 *
	 * @return string
	 */
	public function renderInputField()
	{
		$token = $this->getToken();
		$ns = htmlspecialchars($this->namespace, ENT_QUOTES, 'UTF-8');
		$val = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
		return "<input type=\"hidden\" name=\"{$ns}\" value=\"{$val}\" />";
	}

	/**
	 * Verify the current request ( POST only ). Returns true if the request
	 * is safe to proceed: either it is not a POST, or the token matches.
	 * On failure, logs a warning with the calling script and returns false.
	 *
	 * @return bool
	 */
	public function verifyRequest()
	{
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			return true;
		}
		$userToken = isset($_POST[$this->namespace]) ? $_POST[$this->namespace] : '';
		if ($this->isTokenValid($userToken)) {
			return true;
		}
		$script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
		error_log('CSRF validation failed for ' . $script . ' from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
		return false;
	}

	/**
	 * Generate a new token if none exists.
	 */
	private function setToken()
	{
		$storedToken = $this->readTokenFromStorage();
		if ($storedToken === '') {
			$token = $this->generateToken();
			$this->writeTokenToStorage($token);
		}
	}

	/**
	 * @return string 64-char hex token
	 */
	private function generateToken()
	{
		$bytes = function_exists('random_bytes')
			? random_bytes(32)
			: (function_exists('openssl_random_pseudo_bytes') ? openssl_random_pseudo_bytes(32) : md5(uniqid((string)mt_rand(), true)));
		return bin2hex($bytes);
	}

	/**
	 * @return string
	 */
	private function readTokenFromStorage()
	{
		return isset($_SESSION[$this->namespace]) ? (string)$_SESSION[$this->namespace] : '';
	}

	/**
	 * @param string $token
	 */
	private function writeTokenToStorage($token)
	{
		$_SESSION[$this->namespace] = $token;
	}
}