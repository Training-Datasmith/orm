<?php

class Ormtest_Validation_Item extends Orm\Model
{
	protected static $_table_name = 'validation_items';
	protected static $_properties = array(
		'id',
		'email' => array(
			'data_type' => 'varchar',
			'label' => 'Email',
			'validation' => array('valid_email'),
		),
		'name' => array(
			'data_type' => 'varchar',
			'validation' => array('required'),
		),
	);
	protected static $_observers = array('Orm\\Observer_Validation');
}

class ObserverValidationTest extends OrmTestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		\DB::query('CREATE TABLE validation_items (id INTEGER PRIMARY KEY, email TEXT, name TEXT)')->execute();
	}

	public function testValidationFailedOnSave(): void
	{
		$m = Ormtest_Validation_Item::forge(array('email' => 'not-an-email', 'name' => 'ok'));
		try
		{
			$m->save();
			$this->fail('Expected ValidationFailed');
		}
		catch (\Orm\ValidationFailed $e)
		{
			$this->assertInstanceOf(\Fieldset::class, $e->get_fieldset());
		}
	}

	public function testSetFieldsBuildsFieldset(): void
	{
		$fieldset = \Orm\Observer_Validation::set_fields('Ormtest_Validation_Item');
		$this->assertNotNull($fieldset->field('email'));
		$this->assertNotNull($fieldset->field('name'));
	}
}
