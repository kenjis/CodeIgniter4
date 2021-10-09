<?php

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace CodeIgniter\Securit\CSRF;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Security\CSRF\CSRFConfig;
use CodeIgniter\Security\CSRF\CSRFSession;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Session\Handlers\FileHandler;
use CodeIgniter\Session\Session;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\Mock\MockAppConfig;
use CodeIgniter\Test\Mock\MockSession;
use CodeIgniter\Test\TestLogger;
use Config\Logger as LoggerConfig;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @backupGlobals enabled
 *
 * @internal
 */
final class CSRFSessionTest extends CIUnitTestCase
{
    protected function createSession(): Session
    {
        $appConfig = new MockAppConfig();

        $defaults = [
            'sessionDriver'            => 'CodeIgniter\Session\Handlers\FileHandler',
            'sessionCookieName'        => 'ci_session',
            'sessionExpiration'        => 7200,
            'sessionSavePath'          => null,
            'sessionMatchIP'           => false,
            'sessionTimeToUpdate'      => 300,
            'sessionRegenerateDestroy' => false,
            'cookieDomain'             => '',
            'cookiePrefix'             => '',
            'cookiePath'               => '/',
            'cookieSecure'             => false,
            'cookieSameSite'           => 'Lax',
        ];

        foreach ($defaults as $key => $config) {
            $appConfig->{$key} = $config;
        }

        $session = new MockSession(new FileHandler($appConfig, '127.0.0.1'), $appConfig);
        $session->setLogger(new TestLogger(new LoggerConfig()));

        return $session;
    }

    public function testVerifyFailsWithWrongCsrfToken()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_test_name']   = '8b9218a55906f9dcc1dc263dce7f005a';

        $configSecurity = config('Security');
        $csrfConfig     = new CSRFConfig($configSecurity);
        $session        = $this->createSession();
        $csrfSession    = new CSRFSession($csrfConfig, $session);

        $request = new IncomingRequest(new MockAppConfig(), new URI('http://badurl.com'), null, new UserAgent());

        $this->expectException(SecurityException::class);
        $csrfSession->verify($request);
    }

    public function testVerifyPassesWithCorrectCsrfToken()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $configSecurity = config('Security');
        $csrfConfig     = new CSRFConfig($configSecurity);
        $session        = $this->createSession();
        $csrfSession    = new CSRFSession($csrfConfig, $session);

        $token                   = $csrfSession->getHash();
        $_POST['csrf_test_name'] = $token;

        $request = new IncomingRequest(new MockAppConfig(), new URI('http://badurl.com'), null, new UserAgent());

        $this->assertTrue($csrfSession->verify($request));
    }
}
