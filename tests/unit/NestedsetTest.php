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

class Ormtest_Nested_Forest extends Orm\Model_Nestedset
{
	protected static $_table_name = 'nested_forest';
	protected static $_tree = array('tree_field' => 'tree_id');
	protected static $_properties = array(
		'id',
		'title',
		'left_id',
		'right_id',
		'tree_id',
	);
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
		\DB::query('CREATE TABLE nested_forest (
			id INTEGER PRIMARY KEY,
			title TEXT,
			left_id INTEGER,
			right_id INTEGER,
			tree_id INTEGER
		)')->execute();
	}

	public function testRootChildSiblingsAndPath(): void
	{
		$root = $this->saveModel(Ormtest_Nested_Node::forge(array('title' => 'root')));
		$child = Ormtest_Nested_Node::forge(array('title' => 'child'));
		$child->child($root)->save();
		$this->assertTrue($root->is_root());
		$this->assertTrue($child->is_child_of($root));
		$siblings = $root->children()->get();
		$this->assertCount(1, $siblings);
		$path = $child->path()->get();
		$this->assertStringContainsString('child', $path);
	}

	public function testMoveAndDelete(): void
	{
		$a = $this->saveModel(Ormtest_Nested_Node::forge(array('title' => 'a')));
		$b = $this->saveModel(Ormtest_Nested_Node::forge(array('title' => 'b')));
		$b->previous_sibling($a)->save();
		$this->assertLessThan($b->left_id, $a->left_id);
		$leaf = Ormtest_Nested_Node::forge(array('title' => 'leaf'));
		$leaf->child($a)->save();
		$leaf->delete();
		$this->assertNull(Ormtest_Nested_Node::find($leaf->id));
	}

	public function testDuplicateRootGuard(): void
	{
		$this->saveModel(Ormtest_Nested_Node::forge(array('title' => 'r1')));
		$this->expectException(\OutOfBoundsException::class);
		$this->saveModel(Ormtest_Nested_Node::forge(array('title' => 'r2')));
	}

	public function testMultiTreeAndBooleanHelpers(): void
	{
		$r1 = $this->saveModel(Ormtest_Nested_Forest::forge(array('title' => 't1')));
		$r2 = $this->saveModel(Ormtest_Nested_Forest::forge(array('title' => 't2')));
		$this->assertNotEquals($r1->tree_id, $r2->tree_id);
		$c = Ormtest_Nested_Forest::forge(array('title' => 'c'));
		$c->child($r1)->save();
		$this->assertTrue($r1->is_parent_of($c));
		$this->assertTrue($c->is_descendant_of($r1));
	}
}
