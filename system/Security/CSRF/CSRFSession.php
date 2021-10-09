<?php

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace CodeIgniter\Security\CSRF;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Session\Session;

use function log_message;

/**
 * Provides methods that help protect your site against
 * Cross-Site Request Forgery attacks with Session.
 */
class CSRFSession
{
    /**
     * CSRF Hash
     *
     * Random hash for Cross Site Request Forgery protection.
     *
     * @var string|null
     */
    protected $hash;

    /**
     * CSRF Token Name
     *
     * Token name for Cross Site Request Forgery protection.
     *
     * @var string
     */
    protected $tokenName = 'csrf_token_name';

    /**
     * CSRF Header Name
     *
     * Header name for Cross Site Request Forgery protection.
     *
     * @var string
     */
    protected $headerName = 'X-CSRF-TOKEN';

    /**
     * CSRF Regenerate
     *
     * Regenerate CSRF Token on every request.
     *
     * @var bool
     */
    protected $regenerate = true;

    /**
     * CSRF Redirect
     *
     * Redirect to previous page with error on failure.
     *
     * @var bool
     */
    protected $redirect = true;

    /**
     * @var Session
     */
    private $session;

    public function __construct(CSRFConfig $config, ?Session $session = null)
    {
        $this->session = $session ?? service('session');

        // Store the necessary configurations
        $this->tokenName  = $config->tokenName;
        $this->headerName = $config->headerName;
        $this->regenerate = $config->regenerate;
        $this->redirect   = $config->redirect;

        $this->generateHash();
    }

    /**
     * CSRF Verify
     *
     * @throws SecurityException
     */
    public function verify(RequestInterface $request): bool
    {
        [$token, $json] = $this->getToken($request);

        // Does the token exist and match?
        if (! isset($token) || ! hash_equals($token, $this->hash)) {
            throw SecurityException::forDisallowedAction();
        }

        if (isset($_POST[$this->tokenName])) {
            // We kill this since we're done and we don't want to pollute the POST array.
            unset($_POST[$this->tokenName]);
            $request->setGlobal('post', $_POST);
        } elseif (isset($json->{$this->tokenName})) {
            // We kill this since we're done and we don't want to pollute the JSON data.
            unset($json->{$this->tokenName});
            $request->setBody(json_encode($json));
        }

        if ($this->regenerate) {
            $this->hash = null;
            $this->session->set($this->tokenName, null);
        }

        log_message('info', 'CSRF token verified.');

        return true;
    }

    protected function getToken(RequestInterface $request): array
    {
        $json = null;

        // Does the token exist in POST, HEADER or optionally php:://input - json data.
        if ($request->hasHeader($this->headerName) && ! empty($request->header($this->headerName)->getValue())) {
            $tokenFromHeader = $request->header($this->headerName)->getValue();
        } else {
            $json = json_decode($request->getBody());

            if (! empty($request->getBody()) && ! empty($json) && json_last_error() === JSON_ERROR_NONE) {
                $tokenFromJson = $json->{$this->tokenName} ?? null;
            }
        }

        $token = $_POST[$this->tokenName] ?? $tokenFromHeader ?? $tokenFromJson ?? null;

        return [$token, $json];
    }

    /**
     * Returns the CSRF Hash.
     */
    public function getHash(): ?string
    {
        return $this->hash;
    }

    /**
     * Returns the CSRF Token Name.
     */
    public function getTokenName(): string
    {
        return $this->tokenName;
    }

    /**
     * Returns the CSRF Header Name.
     */
    public function getHeaderName(): string
    {
        return $this->headerName;
    }

    /**
     * Check if request should be redirect on failure.
     */
    public function shouldRedirect(): bool
    {
        return $this->redirect;
    }

    /**
     * Generates the CSRF Hash.
     */
    protected function generateHash(): string
    {
        if ($this->session->get($this->tokenName) !== null) {
            return $this->hash = $this->session->get($this->tokenName);
        }

        $this->hash = bin2hex(random_bytes(16));
        $this->session->set($this->tokenName, $this->hash);

        return $this->hash;
    }
}
