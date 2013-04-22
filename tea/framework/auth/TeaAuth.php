<?php
/**
 * TeaAuth class, manage user authentication
 * @author Gustavo Seip <glseip@gmail.com>
 * @license http://www.Tea.com/license
 * @version $Id: TeaAuth.php 2009-10-06 15:10:12
 * @package Tea.auth
 * @since 1.3
 *
 */

class TeaAuth {
    /**
     * HIGH security level
     * @var integer
     */
    const LEVEL_HIGH = 1;
    /**
     * MEDIUM security level
     * @var integer
     */
    const LEVEL_MEDIUM = 2;
    /**
     * LOW security level
     * @var integer
     */
    const LEVEL_LOW = 3;
    /**
     * Discarded form indicator
     * @var integer
     */
    const FORM_DISCARDED = 1;
    /**
     * Timeout form indicator
     * @var integer
     */
    const FORM_TIMEOUT = 2;
    /**
     * TeaSession instance
     * @var TeaSession
     */
    protected $appSession;
    /**
     * Application name
     * @var string
     */
    protected $appName;
    /**
     * A random string for hashing
     * @var string
     */
    protected $salt;
    /**
     * maximum time for downtime
     * @var integer
     */
    protected $authSessionExpire = 60; //time in seconds
    /**
     * Security level
     * @var integer
     */
    protected $securityLevel;
    /**
     * Maximun time for form timeout
     * @var integer
     */
    protected $authPostWait = 60; //time frame - in seconds
    /**
     * Minimun time for form timeout
     * @var integer
     */
    protected $authPostExpire = 20; //time frame - in seconds
    /**
     * Indicator for valid authetication
     * @var boolean
     */
    protected $isValid = false;

    /**
     * Username from the session
     * @var string
     */
    public $username;

    /**
     * Group name from the session
     * @var string
     */
    public $group;

    /**
     * Constructor - returns an instance object of TeaAuth
     */
    public function __construct($appName) {
        $this->setApplicationName($appName);
    }

    /**
     * Start auth component
     */
    public function start() {
        $this->appSession = Tea::session($this->getApplicationName());
        $this->validate();
    }

    /**
     * Finalize autentication
     */
    public function finalize() {
        if (!$this->appSession->isDestroyed())
            $this->appSession->destroy();
    }

    /**
     * Set auth data for user session
     * @param string User name
     * @param mixed User group
     */
    public function setData($username, $group=FALSE) {
        $this->appSession->AuthData = array();
        $this->username = $this->appSession->AuthData['_username'] = $username;
        $this->group = $this->appSession->AuthData['_group'] = $group;
        $this->appSession->AuthData['_securityLevel'] = $this->getSecurityLevel();
        $this->appSession->AuthData['_time'] = time();
        switch ($this->securityLevel) {
            case self::LEVEL_HIGH:
                $this->appSession->AuthData['_initialized'] = true;
                $this->appSession->AuthData['_fingerprint'] = md5($_SERVER['HTTP_USER_AGENT'].$this->getSalt());
                session_regenerate_id();
                $this->appSession->AuthData['_id'] = md5($this->appSession->getId());
                $this->appSession->AuthData['_authSessionExpire'] = $this->getSessionExpire() * 15;
                $this->appSession->AuthData['_authPostWait'] = $this->getPostWait() * 11; //~25% of authSessionExpire
                $this->appSession->AuthData['_authPostExpire'] = $this->getPostExpire();
                break;
            case self::LEVEL_MEDIUM:
                $this->appSession->AuthData['_initialized'] = true;
                $this->appSession->AuthData['_fingerprint'] = md5($_SERVER['HTTP_USER_AGENT'].$this->getSalt());
                $this->appSession->AuthData['_authSessionExpire'] = $this->getSessionExpire() * 120;
                $this->appSession->AuthData['_authPostWait'] = $this->getPostWait() * 60; //~50% of authSessionExpire
                $this->appSession->AuthData['_authPostExpire'] = $this->getPostExpire();
                break;
            case self::LEVEL_LOW:
                $this->appSession->AuthData['_initialized'] = true;
                $this->appSession->AuthData['_authSessionExpire'] = $this->getSessionExpire() * 360;
                $this->appSession->AuthData['_authPostWait'] = $this->getPostWait() * 90; //~75% of authSessionExpire
                $this->appSession->AuthData['_authPostExpire'] = $this->getPostExpire();
                break;
            default:
                break;
        }
    }

    /**
     * Validate authentication data
     * @see http://phpsec.org/projects/guide/4.html
     * @see http://www.serversidemagazine.com/php/session-hijacking
     * @return <Boolean>
     */
    public function validate() {
        if (isset ($this->appSession) && $this->appSession->AuthData['_initialized'] !== null) {
            if (    ($this->_securityLevel==self::LEVEL_LOW && ($this->_initialized || isset ($this->appSession->AuthData['_username']) || ((time()-$this->appSession->AuthData['_time']) <= $this->_authSessionExpire))) || //LEVEL_LOW
                    (($this->_securityLevel==self::LEVEL_MEDIUM || $this->_securityLevel==self::LEVEL_HIGH) //LEVEL_MEDIUM
                         && $this->_fingerprint == md5($_SERVER['HTTP_USER_AGENT'].$this->getSalt())) ||
                    ($this->_securityLevel==self::LEVEL_HIGH && $this->_id==md5($this->appSession->getId())) ) { //LEVEL_HIGH
                $this->_time = time();
                $this->isValid = true;
                $this->username = $this->appSession->AuthData['_username'];
                $this->group = $this->appSession->AuthData['_group'];
            }
        } else
            $this->isValid = false;
    }

    /**
     * Get token for security purpose (secure forms, etc)
     * @see http://www.serversidemagazine.com/php/php-security-measures-against-csrf-attacks
     * @see http://www.serversidemagazine.com/php/session-hijacking
     * @return mixed
     */
    public function securityToken() {
        if ($this->isValid()) {
            $this->appSession->AuthData['_formToken'] = uniqid(rand(), true);
            return $this->_formToken;
        }
        return false;
    }

    /**
     * Validate form with security token
     * @see http://www.serversidemagazine.com/php/php-security-measures-against-csrf-attacks
     * @return mixed
     */
    public function validateForm($receivedToken) {
        if ($this->isValid && isset($receivedToken)) {
            if ($this->_formToken!=$receivedToken)
                return false;
            $time = time() - $this->_time;
            if ($time < $this->_authPostExpire)
                return self::$FORM_DISCARDED;
            elseif ($time > $this->_authPostWait)
                return self::$FORM_TIMEOUT;
            return true;
        }
        return false;
    }

    /////////// SETTERs & GETTERs ////////////
    public function setApplicationName($appName) {
        if (!isset ($appName))
            throw new TeaAuthException("Application name cannot be empty");
        $this->appName = $appName;
    }
    public function getApplicationName() {
        if (!isset ($this->appName))
            throw new TeaAuthException("Application name not defined");
        return $this->appName;
    }
    public function setSalt($salt) {
        if (!isset ($salt))
            throw new TeaAuthException("Salt cannot be empty");
        $this->salt = $salt;
    }
    public function getSalt() {
        if (!isset ($this->salt))
            throw new TeaAuthException("Salt not defined");
        return $this->salt;
    }
    public function setSecurityLevel($securityLevel) {
        if (!isset ($securityLevel))
            throw new TeaAuthException("Security level cannot be empty");
        $this->securityLevel = $securityLevel;
    }
    public function getSecurityLevel() {
        if (!isset ($this->securityLevel))
            throw new TeaAuthException("Security level not defined");
        return $this->securityLevel;
    }
    public function setSessionExpire($sessionExpire) {
        if (!isset ($sessionExpire))
            throw new TeaAuthException("Session expire cannot be empty");
        $this->authSessionExpire = $sessionExpire;
    }
    public function getSessionExpire() {
        if (!isset ($this->authSessionExpire))
            throw new TeaAuthException("Session expire not defined");
        return $this->authSessionExpire;
    }
    public function setPostExpire($postExpire) {
        if (!isset ($postExpire))
            throw new TeaAuthException("Post expire cannot be empty");
        $this->authPostExpire = $postExpire;
    }
    public function getPostExpire() {
        if (!isset ($this->authPostExpire))
            throw new TeaAuthException("Post expire not defined");
        return $this->authPostExpire;
    }
    public function setPostWait($postWait) {
        if (!isset ($postWait))
            throw new TeaAuthException("Post wait cannot be empty");
        $this->authPostWait = $postWait;
    }
    public function getPostWait() {
        if (!isset ($this->authPostWait))
            throw new TeaAuthException("Post wait not defined");
        return $this->authPostWait;
    }
    public function isValid() {
        return $this->isValid;
    }

    ////////////////// Magic ////////////////////
    public function  __set($name,  $value) {
        if (!isset ($this->appSession->AuthData))
            throw new TeaAuthException("authentication data not initialized");
        return $this->appSession->AuthData[$name] = $value;
    }
    public function  __get($name) {
        if (!isset ($this->appSession->AuthData))
            throw new TeaAuthException("authentication data not initialized");
        return $this->appSession->AuthData[$name];
    }
}

class TeaAuthException extends Exception {
    
}
