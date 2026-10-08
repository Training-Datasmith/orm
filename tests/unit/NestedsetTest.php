<?php

class Ormtest_Nested_Node extends Orm\Model_Nestedset
{
	protected static $_tree = array('title_field' => 'title');
	protected static $_table_name = 'nested_nodes';
	protected static $_properties = array(
		'id',
		'title',
		'left_id',
		'right_id',
	);
}

class Ormtest_Nested_Compound extends Orm\Model_Nestedset
{
	protected static $_primary_key = array('id', 'other');
	protected static $_table_name = 'nested_compound';
	protected static $_properties = array('id', 'other', 'left_id', 'right_id');
}

class NestedsetTest extends OrmTestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		\DB::query('CREATE TABLE nested_nodes (
			id INTEGER PRIMARY KEY,
			title TEXT,
			left_id INTEGER,
			right_id INTEGER
		)')->execute();
	}

	public function testCompoundPrimaryKeyRejected(): void
	{
		$this->expectException(\OutOfBoundsException::class);
		new Ormtest_Nested_Compound();
	}

	public function testBooleanHelpersOnLoadedNode(): void
	{
		$root = Ormtest_Nested_Node::forge(array(
			'title' => 'root',
			'left_id' => 1,
			'right_id' => 4,
		), false);
		$child = Ormtest_Nested_Node::forge(array(
			'title' => 'child',
			'left_id' => 2,
			'right_id' => 3,
		), false);
		$this->assertTrue($root->is_root());
		$this->assertFalse($root->is_leaf());
		$this->assertTrue($child->is_leaf());
		$this->assertTrue($child->is_descendant_of($root));
		$this->assertTrue($root->is_ancestor_of($child));
		$this->assertSame(1, $root->count_descendants());
	}

	public function testReadOnlyTreeFieldCannotBeChanged(): void
	{
		$node = Ormtest_Nested_Node::forge(array('title' => 'n', 'left_id' => 1, 'right_id' => 2), false);
		$this->expectException(\InvalidArgumentException::class);
		$node->set('left_id', 9);
	}

	public function testTreeConfigDefaults(): void
	{
		$config = Ormtest_Nested_Node::tree_config();
		$this->assertSame('left_id', $config['left_field']);
		$this->assertSame('right_id', $config['right_field']);
	}
}
