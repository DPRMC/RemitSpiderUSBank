<?php

namespace DPRMC\RemitSpiderUSBank\Objects;

use Carbon\Carbon;
use DPRMC\RemitSpiderUSBank\Collectors\BaseData;


abstract class BaseObject {

    private array $_data;
    private string $_timezone;

    public Carbon $addedAt;
    public Carbon $lastPulledAt;
    public string $timezone;

    public function __construct( array $data, string $timezone) {
        $this->_data = $data;
        $this->_timezone = $timezone;

        $this->addedAt      = $data[ BaseData::ADDED_AT ];
        $this->lastPulledAt = $data[ BaseData::LAST_PULLED ];
    }


    /**
     * @return bool
     */
    public function pulledInTheLastDay(): bool {
        $now = Carbon::now($this->_timezone);
        $diffInSeconds = $now->diffInSeconds($this->lastPulledAt);
        if(86400 > $diffInSeconds):
            return true;
        endif;
        return false;
    }
}