<?php

namespace DPRMC\RemitSpiderUSBank\Exceptions;

use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;
use HeadlessChromium\Page;
use JetBrains\PhpStorm\Internal\LanguageLevelTypeAware;

/**
 *
 */
class ExceptionUnableToFindPrincipalAndInterestTab extends \Exception {

    public int    $dealId;
    public string $dealLinkSuffix;

    public function __construct( string      $message = "",
                                 int         $code = 0,
                                 ?\Throwable $previous = NULL,
                                 int         $dealId = NULL,
                                 string      $dealLinkSuffix = NULL ) {
        parent::__construct( $message, $code, $previous );

        $this->dealId         = $dealId;
        $this->dealLinkSuffix = $dealLinkSuffix;
    }
}