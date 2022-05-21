<?php

namespace DPRMC\RemitSpiderUSBank\Objects;


use Carbon\Carbon;
use DPRMC\RemitSpiderUSBank\Collectors\FileIndex;
use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;

class File extends BaseObject {

    protected string $dealId;

    public function __construct( array  $data,
                                 string $timezone,
                                 string $pathToCache ) {
        parent::__construct( $data, $timezone, $pathToCache );

        $this->dealId = $data[ FileIndex::DEAL_ID ];
    }


    public function getDealId(): string {
        return $this->dealId;
    }

    public function getType(): string {
        return $this->_data[ 'type' ];
    }

    public function getDate(): Carbon {
        return Carbon::parse( $this->_data[ 'date' ], $this->timezone );
    }

    public function getName(): string {
        return $this->_data[ 'name' ];
    }

    public function getHref(): string {
        return $this->_data[ 'href' ];
    }

    public function getLink(): string {
        return RemitSpiderUSBank::BASE_URL . $this->_data[ 'href' ];
    }

    public function getChildrenLastPulled(): string {
        return Carbon::parse( $this->_data[ 'childrenLastPulled' ], $this->timezone );
    }

    public function getAddedAt(): string {
        return Carbon::parse( $this->_data[ 'addedAt' ], $this->timezone );
    }

    public function getCleanType(): string {
        $type = $this->getType();
        $type = strtolower( $type );
        $type = str_replace( ' ', '-', $type );
        return $type;
    }
}
