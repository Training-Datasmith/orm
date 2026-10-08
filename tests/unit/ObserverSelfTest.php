<?php

class Ormtest_Self_Item extends Orm\Model
{
	protected static $_table_name = 'self_items';
	protected static $_properties = array('id', 'name');
	protected static $_observers = array('Orm\\Observer_Self');

	public $before_save_called = false;

	public function _event_before_save()
	{
		$this->before_save_called = true;
	}
}

class ObserverSelfTest extends OrmTestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		\DB::query('CREATE TABLE self_items (id INTEGER PRIMARY KEY, name TEXT)')->execute();
	}

	public function testSelfObserverDispatchesModelEvent(): void
	{
		$m = Ormtest_Self_Item::forge(array('name' => 'x'));
		$m->save();
		$this->assertTrue($m->before_save_called);
	}
}
