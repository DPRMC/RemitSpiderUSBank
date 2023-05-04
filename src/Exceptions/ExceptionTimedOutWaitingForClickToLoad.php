<?php

namespace DPRMC\RemitSpiderUSBank\Exceptions;

use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;
use HeadlessChromium\Page;

/**
 *
 */
class ExceptionTimedOutWaitingForClickToLoad extends \Exception {

    public string $dealId;

    public function __construct( string $message = "", int $code = 0, ?\Throwable $previous = NULL, string $dealId='' ) {
        parent::__construct( $message, $code, $previous );

        $this->dealId = $dealId;
    }
}