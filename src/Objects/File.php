<?php

namespace DPRMC\RemitSpiderUSBank\Objects;


use Carbon\Carbon;
use DPRMC\RemitSpiderUSBank\Collectors\BaseData;
use DPRMC\RemitSpiderUSBank\Collectors\FileIndex;
use DPRMC\RemitSpiderUSBank\Exceptions\ExceptionInvalidDataInFileConstructor;
use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;

class File extends BaseObject {

    protected string $dealId;

    /**
     * @param array  $data
     * @param string $timezone
     * @param string $pathToCache
     *
     * @throws \DPRMC\RemitSpiderUSBank\Exceptions\ExceptionInvalidDataInFileConstructor
     */
    public function __construct( array  $data,
                                 string $timezone,
                                 string $pathToCache ) {
        parent::__construct( $data, $timezone, $pathToCache );

        if ( !isset( $data[ FileIndex::DEAL_ID ] ) ):
            throw new ExceptionInvalidDataInFileConstructor( "Missing the Deal ID. Can't make the File object.", 0, NULL,
                                                             $data,
                                                             $timezone,
                                                             $pathToCache );
        endif;

        $this->dealId = $data[ FileIndex::DEAL_ID ];
    }


    public function getDealId(): string {
        return $this->dealId;
    }

    public function getType(): string {
        return $this->_data[ FileIndex::TYPE ];
    }

    public function getCleanType(): string {
        $type = $this->getType();
        $type = strtolower( $type );
        $type = str_replace( ' ', '-', $type );
        return $type;
    }

    public function getDate(): Carbon {
        return Carbon::parse( $this->_data[ FileIndex::DATE ], $this->getTimezone() );
    }

    public function getName(): string {
        return $this->_data[ FileIndex::NAME ];
    }

    public function getCleanName(): string {
        $name = $this->getName();
        $name = strtolower( $name );

        // There are some tabs and new lines in some of the names.
        // Let's get rid of those.
        $pattern = '/\s/';
        $name    = preg_replace( $pattern, '-', $name );

        $name = str_replace( '_', '-', $name );

        // Let's replace repeat dashes (--) with single dashes.
        $pattern = '/-{2,}/';
        $name    = preg_replace( $pattern, '-', $name );

        return $name;
    }

    public function getHref(): string {
        return $this->_data[ FileIndex::HREF ];
    }


    public function getLink(): string {
        return RemitSpiderUSBank::BASE_URL . $this->_data[ FileIndex::HREF ];
    }

    public function getChildrenLastPulled(): string {
        return Carbon::parse( $this->_data[ BaseData::CHILDREN_LAST_PULLED ], $this->getTimezone() );
    }

    public function getAddedAt(): string {
        return Carbon::parse( $this->_data[ BaseData::ADDED_AT ], $this->getTimezone() );
    }


}
