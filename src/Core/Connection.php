<?php

namespace Bluerock\Sellsy\Core;

use Bluerock\Sellsy\Core\Config;
use Bluerock\Sellsy\Core\Request;

/**
 * The single connection instance, issuing prepared & authenticated requests.
 *
 * @package bluerock/sellsy-client
 * @author Thomas <thomas@bluerocktel.com>
 * @version 1.1
 * @access public
 */
class Connection
{
    /**
     * The singleton instance.
     *
     * @var Connection
     */
    protected static $_instance = null;

    /**
     * The config instance.
     *
     * @var Config
     */
    protected $config = null;

    /**
     * Get the instance.
     *
     * @return Connection
     */
    public static function getInstance()
    {
        if (!static::$_instance) {
            static::$_instance = new static;
        }

        return static::$_instance;
    }

    /**
     * The connection constructor.
     */
    private function __construct()
    {
        $this->config = Config::getInstance();
    }
    
    /**
     * Singletons should not be cloneable.
     */
    protected function __clone()
    {
    }
    
    /**
     * Singletons should not be restorable from strings.
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    /**
     * Instanciate a new Request instance,
     * with Auth token and fully qualified Endpoint.
     * 
     * @param string $endpoint The unqualified endpoint to call.
     * @return \Bluerock\Sellsy\Core\Request
     */
    public function request(string $endpoint)
    {
        $endpoint = sprintf('%s/%s', trim($this->config->get('url'), '/'), ltrim($endpoint, '/'));

        return Request::make($endpoint)
                    ->withToken($this->getToken())
                    ->withOptions([])
                    ->withHeaders([
                        'Accept' => 'application/json',
                    ]);
    }

    /**
     * Check if we have a valid and unexpired auth token to use.
     *
     * @return bool
     */
    protected function hasValidToken()
    {
        return $this->config->get('authentication.token') && time() < $this->config->get('authentication.expires_at');
    }
    
    /**
     * Get the Sellsy Auth token.
     * If no token exists, or if the token is expired, get a new one.
     *
     * @return string
     */
    protected function getToken()
    {
        if ($this->hasValidToken()) {
            return $this->config->get('authentication.token');
        }

        $auth = new Authentication();

        $token = $auth->getToken(
            $this->config->get('client_id'),
            $this->config->get('client_secret'),
        );

        $this->config->set('authentication.token', $token['access_token']);
        $this->config->set('authentication.expires_at', time() + $token['expires_in']);

        return $token['access_token'];
    }
}
