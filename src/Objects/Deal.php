<?php

namespace DPRMC\RemitSpiderUSBank\Objects;

use Carbon\Carbon;
use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;
use HeadlessChromium\Page;



class Deal extends BaseObject {

    public function __construct( array $data, string $timezone ) {
        parent::__construct( $data, $timezone );
    }


}