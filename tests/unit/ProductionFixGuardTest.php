<?php

/**
 * Minimal guards that fail if production fixes are reverted.
 */

class Ormtest_Guard_Map extends Orm\Model
{
	protected static $_table_name = 'guard_map';
	protected static $_properties = array(
		'id',
		'label' => array('data_type' => 'varchar'),
	);
	protected static $_property_map = array(
		'label' => 'mapped_label',
	);
}

class Ormtest_Guard_Encrypt extends Orm\Model
{
	protected static $_table_name = 'guard_encrypt';
	protected static $_properties = array(
		'id',
		'secret' => array(
			'data_type' => 'encrypt',
			'encryption_key' => '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef',
		),
	);
	protected static $_observers = array('Orm\\Observer_Typing');
}

class Ormtest_Guard_Post extends Orm\Model
{
	protected static $_table_name = 'guard_posts';
	protected static $_properties = array('id', 'title');
	protected static $_many_many = array(
		'tags' => array(
			'model_to' => 'Ormtest_Guard_Tag',
			'table_through' => 'guard_posts_tags',
			'key_through_from' => 'post_id',
			'key_through_to' => 'tag_id',
		),
	);
}

class Ormtest_Guard_Tag extends Orm\Model
{
	protected static $_table_name = 'guard_tags';
	protected static $_properties = array('id', 'name');
}

class ProductionFixGuardTest extends OrmTestCase
{
	protected function setUp(): void
	{
		parent::setUp();
	}

	/** Fix 1: __unset via mapped column name delegates to real property */
	public function testFix1PropertyMapUnsetUsesCorrectMethod(): void
	{
		\DB::query('CREATE TABLE guard_map (id INTEGER PRIMARY KEY, label TEXT)')->execute();
		$m = $this->saveModel(Ormtest_Guard_Map::forge(array('label' => 'x')));
		unset($m->mapped_label); // mapped alias for label property
		$this->assertNull($m->label);
	}

	/** Fix 2: type_decrypt accepts per-field encryption_key settings */
	public function testFix2DecryptUsesCustomEncryptionKey(): void
	{
		\DB::query('CREATE TABLE guard_encrypt (id INTEGER PRIMARY KEY, secret TEXT)')->execute();
		$payload = array('orm_custom_key_probe' => 'guard-fix2-only-with-field-key');
		$m = $this->saveModel(Ormtest_Guard_Encrypt::forge(array('secret' => $payload)));
		$ciphertext = (string) \DB::select('secret')->from('guard_encrypt')->where('id', $m->id)->execute()->get('secret');
		$wrongKey = 'ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff';
		$this->assertCustomEncryptionKeyRoundtrip(
			$payload,
			Ormtest_Guard_Encrypt::find($m->id)->secret,
			$ciphertext,
			$wrongKey
		);
	}

	/** Fix 3: is_parent() detects many_many pivot rows */
	public function testFix3IsParentManyMany(): void
	{
		\DB::query('CREATE TABLE guard_posts (id INTEGER PRIMARY KEY, title TEXT)')->execute();
		\DB::query('CREATE TABLE guard_tags (id INTEGER PRIMARY KEY, name TEXT)')->execute();
		\DB::query('CREATE TABLE guard_posts_tags (post_id INTEGER, tag_id INTEGER)')->execute();
		$post = $this->saveModel(Ormtest_Guard_Post::forge(array('title' => 'p')));
		$tag = $this->saveModel(Ormtest_Guard_Tag::forge(array('name' => 't')));
		\DB::insert('guard_posts_tags')->set(array('post_id' => $post->id, 'tag_id' => $tag->id))->execute();
		$this->assertTrue($post->is_parent());
	}

}
