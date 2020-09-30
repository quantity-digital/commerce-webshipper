<?php

namespace QD\commerce\webshipper\records;

use craft\db\ActiveRecord;

class ShipmentLine extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%webshipper_shipment_lines}}';
    }
}
