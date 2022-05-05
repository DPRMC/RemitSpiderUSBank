<?php

namespace DPRMC\RemitSpiderUSBank\Objects;

use Carbon\Carbon;
use DPRMC\RemitSpiderUSBank\Collectors\BaseData;


abstract class BaseObject {

    protected array  $_data;
    protected string $_timezone;

    public ?Carbon $addedAt;
    public ?Carbon $lastPulledAt;
    public string  $timezone;

    public string $pathToCache;

    // A 1D array of the indexes you need to follow to get to this "rows" data array.
    public array $indexesToData;

    public function __construct( array $data, string $timezone, string $pathToCache ) {
        $this->_data       = $data;
        $this->_timezone   = $timezone;
        $this->pathToCache = $pathToCache;


        $this->addedAt      = isset($data[ BaseData::ADDED_AT ]) ? Carbon::parse($data[ BaseData::ADDED_AT ], $timezone) : NULL;
        $this->lastPulledAt = isset($data[ BaseData::LAST_PULLED ]) ? Carbon::parse($data[ BaseData::LAST_PULLED ], $timezone) : NULL;
    }


    /**
     * @return bool
     */
    public function pulledInTheLastDay(): bool {
        $now           = Carbon::now( $this->_timezone );
        $diffInSeconds = $now->diffInSeconds( $this->lastPulledAt );
        if ( 86400 > $diffInSeconds ):
            return TRUE;
        endif;
        return FALSE;
    }


    public function markAsPulled() {
        $string = file_get_contents($this->pathToCache);
        $array = json_decode($string,true);
    }



}