<?php
/**
 * Created by IntelliJ IDEA.
 * User: Nikolay Chervyakov 
 * Date: 11.08.2014
 * Time: 14:09
 */


namespace VulnModule\Csrf;


use App\Exception\HttpException; // kept for getToken() exception

/**
 * Class TokenStorage
 * @package VulnModule\Csrf
 */
class TokenStorage
{
    /**
     * The namespace used to store values in the session.
     * @var string
     */
    const SESSION_NAMESPACE = '_csrf';

    /**
     * @var bool
     */
    private $sessionStarted = false;

    /**
     * @var string
     */
    private $namespace;

    /**
     * Initializes the storage with a session namespace.
     *
     * @param string  $namespace The namespace under which the token is stored
     *                           in the session
     */
    public function __construct($namespace = self::SESSION_NAMESPACE)
    {
        $this->namespace = $namespace;
    }

    /**
     * @param $tokenId
     * @return string
     * @throws \App\Exception\HttpException
     */
    private function key($tokenId): string
    {
        return $this->namespace . '.' . $tokenId;
    }

    public function getToken($tokenId)
    {
        $value = session($this->key($tokenId));
        if ($value === null) {
            throw new HttpException('The CSRF token with ID '.$tokenId.' does not exist.', 400, null, 'Bad request');
        }
        return (string) $value;
    }

    public function setToken($tokenId, $token)
    {
        session([$this->key($tokenId) => (string) $token]);
    }

    public function hasToken($tokenId)
    {
        return session()->has($this->key($tokenId));
    }

    public function removeToken($tokenId)
    {
        $key   = $this->key($tokenId);
        $token = session($key);
        session()->forget($key);
        return $token !== null ? (string) $token : null;
    }
}