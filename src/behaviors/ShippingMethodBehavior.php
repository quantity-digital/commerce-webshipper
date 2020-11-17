<?php

namespace QD\commerce\webshipper\behaviors;

use Craft;
use craft\commerce\models\ShippingMethod;
use craft\db\Query;
use yii\base\Behavior;

class ShippingMethodBehavior extends Behavior
{
	/**
	 * @var string|null
	 */
	public $webshipperRateId;

	private $droppoints;

	/**
	 * @var string Table name where extra info is stored
	 */
	const EXTRAS_TABLE = '{{%webshipper_shippingmethods}}';


	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function events()
	{
		return [
			ShippingMethod::EVENT_DEFINE_EXTRA_FIELDS => [$this, 'saveShippingInfo'],
		];
	}

	public function getWebshipperRateId()
	{
		$row = (new Query())
			->select('*')
			->from(self::EXTRAS_TABLE)
			->where('id = :id', array(':id' => $this->owner->id))
			->one();

		return isset($row['webshipperRateId']) ? $row['webshipperRateId'] : null;
	}

	public function saveShippingInfo($event)
	{
		$request = Craft::$app->getRequest();
		$this->webshipperRateId = $request->getParam('webshipperRateId');

		if ($this->webshipperRateId) {
			Craft::$app->getDb()->createCommand()
				->upsert(self::EXTRAS_TABLE, [
					'id' => $event->sender->id,
				], [
					'webshipperRateId' => $this->webshipperRateId,
				], [], false)
				->execute();
		}
	}
}
