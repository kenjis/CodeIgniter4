<?php

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace CodeIgniter\Database\Live;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

/**
 * @group DatabaseLive
 *
 * @internal
 */
final class DbErrorTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    private $forge;

    protected function setUp(): void
    {
        parent::setUp();

        $this->forge = Database::forge($this->DBGroup);
    }

    protected function tearDown(): void
    {
        $this->setPrivateProperty($this->db, 'DBDebug', true);

        $this->forge->dropTable('auth_identities', true);
        $this->forge->dropTable('auth_users', true);

        parent::tearDown();
    }

    public function testForeignKeyConstraintFailsDBDebugTrue()
    {
        $this->createTableAuthUsers();
        $this->createTableAuthIdentities();

        $this->setPrivateProperty($this->db, 'DBDebug', true);

        $prefix = $this->db->getPrefix();

        $sql = "INSERT INTO {$prefix}auth_identities"
            . ' (user_id, type, secret, created_at)'
            . " VALUES (2, 'email_2fa', '479123', '2022-05-18 23:01:46')";
        $result = $this->db->simpleQuery($sql);

        $this->assertFalse($result);
    }

    public function testForeignKeyConstraintFailsDBDebugFalse()
    {
        $this->createTableAuthUsers();
        $this->createTableAuthIdentities();

        $this->setPrivateProperty($this->db, 'DBDebug', false);

        $prefix = $this->db->getPrefix();

        $sql = "INSERT INTO {$prefix}auth_identities"
            . ' (user_id, type, secret, created_at)'
            . " VALUES (2, 'email_2fa', '479123', '2022-05-18 23:01:46')";
        $result = $this->db->simpleQuery($sql);

        $this->assertFalse($result);
    }

    private function createTableAuthUsers()
    {
        $this->forge->dropTable('auth_users', true);

        $this->forge->addField([
            'id' => [
                'type'           => 'int',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'username' => [
                'type'       => 'varchar',
                'constraint' => 30,
                'null'       => true,
            ],
            'active' => [
                'type'       => 'tinyint',
                'constraint' => 1,
                'null'       => 0,
                'default'    => 0,
            ],
            'last_active' => [
                'type' => 'datetime',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'datetime',
                'null' => true,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('username');
        $this->forge->createTable('auth_users', true);
    }

    private function createTableAuthIdentities()
    {
        $this->forge->dropTable('auth_identities', true);

        $this->forge->addField([
            'id'           => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'      => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'type'         => ['type' => 'varchar', 'constraint' => 255],
            'secret'       => ['type' => 'varchar', 'constraint' => 255],
            'secret2'      => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'expires'      => ['type' => 'datetime', 'null' => true],
            'last_used_at' => ['type' => 'datetime', 'null' => true],
            'created_at'   => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['type', 'secret']);
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('user_id', 'auth_users', 'id', '', 'CASCADE');
        $this->forge->createTable('auth_identities', true);
    }
}
