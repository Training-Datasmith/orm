<?php

class Ormtest_Rel_Parent extends Orm\Model
{
	protected static $_table_name = 'rel_parent';
	protected static $_properties = array('id');
	protected static $_has_one = array(
		'child' => array('model_to' => 'Ormtest_Rel_Child'),
	);
	protected static $_has_many = array(
		'items' => array(
			'model_to' => 'Ormtest_Rel_Item',
			'constraint' => \Orm\Relation::CONSTRAINT_CASCADE,
		),
	);
	protected static $_many_many = array(
		'tags' => array(
			'model_to' => 'Ormtest_Rel_Tag',
			'table_through' => 'rel_parent_tags',
		),
	);
}

class Ormtest_Rel_Child extends Orm\Model
{
	protected static $_table_name = 'rel_child';
	protected static $_properties = array('id', 'parent_id');
}

class Ormtest_Rel_Item extends Orm\Model
{
	protected static $_table_name = 'rel_items';
	protected static $_properties = array('id', 'parent_id');
}

class Ormtest_Rel_Tag extends Orm\Model
{
	protected static $_table_name = 'rel_tags';
	protected static $_properties = array('id');
}

class Ormtest_Rel_Collision extends Orm\Model
{
	protected static $_table_name = 'rel_collision';
	protected static $_properties = array('id', 'notes');
	protected static $_has_many = array('notes');
}

class Ormtest_Rel_Missing extends Orm\Model
{
	protected static $_table_name = 'rel_missing';
	protected static $_properties = array('id');
	protected static $_has_many = array(
		'ghosts' => array('model_to' => 'Ormtest_Rel_DoesNotExist'),
	);
}

class RelationMetadataTest extends OrmTestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		\DB::query('CREATE TABLE rel_parent (id INTEGER PRIMARY KEY)')->execute();
		\DB::query('CREATE TABLE rel_child (id INTEGER PRIMARY KEY, parent_id INTEGER)')->execute();
		\DB::query('CREATE TABLE rel_items (id INTEGER PRIMARY KEY, parent_id INTEGER)')->execute();
		\DB::query('CREATE TABLE rel_tags (id INTEGER PRIMARY KEY)')->execute();
		\DB::query('CREATE TABLE rel_parent_tags (rel_parent_id INTEGER, rel_tag_id INTEGER)')->execute();
		\DB::query('CREATE TABLE rel_collision (id INTEGER PRIMARY KEY, notes TEXT)')->execute();
		\DB::query('CREATE TABLE rel_missing (id INTEGER PRIMARY KEY)')->execute();
	}

	public function testRelationDefaultsAndManyManyTable(): void
	{
		$hasOne = Ormtest_Rel_Parent::relations('child');
		$this->assertTrue($hasOne->cascade_save);
		$this->assertFalse($hasOne->cascade_delete);
		$many = Ormtest_Rel_Parent::relations('tags');
		$this->assertSame('rel_parent_tags', $many->table_through);
	}

	public function testMissingModelThrows(): void
	{
		$this->expectException(\FuelException::class);
		Ormtest_Rel_Missing::relations();
	}

	public function testPropertyRelationCollisionThrows(): void
	{
		$this->expectException(\FuelException::class);
		Ormtest_Rel_Collision::relations();
	}

	public function testConstraintCascadeOnHasMany(): void
	{
		$rel = Ormtest_Rel_Parent::relations('items');
		$this->assertTrue($rel->cascade_delete);
	}

	public function testRelationSelect(): void
	{
		$rel = Ormtest_Rel_Parent::relations('child');
		$selected = $rel->select('t1');
		$this->assertNotEmpty($selected);
		$this->assertStringContainsString('t1', $selected[0][0]);
	}
}
