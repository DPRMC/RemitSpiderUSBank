<?php

namespace DPRMC\RemitSpiderUSBank\Exceptions;

/**
 *
 */
class ExceptionOurAccessToThisPeriodicReportSecuredIsPending extends \Exception {

    public string $dealLinkSuffix = '';

    public function __construct( string $message = "", int $code = 0, ?\Throwable $previous = NULL, string $dealLinkSuffix = '' ) {
        parent::__construct( $message, $code, $previous );

        $this->dealLinkSuffix = $dealLinkSuffix;
    }
}