<?php

class Ormtest_Typing_All extends Orm\Model
{
	protected static $_table_name = 'typing_all';
	protected static $_properties = array(
		'id' => array('data_type' => 'int'),
		'nullable' => array('data_type' => 'varchar', 'null' => true),
		'title' => array('data_type' => 'varchar'),
		'qty' => array('data_type' => 'int'),
		'price' => array('data_type' => 'float'),
		'amount' => array('data_type' => 'decimal:2'),
		'role' => array('data_type' => 'enum', 'options' => array('a', 'b')),
		'flags' => array('data_type' => 'set', 'options' => array('x', 'y')),
		'active' => array('data_type' => 'bool'),
		'meta' => array('data_type' => 'serialize'),
		'payload' => array('data_type' => 'json'),
		'seen_at' => array('data_type' => 'time', 'mysql_timestamp' => false),
		'secret' => array(
			'data_type' => 'encrypt',
			'encryption_key' => '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef',
		),
	);
	protected static $_observers = array('Orm\\Observer_Typing');
}

class ObserverTypingTest extends OrmTestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		\DB::query('CREATE TABLE typing_all (
			id INTEGER PRIMARY KEY,
			nullable TEXT,
			title TEXT,
			qty INTEGER,
			price REAL,
			amount TEXT,
			role TEXT,
			flags TEXT,
			active INTEGER,
			meta TEXT,
			payload TEXT,
			seen_at INTEGER,
			secret TEXT
		)')->execute();
	}

	public function testNullAndStringInt(): void
	{
		$m = Ormtest_Typing_All::forge(array(
			'nullable' => null,
			'title' => 123,
			'qty' => '4',
		));
		$m->save();
		$this->assertNull($m->nullable);
		$this->assertSame('123', $m->title);
		$this->assertSame(4, $m->qty);
	}

	public function testFloatLocale(): void
	{
		$prev = setlocale(LC_NUMERIC, 0);
		setlocale(LC_NUMERIC, 'de_DE.UTF-8', 'de_DE', 'de');
		\Orm\Observer_Typing::$use_locale = true;
		$m = Ormtest_Typing_All::forge(array('price' => '1,5'));
		$m->save();
		$loaded = Ormtest_Typing_All::find($m->id);
		$this->assertEquals(1.5, $loaded->price);
		\Orm\Observer_Typing::$use_locale = true;
		if ($prev)
		{
			setlocale(LC_NUMERIC, $prev);
		}
	}

	public function testDecimalEnumSetBool(): void
	{
		$m = Ormtest_Typing_All::forge(array(
			'amount' => '12.34',
			'role' => 'a',
			'flags' => array('x', 'y'),
			'active' => true,
		));
		$m->save();
		$loaded = Ormtest_Typing_All::find($m->id);
		$this->assertEquals(12.34, $loaded->amount);
		$this->assertSame('a', $loaded->role);
		$this->assertSame(array('x', 'y'), $loaded->flags);
		$this->assertTrue($loaded->active);
	}

	public function testSerializeJsonTime(): void
	{
		$ts = time();
		$m = Ormtest_Typing_All::forge(array(
			'meta' => array('k' => 'v'),
			'payload' => array('n' => 1),
			'seen_at' => $ts,
		));
		$m->save();
		$loaded = Ormtest_Typing_All::find($m->id);
		$this->assertSame(array('k' => 'v'), $loaded->meta);
		$this->assertSame(array('n' => 1), $loaded->payload);
		$this->assertSame($ts, $loaded->seen_at);
	}

	public function testEncryptCustomKeyRoundtrip(): void
	{
		$data = array('token' => 'abc');
		$m = $this->saveModel(Ormtest_Typing_All::forge(array('secret' => $data)));
		$loaded = Ormtest_Typing_All::find($m->id);
		$this->assertNotNull($loaded);
		$this->assertSame($data, $loaded->secret);
	}

	public function testOrmNotifySkipsPrimaryKey(): void
	{
		$m = Ormtest_Typing_All::forge(array('qty' => 1));
		$m->id = '7';
		Orm\Observer_Typing::orm_notify($m, 'before_save');
		$this->assertSame('7', $m->id);
	}
}
