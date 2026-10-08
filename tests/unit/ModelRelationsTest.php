<?php

class Ormtest_Post extends Orm\Model
{
	protected static $_table_name = 'posts';
	protected static $_properties = array('id', 'title');
	protected static $_has_many = array(
		'comments' => array(
			'model_to' => 'Ormtest_Comment',
			'key_to' => 'post_id',
			'constraint' => \Orm\Relation::CONSTRAINT_CASCADE,
		),
	);
	protected static $_has_one = array(
		'profile' => array('model_to' => 'Ormtest_Profile', 'key_to' => 'post_id'),
	);
	protected static $_many_many = array(
		'tags' => array(
			'model_to' => 'Ormtest_Tag',
			'table_through' => 'posts_tags',
			'key_through_from' => 'post_id',
			'key_through_to' => 'tag_id',
		),
	);
}

class Ormtest_Comment extends Orm\Model
{
	protected static $_table_name = 'comments';
	protected static $_properties = array('id', 'post_id', 'body');
	protected static $_belongs_to = array(
		'post' => array('model_to' => 'Ormtest_Post', 'key_from' => 'post_id'),
	);
}

class Ormtest_Profile extends Orm\Model
{
	protected static $_table_name = 'profiles';
	protected static $_properties = array('id', 'post_id', 'bio');
	protected static $_belongs_to = array(
		'post' => array('model_to' => 'Ormtest_Post'),
	);
}

class Ormtest_Tag extends Orm\Model
{
	protected static $_table_name = 'tags';
	protected static $_properties = array('id', 'name');
}

class Ormtest_PostRestrict extends Orm\Model
{
	protected static $_table_name = 'posts';
	protected static $_properties = array('id', 'title');
	protected static $_has_many = array(
		'comments' => array(
			'model_to' => 'Ormtest_Comment',
			'key_to' => 'post_id',
			'constraint' => \Orm\Relation::CONSTRAINT_RESTRICT,
		),
	);
}

class Ormtest_PostHasManyOnly extends Orm\Model
{
	protected static $_table_name = 'posts';
	protected static $_properties = array('id', 'title');
	protected static $_has_many = array(
		'comments' => array(
			'model_to' => 'Ormtest_Comment',
			'key_to' => 'post_id',
			'constraint' => \Orm\Relation::CONSTRAINT_CASCADE,
		),
	);
}

class Ormtest_PostParentProbe extends Orm\Model
{
	protected static $_table_name = 'posts';
	protected static $_properties = array('id', 'title');
	protected static $_many_many = array(
		'tags' => array(
			'model_to' => 'Ormtest_Tag',
			'table_through' => 'posts_tags',
			'key_through_from' => 'post_id',
			'key_through_to' => 'tag_id',
		),
	);
}

class ModelRelationsTest extends OrmTestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		\DB::query('CREATE TABLE posts (id INTEGER PRIMARY KEY, title TEXT)')->execute();
		\DB::query('CREATE TABLE comments (id INTEGER PRIMARY KEY, post_id INTEGER, body TEXT)')->execute();
		\DB::query('CREATE TABLE profiles (id INTEGER PRIMARY KEY, post_id INTEGER, bio TEXT)')->execute();
		\DB::query('CREATE TABLE tags (id INTEGER PRIMARY KEY, name TEXT)')->execute();
		\DB::query('CREATE TABLE posts_tags (post_id INTEGER, tag_id INTEGER)')->execute();
	}

	public function testHasManySaveAndCascadeDelete(): void
	{
		$post = $this->saveModel(Ormtest_PostHasManyOnly::forge(array('title' => 't')));
		$this->saveModel(Ormtest_Comment::forge(array('post_id' => $post->id, 'body' => 'c1')));
		$this->assertCount(1, Ormtest_Comment::query()->where('post_id', $post->id)->get());
		$post->delete(true);
		$this->assertCount(0, Ormtest_Comment::query()->where('post_id', $post->id)->get());
	}

	public function testHasManyRestrict(): void
	{
		$post = $this->saveModel(Ormtest_PostRestrict::forge(array('title' => 't')));
		$this->saveModel(Ormtest_Comment::forge(array('post_id' => $post->id, 'body' => 'x')));
		$this->expectException(\Orm\DeleteConstraintViolation::class);
		$post->delete(true);
	}

	public function testBelongsTo(): void
	{
		$post = $this->saveModel(Ormtest_Post::forge(array('title' => 't')));
		$comment = $this->saveModel(Ormtest_Comment::forge(array('post_id' => $post->id, 'body' => 'b')));
		$this->assertSame($post->id, $comment->post->id);
	}

	public function testHasOne(): void
	{
		$post = $this->saveModel(Ormtest_Post::forge(array('title' => 't')));
		$this->saveModel(Ormtest_Profile::forge(array('post_id' => $post->id, 'bio' => 'bio')));
		$post = Ormtest_Post::find($post->id);
		$this->assertSame('bio', $post->profile->bio);
	}

	public function testManyMany(): void
	{
		$post = $this->saveModel(Ormtest_Post::forge(array('title' => 't')));
		$tag = $this->saveModel(Ormtest_Tag::forge(array('name' => 'news')));
		$post->set('tags', array($tag));
		$this->assertTrue($post->save());
		$post = Ormtest_Post::find($post->id);
		$this->assertCount(1, $post->tags);
	}

	public function testIsParentHasOneAndManyMany(): void
	{
		$post = $this->saveModel(Ormtest_Post::forge(array('title' => 't')));
		$this->saveModel(Ormtest_Profile::forge(array('post_id' => $post->id, 'bio' => 'b')));
		$this->assertTrue(Ormtest_Post::find($post->id)->is_parent());
		$mmPost = $this->saveModel(Ormtest_PostParentProbe::forge(array('title' => 'mm')));
		$tag = $this->saveModel(Ormtest_Tag::forge(array('name' => 'x')));
		\DB::insert('posts_tags')->set(array('post_id' => $mmPost->id, 'tag_id' => $tag->id))->execute();
		$this->assertContains('tags', Ormtest_PostParentProbe::find($mmPost->id)->is_parent(true));
	}

	public function testRelatedEagerLoad(): void
	{
		$post = $this->saveModel(Ormtest_Post::forge(array('title' => 't')));
		$this->saveModel(Ormtest_Comment::forge(array('post_id' => $post->id, 'body' => 'e')));
		$loaded = Ormtest_Post::query()->related('comments')->get_one();
		$this->assertNotNull($loaded);
		$this->assertTrue($loaded->is_fetched('comments'));
		$this->assertCount(1, $loaded->comments);
	}
}
