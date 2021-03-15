<?php

namespace QD\commerce\webshipper\migrations;

use Craft;
use craft\db\Migration;
use QD\commerce\webshiper\base\Table;

/**
 * m1510321_120000_droppointinfo migration.
 */
class m1510321_120000_droppointinfo extends Migration
{
	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{
		$this->addColumn('{{%webshipper_orderinfo}}', 'droppointSnapshot', $this->json()->null());
	}

	/**
	 * @inheritdoc
	 */
	public function safeDown()
	{
		echo "m1510321_120000_droppointinfo cannot be reverted.\n";
		return false;
	}
}
