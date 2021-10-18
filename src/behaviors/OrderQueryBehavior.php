<?php

namespace QD\commerce\webshipper\behaviors;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use yii\base\Behavior;

class OrderQueryBehavior extends Behavior
{
	/**
	 * @var mixed Value
	 */
	public $webshipperId;

	/**
	 * @var mixed Value
	 */
	public $droppointId;

	/**
	 * @inheritdoc
	 */
	public function events()
	{
		return [
			ElementQuery::EVENT_BEFORE_PREPARE => 'beforePrepare',
		];
	}

	/**
	 * Applies the `webshipperID param to the query. Accepts anything that can eventually be passed to `Db::parseParam(…)`.
	 *
	 * @param mixed $value
	 */
	public function webshipperId($value)
	{
		$this->webshipperId = $value;
	}

	/**
	 * Applies the `droppointId param to the query. Accepts anything that can eventually be passed to `Db::parseParam(…)`.
	 *
	 * @param mixed $value
	 */
	public function droppointId($value)
	{
		$this->droppointId = $value;
	}

	/**
	 * Prepares the user query.
	 */
	public function beforePrepare()
	{
		if ($this->owner->select === ['COUNT(*)']) {
			return;
		}

		// Join our `orderextras` table:
		$this->owner->query->leftJoin('webshipper_orderinfo webshipper', '`webshipper`.id = `commerce_orders`.`id`');

		// Select custom columns:
		$this->owner->query->addSelect([
			'webshipper.webshipperId',
			'webshipper.droppointId',
			'webshipper.droppointSnapshot',
		]);

		if (!is_null($this->webshipperId)) {
			$this->owner->subQuery->andWhere(Db::parseParam('webshipper.webshipperId', $this->webshipperId));
		}

		if (!is_null($this->droppointId)) {
			$this->owner->subQuery->andWhere(Db::parseParam('webshipper.droppointId', $this->droppointId));
		}
	}
}
