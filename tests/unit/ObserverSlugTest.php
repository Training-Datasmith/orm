<?php

class Ormtest_Slug_Basic extends Orm\Model
{
	protected static $_table_name = 'slug_basic';
	protected static $_properties = array('id', 'title', 'slug');
	protected static $_observers = array(
		'Orm\\Observer_Slug' => array(
			'events' => array('before_insert', 'before_update'),
			'property' => 'slug',
			'source' => 'title',
		),
	);
}

class Ormtest_Slug_Underscore extends Orm\Model
{
	protected static $_table_name = 'slug_underscore';
	protected static $_properties = array('id', 'title', 'slug');
	protected static $_observers = array(
		'Orm\\Observer_Slug' => array(
			'events' => array('before_insert'),
			'property' => 'slug',
			'source' => 'title',
			'separator' => '_',
			'unique' => true,
		),
	);
}

class Ormtest_Slug_Dot extends Orm\Model
{
	protected static $_table_name = 'slug_dot';
	protected static $_properties = array('id', 'title', 'slug');
	protected static $_observers = array(
		'Orm\\Observer_Slug' => array(
			'events' => array('before_insert'),
			'property' => 'slug',
			'source' => 'title',
			'separator' => '.',
			'unique' => true,
		),
	);
}

class Ormtest_Slug_Manual extends Orm\Model
{
	protected static $_table_name = 'slug_manual';
	protected static $_properties = array('id', 'title', 'slug');
	protected static $_observers = array(
		'Orm\\Observer_Slug' => array(
			'events' => array('before_insert'),
			'property' => 'slug',
			'source' => 'title',
			'overwrite' => false,
		),
	);
}

class ObserverSlugTest extends OrmTestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		\DB::query('CREATE TABLE slug_basic (id INTEGER PRIMARY KEY, title TEXT, slug TEXT)')->execute();
		\DB::query('CREATE TABLE slug_underscore (id INTEGER PRIMARY KEY, title TEXT, slug TEXT)')->execute();
		\DB::query('CREATE TABLE slug_dot (id INTEGER PRIMARY KEY, title TEXT, slug TEXT)')->execute();
		\DB::query('CREATE TABLE slug_manual (id INTEGER PRIMARY KEY, title TEXT, slug TEXT)')->execute();
	}

	public function testBasicSlug(): void
	{
		$m = $this->saveModel(Ormtest_Slug_Basic::forge(array('title' => 'Hello World')));
		$this->assertSame('hello-world', $m->slug);
	}

	public function testUniqueSuffix(): void
	{
		\DB::insert('slug_underscore')->set(array('title' => 'Hello World', 'slug' => 'hello_world'))->execute();
		$second = $this->saveModel(Ormtest_Slug_Underscore::forge(array('title' => 'Hello World')));
		$this->assertSame('hello_world_1', $second->slug);
	}

	public function testManualSlugNotOverwritten(): void
	{
		$m = $this->saveModel(Ormtest_Slug_Manual::forge(array('title' => 'Auto', 'slug' => 'custom-slug')));
		$this->assertSame('custom-slug', $m->slug);
	}

	public function testSeparatorUnderscoreAndDot(): void
	{
		\DB::insert('slug_underscore')->set(array('title' => 'A B', 'slug' => 'a_b'))->execute();
		$u = $this->saveModel(Ormtest_Slug_Underscore::forge(array('title' => 'A B')));
		$this->assertSame('a_b_1', $u->slug);

		\DB::insert('slug_dot')->set(array('title' => 'A B', 'slug' => 'a.b'))->execute();
		$d = $this->saveModel(Ormtest_Slug_Dot::forge(array('title' => 'A B')));
		$this->assertSame('a.b.1', $d->slug);
	}

	public function testSlugOnUpdate(): void
	{
		$m = $this->saveModel(Ormtest_Slug_Basic::forge(array('title' => 'First')));
		$m->unfreeze();
		$m->title = 'Second Title';
		$m->save();
		$this->assertSame('second-title', $m->slug);
	}
}
