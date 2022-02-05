<?php

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace CodeIgniter\Mailer;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class EmailTest extends CIUnitTestCase
{
    public function testConstructorUsesData()
    {
        $address = 'leia@alderaan.org';
        $email   = new Email([
            'from' => $address,
        ]);

        $this->assertSame($address, (string) $email->getFrom());
    }
}
