<?php

namespace QD\commerce\webshipper\records;

use craft\db\ActiveRecord;

class Shipment extends ActiveRecord
{
	// Public Static Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%webshipper_shipments}}';
	}
}
