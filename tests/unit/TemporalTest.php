<?php

class Ormtest_Temporal_Item extends Orm\Model_Temporal
{
	protected static $_table_name = 'temporal_items';
	protected static $_properties = array(
		'id',
		'title',
		'temporal_start',
		'temporal_end',
	);
}

class TemporalTest extends OrmTestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		\DB::query('CREATE TABLE temporal_items (
			id INTEGER NOT NULL,
			title TEXT,
			temporal_start INTEGER NOT NULL,
			temporal_end INTEGER,
			PRIMARY KEY (id, temporal_start)
		)')->execute();
	}

	public function testInsertRevisionAndFindRevision(): void
	{
		$start = time();
		\DB::insert('temporal_items')->set(array(
			'id' => 1,
			'title' => 'v1',
			'temporal_start' => $start,
			'temporal_end' => 2147483647,
		))->execute();
		$this->assertSame('v1', Ormtest_Temporal_Item::find(1)->title);
		$this->assertGreaterThanOrEqual(1, count(Ormtest_Temporal_Item::find_revisions_between(1)));
	}

	public function testDeletePurgeAndPropertyRead(): void
	{
		$start = time();
		\DB::insert('temporal_items')->set(array(
			'id' => 1,
			'title' => 'live',
			'temporal_start' => $start,
			'temporal_end' => 2147483647,
		))->execute();
		$row = Ormtest_Temporal_Item::find(1);
		$this->assertSame('live', $row->title);
		$row->delete();
		$this->assertNull(Ormtest_Temporal_Item::find(1));
		$this->assertTrue($row->purge() >= 0);
		$this->assertNull(Ormtest_Temporal_Item::query()->where('id', 1)->get_one());
	}
}
