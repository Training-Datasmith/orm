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
			id INTEGER PRIMARY KEY,
			title TEXT,
			temporal_start INTEGER,
			temporal_end INTEGER
		)')->execute();
	}

	public function testInsertRevisionAndFindRevision(): void
	{
		$row = $this->saveModel(Ormtest_Temporal_Item::forge(array('title' => 'v1')));
		$row->title = 'v2';
		$this->assertTrue($row->save());
		$current = Ormtest_Temporal_Item::find($row->id);
		$this->assertSame('v2', $current->title);
		$this->assertGreaterThanOrEqual(1, count(Ormtest_Temporal_Item::find_revisions_between($row->id)));
	}

	public function testDeletePurgeAndPropertyRead(): void
	{
		$row = $this->saveModel(Ormtest_Temporal_Item::forge(array('title' => 'live')));
		$id = $row->id;
		$this->assertSame('live', Ormtest_Temporal_Item::find($id)->title);
		$row->delete();
		$this->assertNull(Ormtest_Temporal_Item::find($id));
		Ormtest_Temporal_Item::disable_filter();
		$archived = Ormtest_Temporal_Item::find($id);
		$this->assertNotNull($archived);
		$archived->purge();
		Ormtest_Temporal_Item::enable_filter();
	}
}
