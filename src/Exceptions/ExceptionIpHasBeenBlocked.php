<?php

namespace DPRMC\RemitSpiderUSBank\Exceptions;

/**
 * They will block your IP address if you hit their system too frequently.
 */
class ExceptionIpHasBeenBlocked extends \Exception {

    public int    $dealId;
    public string $html;

    public function __construct( string      $message = "",
                                 int         $code = 0,
                                 ?\Throwable $previous = NULL,
                                 ?int        $dealId = NULL,
                                 ?string     $html = NULL ) {
        parent::__construct( $message, $code, $previous );

        $this->dealId          = $dealId;
        $this->html            = $html;
    }
}