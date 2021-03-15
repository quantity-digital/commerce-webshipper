<?php

namespace QD\commerce\webshipper\migrations;

use Craft;
use craft\db\Migration;

/**
 * m210315_100020_droppointdata migration.
 */
class m210315_100020_droppointdata extends Migration
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
		echo "m210315_100020_droppointdata cannot be reverted.\n";
		return false;
	}
}
