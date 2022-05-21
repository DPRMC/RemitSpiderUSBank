<?php

namespace DPRMC\RemitSpiderUSBank\Objects;

use Carbon\Carbon;
use DPRMC\RemitSpiderUSBank\Collectors\BaseData;


abstract class BaseObject {

    protected array  $_data;
    protected string $_timezone;

    public ?Carbon $addedAt;
    public ?Carbon $childrenLastPulled;
    public string  $timezone;

    public string $pathToCache;

    // A 1D array of the indexes you need to follow to get to this "rows" data array.
    public array $indexesToData;

    public function __construct( array $data, string $timezone, string $pathToCache ) {
        $this->_data       = $data;
        $this->_timezone   = $timezone;
        $this->pathToCache = $pathToCache;


        $this->addedAt            = isset( $data[ BaseData::ADDED_AT ] ) ? Carbon::parse( $data[ BaseData::ADDED_AT ], $timezone ) : NULL;
        $this->childrenLastPulled = isset( $data[ BaseData::CHILDREN_LAST_PULLED ] ) ? Carbon::parse( $data[ BaseData::CHILDREN_LAST_PULLED ], $timezone ) : NULL;
    }


    /**
     * A simple getter
     * @return string Ex: America/New_York
     */
    public function getTimezone(): string {
        return $this->_timezone;
    }

    /**
     * @return bool
     */
    public function pulledInTheLastDay(): bool {
        if ( is_null( $this->childrenLastPulled ) ):
            return FALSE;
        endif;

        $now           = Carbon::now( $this->_timezone );
        $diffInSeconds = $now->diffInSeconds( $this->childrenLastPulled );
        if ( 86400 > $diffInSeconds ):
            return TRUE;
        endif;

        return FALSE;
    }


}