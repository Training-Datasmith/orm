<?php

class Ormtest_Soft_Post extends Orm\Model_Soft
{
	protected static $_table_name = 'soft_posts';
	protected static $_properties = array('id', 'title', 'deleted_at');
	protected static $_soft_delete = array(
		'deleted_field' => 'deleted_at',
		'mysql_timestamp' => false,
	);
	protected static $_has_many = array(
		'comments' => array(
			'model_to' => 'Ormtest_Soft_Comment',
			'key_to' => 'post_id',
			'cascade_delete' => true,
		),
	);
}

class Ormtest_Soft_Comment extends Orm\Model_Soft
{
	protected static $_table_name = 'soft_comments';
	protected static $_properties = array('id', 'post_id', 'body', 'deleted_at');
	protected static $_soft_delete = array('deleted_field' => 'deleted_at');
	protected static $_belongs_to = array(
		'post' => array(
			'model_to' => 'Ormtest_Soft_Post',
			'key_from' => 'post_id',
		),
	);
}

class SoftDeleteTest extends OrmTestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		\DB::query('CREATE TABLE soft_posts (id INTEGER PRIMARY KEY, title TEXT, deleted_at INTEGER)')->execute();
		\DB::query('CREATE TABLE soft_comments (id INTEGER PRIMARY KEY, post_id INTEGER, body TEXT, deleted_at INTEGER)')->execute();
	}

	public function testDeleteRestoreFilterPurge(): void
	{
		$post = $this->saveModel(Ormtest_Soft_Post::forge(array('title' => 't')));
		$id = $post->id;
		$post->delete();
		$this->assertNotNull($post->deleted_at);
		$this->assertNull(Ormtest_Soft_Post::find($id));
		Ormtest_Soft_Post::disable_filter();
		$found = Ormtest_Soft_Post::find($id);
		$this->assertNotNull($found);
		$found->restore();
		Ormtest_Soft_Post::enable_filter();
		$this->assertNotNull(Ormtest_Soft_Post::find($id));
		$post = Ormtest_Soft_Post::find($id);
		$post->purge();
		Ormtest_Soft_Post::disable_filter();
		$this->assertNull(Ormtest_Soft_Post::find($id));
		Ormtest_Soft_Post::enable_filter();
	}

	public function testCascadeDeleteAndRelatedFilter(): void
	{
		$post = $this->saveModel(Ormtest_Soft_Post::forge(array('title' => 't')));
		$comment = $this->saveModel(Ormtest_Soft_Comment::forge(array('post_id' => $post->id, 'body' => 'c')));
		$post->delete(true);
		Ormtest_Soft_Comment::disable_filter();
		$deletedComment = Ormtest_Soft_Comment::find($comment->id);
		if ($deletedComment === null)
		{
			$this->assertSame(0, Ormtest_Soft_Comment::query()->where('post_id', $post->id)->count());
		}
		else
		{
			$this->assertNotNull($deletedComment->deleted_at);
		}
		Ormtest_Soft_Comment::enable_filter();
	}
}
