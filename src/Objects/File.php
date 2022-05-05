<?php

namespace DPRMC\RemitSpiderUSBank\Objects;

use DPRMC\RemitSpiderUSBank\Collectors\FileIndex;

class File extends BaseObject {

    public function __construct( array $data, string $timezone, string $pathToCache ) {
        parent::__construct( $data, $timezone, $pathToCache );
    }
}
