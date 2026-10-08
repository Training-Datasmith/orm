<?php

use Orm\Model;
use Orm\Model_Nestedset;
use Orm\Model_Soft;
use Orm\Model_Temporal;
use Orm\Observer;
use Orm\Observer_Typing;
use Orm\Query;

abstract class OrmTestCase extends \PHPUnit\Framework\TestCase
{
	/** @var string */
	protected $dbFile;

	protected function setUp(): void
	{
		parent::setUp();
		$this->resetOrmStaticState();
		$this->dbFile = null;
		if ( ! \Config::get('db.default'))
		{
			\Config::load('db', true);
		}
		$this->reconnectDatabase();
	}

	protected function tearDown(): void
	{
		$this->resetOrmStaticState();
		parent::tearDown();
	}

	protected function reconnectDatabase(): void
	{
		\Config::set('db.default.connection.dsn', 'sqlite::memory:');
		\Database_Connection::$instances = array();
		\DB::query('SELECT 1')->execute();
	}

	protected function saveModel(\Orm\Model $model): \Orm\Model
	{
		$this->assertTrue($model->save());

		return $model;
	}

	protected function resetOrmStaticState(): void
	{
		$this->setStaticProperty(Model::class, '_cached_objects', array());
		$this->setStaticProperty(Model::class, '_table_names_cached', array());
		$this->setStaticProperty(Model::class, '_properties_cached', array());
		$this->setStaticProperty(Model::class, '_views_cached', array());
		$this->setStaticProperty(Model::class, '_relations_cached', array());
		$this->setStaticProperty(Model::class, '_observers_cached', array());
		$this->setStaticProperty(Model::class, '$to_array_references', array());
		$this->setStaticProperty(Model::class, '_relation_lazy_load', null);

		$this->setStaticProperty(Query::class, 'caching', null);

		$this->setStaticProperty(Observer::class, '_instances', array());

		$this->setStaticProperty(Model_Soft::class, '_soft_delete_cached', array());
		$this->setStaticProperty(Model_Soft::class, '_disable_filter', array());

		$this->setStaticProperty(Model_Temporal::class, '_temporal_cached', array());
		$this->setStaticProperty(Model_Temporal::class, '_pk_check_disabled', array());
		$this->setStaticProperty(Model_Temporal::class, '_pk_id_only', array());
		$this->setStaticProperty(Model_Temporal::class, '_lazy_filtered_classes', array());

		$this->setStaticProperty(Model_Nestedset::class, '_tree_cached', array());

		Observer_Typing::$use_locale = true;

		$fieldsetRef = new \ReflectionClass(\Fieldset::class);
		if ($fieldsetRef->hasProperty('_instances'))
		{
			$instances = $fieldsetRef->getProperty('_instances');
			$instances->setAccessible(true);
			$instances->setValue(null, array());
		}
	}

	protected function setStaticProperty($class, $property, $value): void
	{
		$ref = new \ReflectionClass($class);
		if ( ! $ref->hasProperty(ltrim($property, '$')))
		{
			return;
		}
		$prop = $ref->getProperty(ltrim($property, '$'));
		$prop->setAccessible(true);
		$prop->setValue(null, $value);
	}

	protected function assertTimeWindow($value, $mysql = false): void
	{
		if ($mysql)
		{
			$this->assertIsString($value);
			$ts = strtotime($value.' UTC');
			$this->assertNotFalse($ts);
			$this->assertGreaterThanOrEqual(time() - 5, $ts);
			$this->assertLessThanOrEqual(time() + 5, $ts);
			return;
		}

		$this->assertIsInt($value);
		$this->assertGreaterThanOrEqual(time() - 5, $value);
		$this->assertLessThanOrEqual(time() + 5, $value);
	}
}
