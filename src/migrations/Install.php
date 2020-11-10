<?php

namespace QD\commerce\webshipper\migrations;

Use Craft;
use craft\db\Migration;
use craft\helpers\MigrationHelper;

class Install extends Migration
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function safeUp(): bool
	{
		$this->createTables();
		$this->createIndexes();
		$this->addForeignKeys();

		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function safeDown(): bool
	{
		$this->dropForeignKeys();
		$this->dropTables();
		$this->dropProjectConfig();

		return true;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Creates the tables for Shipments
	 *
	 * @return void
	 */
	protected function createTables()
	{
		$this->createTable('{{%webshipper_shipments}}', [
			'id' => $this->primaryKey(),
			'orderId' => $this->integer()->notNull(),
			'webshipperId' => $this->integer()->notNull(),
			'packageCount' => $this->integer()->null(),
			'trackingLink' => $this->string()->null(),
			'dateCreated' => $this->dateTime()->notNull(),
			'dateUpdated' => $this->dateTime()->notNull(),
			'uid' => $this->uid(),
		]);

		$this->createTable('{{%webshipper_shipment_lines}}', [
			'id' => $this->primaryKey(),
			'shipmentId' => $this->integer()->notNull(),
			'lineItemId' => $this->integer()->notNull(),
			'shippedQty' => $this->integer()->notNull(),
			'dateCreated' => $this->dateTime()->notNull(),
			'dateUpdated' => $this->dateTime()->notNull(),
			'uid' => $this->uid(),
		]);

		$this->createTable('{{%webshipper_orderinfo}}', [
            'id' => $this->integer()->notNull(),
            'webshipperId' => $this->string()->null(),
            'droppointId' => $this->string()->null(),
            'PRIMARY KEY([[id]])',
        ]);

		$this->createTable('{{%webshipper_shippingmethods}}', [
            'id' => $this->integer()->notNull(),
            'webshipperRateId' => $this->string()->null(),
            'PRIMARY KEY([[id]])',
        ]);
	}

	/**
	 * Drop the tables
	 *
	 * @return void
	 */
	protected function dropTables()
	{
		$this->dropTableIfExists('{{%webshipper_shipments}}');
		$this->dropTableIfExists('{{%webshipper_shipment_lines}}');
		$this->dropTableIfExists('{{%webshipper_orderinfo}}');
		$this->dropTableIfExists('{{%webshipper_shippingmethods}}');

		return null;
	}

	/**
	 * Deletes the project config entry.
	 */
	protected function dropProjectConfig()
	{
		Craft::$app->projectConfig->remove('commerce-webshipper');
	}

	/**
	 * Creates the indexes.
	 *
	 * @return void
	 */
	protected function createIndexes()
	{
		$this->createIndex($this->db->getIndexName('{{%webshipper_shipments}}', 'orderId', false), '{{%webshipper_shipments}}', 'orderId', false);
		$this->createIndex($this->db->getIndexName('{{%webshipper_shipment_lines}}', 'lineItemId', false), '{{%webshipper_shipment_lines}}', 'lineItemId', false);
		$this->createIndex($this->db->getIndexName('{{%webshipper_shipment_lines}}', 'shipmentId', false), '{{%webshipper_shipment_lines}}', 'shipmentId', false);
	}

	/**
	 * Adds the foreign keys.
	 *
	 * @return void
	 */
	protected function addForeignKeys()
	{
		$this->addForeignKey($this->db->getForeignKeyName('{{%webshipper_shipments}}', 'orderId'), '{{%webshipper_shipments}}', 'orderId', '{{%commerce_orders}}', 'id', 'CASCADE', 'CASCADE');
		$this->addForeignKey($this->db->getForeignKeyName('{{%webshipper_shipment_lines}}', 'lineItemId'), '{{%webshipper_shipment_lines}}', 'lineItemId', '{{%commerce_lineitems}}', 'id', 'CASCADE', 'CASCADE');
		$this->addForeignKey($this->db->getForeignKeyName('{{%webshipper_shipment_lines}}', 'shipmentId'), '{{%webshipper_shipment_lines}}', 'shipmentId', '{{%webshipper_shipments}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%webshipper_orderinfo}}', ['id'], '{{%commerce_orders}}', ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%webshipper_shippingmethods}}', ['id'], '{{%commerce_shippingmethods}}', ['id'], 'CASCADE', 'CASCADE');
	}

	/**
	 * Adds the foreign keys.
	 *
	 * @return void
	 */
	protected function dropForeignKeys()
	{
		if ($this->db->tableExists('{{%webshipper_shipments}}')) {
			MigrationHelper::dropAllForeignKeysOnTable('{{%webshipper_shipments}}', $this);
		}

		if ($this->db->tableExists('{{%webshipper_shipment_lines}}')) {
			MigrationHelper::dropAllForeignKeysOnTable('{{%webshipper_shipment_lines}}', $this);
		}

		if ($this->db->tableExists('{{%webshipper_orderinfo}}')) {
			MigrationHelper::dropAllForeignKeysOnTable('{{%webshipper_orderinfo}}', $this);
		}

		if ($this->db->tableExists('{{%webshipper_shippingmethods}}')) {
			MigrationHelper::dropAllForeignKeysOnTable('{{%webshipper_shippingmethods}}', $this);
		}
	}
}
