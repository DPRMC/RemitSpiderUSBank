<?php

namespace DPRMC\RemitSpiderUSBank\Objects;

use Carbon\Carbon;
use DPRMC\RemitSpiderUSBank\Helpers\BaseData;


abstract class BaseObject {

    private array $_data;

    public Carbon $addedAt;
    public Carbon $lastPulled;

    public function __construct( array $data ) {
        $this->_data = $data;

        $this->addedAt    = $data[ BaseData::ADDED_AT ];
        $this->lastPulled = $data[ BaseData::LAST_PULLED ];
    }
}