<?php

class Ormtest_Query_Item extends Orm\Model
{
	protected static $_table_name = 'query_items';
	protected static $_properties = array(
		'id',
		'category',
		'amount',
		'label',
	);
	protected static $_views = array(
		'by_cat' => array(
			'columns' => array('category', 'amount'),
		),
	);
}

class QueryTest extends OrmTestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		\DB::query('CREATE TABLE query_items (id INTEGER PRIMARY KEY, category TEXT, amount INTEGER, label TEXT)')->execute();
		$this->saveModel(Ormtest_Query_Item::forge(array('category' => 'a', 'amount' => 10, 'label' => 'one')));
		$this->saveModel(Ormtest_Query_Item::forge(array('category' => 'a', 'amount' => 20, 'label' => 'two')));
		$this->saveModel(Ormtest_Query_Item::forge(array('category' => 'b', 'amount' => 5, 'label' => 'three')));
	}

	public function testWhereOrGroupLimit(): void
	{
		$rows = Ormtest_Query_Item::query()
			->where('category', 'a')
			->limit(2)
			->get();
		$this->assertCount(2, $rows);
	}

	public function testCountMaxMin(): void
	{
		$q = Ormtest_Query_Item::query()->where('category', '=', 'a');
		$this->assertSame(2, $q->count());
		$this->assertSame(20, (int) $q->max('amount'));
		$this->assertSame(10, (int) $q->min('amount'));
	}

	public function testGetOneUpdateDelete(): void
	{
		$one = Ormtest_Query_Item::query()->where('label', 'one')->get_one();
		$this->assertSame('one', $one->label);
		Ormtest_Query_Item::query()->where('label', 'three')->set(array('label' => 'updated'))->update();
		$this->assertSame('updated', Ormtest_Query_Item::find('first', array('where' => array('category', 'b')))->label);
		Ormtest_Query_Item::query()->where('label', 'updated')->delete();
		$this->assertNull(Ormtest_Query_Item::find('first', array('where' => array('label', 'updated'))));
	}

	public function testFindHelpersAndCache(): void
	{
		$this->assertCount(3, Ormtest_Query_Item::query()->get());
		\Orm\Query::$caching = true;
		$q1 = Ormtest_Query_Item::query();
		$q2 = Ormtest_Query_Item::query();
		$this->assertSame($q1, $q2);
		\Orm\Query::$caching = null;
	}

	public function testViewQuery(): void
	{
		$views = Ormtest_Query_Item::views();
		$this->assertArrayHasKey('by_cat', $views);
		Ormtest_Query_Item::query()->use_view('by_cat');
		$this->assertSame('by_cat', $views['by_cat']['view']);
	}
}
