<?php

namespace DPRMC\RemitSpiderUSBank\Exceptions;

/**
 * On an inconsistent basis we will get a 403 error.
 * Like we were logged out.
 * That doesn't mean that we don't have access to this deal.
 * It simply means, we need to try back later.
 */
class ExceptionAccessDenied extends \Exception {

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