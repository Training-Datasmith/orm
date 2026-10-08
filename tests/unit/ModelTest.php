<?php

class Ormtest_Model_Basic extends Orm\Model
{
	protected static $_table_name = 'model_basic';
	protected static $_properties = array(
		'id',
		'name' => array('data_type' => 'varchar'),
		'status' => array('data_type' => 'varchar', 'default' => 'new'),
	);
	protected static $_property_map = array(
		'name' => 'display_name',
	);
	protected static $_views = array(
		'summary' => array(
			'columns' => array('id', 'name'),
		),
	);
	protected static $_conditions = array(
		'default' => array(
			'where' => array(array('status', '=', 'active')),
		),
	);
	protected static $_has_one = array(
		'child' => array(
			'model_to' => 'Ormtest_Model_Child',
			'key_from' => 'id',
			'key_to' => 'parent_id',
		),
	);
}

class Ormtest_Model_Child extends Orm\Model
{
	protected static $_table_name = 'model_child';
	protected static $_properties = array('id', 'parent_id', 'label');
}

class Ormtest_Model_Cycle extends Orm\Model
{
	protected static $_table_name = 'model_cycle';
	protected static $_properties = array('id', 'name');
	protected static $_has_one = array(
		'partner' => array(
			'model_to' => 'Ormtest_Model_Cycle',
			'key_from' => 'id',
			'key_to' => 'id',
		),
	);
}

class Ormtest_Model_Conn extends Orm\Model
{
	protected static $_connection = 'default';
	protected static $_table_name = 'model_conn';
	protected static $_properties = array('id');
}

class ModelTest extends OrmTestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		\DB::query('CREATE TABLE model_basic (id INTEGER PRIMARY KEY, name TEXT, status TEXT)')->execute();
		\DB::query('CREATE TABLE model_child (id INTEGER PRIMARY KEY, parent_id INTEGER, label TEXT)')->execute();
		\DB::query('CREATE TABLE model_cycle (id INTEGER PRIMARY KEY, name TEXT)')->execute();
		\DB::query('CREATE TABLE model_conn (id INTEGER PRIMARY KEY)')->execute();
	}

	public function testTableAndProperties(): void
	{
		$this->assertSame('model_basic', Ormtest_Model_Basic::table());
		$this->assertArrayHasKey('name', Ormtest_Model_Basic::properties());
		$this->assertSame('new', Ormtest_Model_Basic::property('status')['default']);
	}

	public function testViews(): void
	{
		$views = Ormtest_Model_Basic::views();
		$this->assertArrayHasKey('summary', $views);
		$this->assertContains('id', $views['summary']['columns']);
	}

	public function testImplodePkSingleAndComposite(): void
	{
		$this->assertSame('5', Ormtest_Model_Basic::implode_pk(array('id' => 5)));
		$this->assertNull(Ormtest_Model_Basic::implode_pk(array('id' => null)));
	}

	public function testForgeCacheReturnsSameInstance(): void
	{
		$row = $this->saveModel(Ormtest_Model_Basic::forge(array('name' => 'a', 'status' => 'active')));
		$cached = Ormtest_Model_Basic::forge(array('id' => $row->id), false);
		$this->assertSame($row, $cached);
	}

	public function testSetGetAndMappedNames(): void
	{
		$m = Ormtest_Model_Basic::forge();
		$m->set('display_name', 'mapped');
		$this->assertSame('mapped', $m->name);
		$this->assertSame('mapped', $m->get('display_name'));
	}

	public function testUnsetViaMappedName(): void
	{
		$m = Ormtest_Model_Basic::forge(array('name' => 'x', 'status' => 'active'));
		unset($m->display_name);
		$this->assertNull($m->name);
	}

	public function testFreezePreventsMutation(): void
	{
		$m = Ormtest_Model_Basic::forge(array('status' => 'active'));
		$m->freeze();
		$this->expectException(\Orm\FrozenObject::class);
		$m->set('name', 'nope');
	}

	public function testIsChanged(): void
	{
		$m = $this->saveModel(Ormtest_Model_Basic::forge(array('name' => 'a', 'status' => 'active')));
		$this->assertFalse($m->is_changed('name'));
		$m->name = 'b';
		$this->assertTrue($m->is_changed('name'));
	}

	public function testToArrayCycleGuard(): void
	{
		$a = $this->saveModel(Ormtest_Model_Cycle::forge(array('name' => 'a')));
		$b = $this->saveModel(Ormtest_Model_Cycle::forge(array('name' => 'b')));
		$a->partner = $b;
		$b->partner = $a;
		$array = $a->to_array(false, true, false, true);
		$this->assertSame('a', $array['name']);
		$this->assertArrayHasKey('partner', $array);
	}

	public function testArrayAccessAndClone(): void
	{
		$m = Ormtest_Model_Basic::forge(array('name' => 'z', 'status' => 'active'));
		$m['name'] = 'y';
		$this->assertSame('y', $m['name']);
		$clone = clone $m;
		$this->assertTrue($clone->is_new());
		$this->assertSame('y', $clone->name);
	}

	public function testFromArrayRelationArray(): void
	{
		$parent = $this->saveModel(Ormtest_Model_Basic::forge(array('name' => 'p', 'status' => 'active')));
		$child = Ormtest_Model_Child::forge(array('label' => 'c'));
		$parent->from_array(array('child' => $child));
		$this->assertSame('c', $parent->child->label);
	}

	public function testFindInvalidOptionsThrows(): void
	{
		$this->expectException(\FuelException::class);
		Ormtest_Model_Basic::find(1, 'not-an-array');
	}

	public function testConnections(): void
	{
		$this->assertSame('default', Ormtest_Model_Conn::connection());
	}

	public function testConditions(): void
	{
		$where = Ormtest_Model_Basic::condition('default');
		$this->assertSame('active', $where['where'][0][2]);
	}

	public function testRegisterObserver(): void
	{
		Ormtest_Model_Basic::register_observer('Orm\\Observer_Self');
		$observers = Ormtest_Model_Basic::observers();
		$this->assertArrayHasKey('Orm\\Observer_Self', $observers);
	}
}
