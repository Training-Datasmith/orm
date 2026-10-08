<?php

class Ormtest_Audit_Item extends Orm\Model
{
	protected static $_table_name = 'audit_items';
	protected static $_properties = array('id', 'name');
	protected static $_observers = array('Orm\\Observer_Audit');
}

class ObserverAuditTest extends OrmTestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		\DB::query('CREATE TABLE audit_items (id INTEGER PRIMARY KEY, name TEXT)')->execute();
		\DB::query('CREATE TABLE users (
			id INTEGER PRIMARY KEY,
			username TEXT,
			password TEXT,
			group_id INTEGER,
			email TEXT,
			last_login INTEGER,
			login_hash TEXT,
			profile_fields TEXT
		)')->execute();
		\DB::query('CREATE TABLE orm_audit (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			key TEXT,
			user_id INTEGER,
			ip TEXT,
			first INTEGER,
			last INTEGER
		)')->execute();
		\DB::query('CREATE TABLE orm_audit_diff (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			audit_id INTEGER,
			logged INTEGER,
			type TEXT,
			model TEXT,
			diff TEXT
		)')->execute();
	}

	protected function tearDown(): void
	{
		\Config::set('orm.audit.enabled', false);
		\Config::set('orm.audit.table', null);
		parent::tearDown();
	}

	public function testAuditDisabledWritesNothing(): void
	{
		\Config::set('orm.audit.enabled', false);
		Ormtest_Audit_Item::forge(array('name' => 'a'))->save();
		$this->assertSame(0, (int) \DB::select(\DB::expr('COUNT(*) as c'))->from('orm_audit_diff')->execute()->get('c'));
	}

	public function testAuditWithoutSessionWritesNothing(): void
	{
		\Config::set('orm.audit.enabled', true);
		\Config::set('orm.audit.table', 'orm_audit');
		Ormtest_Audit_Item::forge(array('name' => 'b'))->save();
		$this->assertSame(0, (int) \DB::select(\DB::expr('COUNT(*) as c'))->from('orm_audit_diff')->execute()->get('c'));
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testAuditEnabledWithSessionAndUser(): void
	{
		\Config::set('orm.audit.enabled', true);
		\Config::set('orm.audit.table', 'orm_audit');
		\DB::insert('users')->set(array(
			'id' => 7,
			'username' => 'audituser',
			'password' => 'x',
			'group_id' => 1,
			'email' => 'a@example.com',
			'last_login' => 0,
			'login_hash' => 'hash',
			'profile_fields' => 'a:0:{}',
		))->execute();
		\Session::instance()->destroy();
		\Session::start();
		\Auth::instance()->force_login(7);
		Ormtest_Audit_Item::forge(array('name' => 'logged'))->save();
		$count = (int) \DB::select(\DB::expr('COUNT(*) as c'))->from('orm_audit_diff')->execute()->get('c');
		$this->assertGreaterThan(0, $count);
	}
}
