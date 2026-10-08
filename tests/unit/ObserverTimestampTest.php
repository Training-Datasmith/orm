<?php

class Ormtest_Ts_Created extends Orm\Model
{
	protected static $_table_name = 'ts_created';
	protected static $_properties = array('id', 'created_at');
	protected static $_observers = array('Orm\\Observer_CreatedAt');
}

class Ormtest_Ts_Updated extends Orm\Model
{
	protected static $_table_name = 'ts_updated';
	protected static $_properties = array('id', 'title', 'updated_at');
	protected static $_observers = array('Orm\\Observer_UpdatedAt');
}

class ObserverTimestampTest extends OrmTestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		\DB::query('CREATE TABLE ts_created (id INTEGER PRIMARY KEY, created_at INTEGER)')->execute();
		\DB::query('CREATE TABLE ts_updated (id INTEGER PRIMARY KEY, title TEXT, updated_at INTEGER)')->execute();
	}

	public function testCreatedAtOnInsert(): void
	{
		$m = $this->saveModel(Ormtest_Ts_Created::forge());
		$this->assertTimeWindow($m->created_at);
	}

	public function testUpdatedAtOnSave(): void
	{
		$m = $this->saveModel(Ormtest_Ts_Updated::forge(array('title' => 'a')));
		$this->assertTimeWindow($m->updated_at);
		$before = $m->updated_at;
		sleep(1);
		$m->title = 'b';
		$m->save();
		$this->assertGreaterThanOrEqual($before, $m->updated_at);
	}
}
