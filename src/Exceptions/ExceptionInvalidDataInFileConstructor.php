<?php

namespace DPRMC\RemitSpiderUSBank\Exceptions;

use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;
use HeadlessChromium\Page;
use JetBrains\PhpStorm\Internal\LanguageLevelTypeAware;

/**
 *
 */
class ExceptionInvalidDataInFileConstructor extends \Exception {

    public array  $data;
    public string $timezone;
    public string $pathToCache;

    public function __construct( string      $message = "",
                                 int         $code = 0,
                                 ?\Throwable $previous = NULL,
                                 array       $data = [],
                                 string      $timezone = NULL,
                                 string      $pathToCache = NULL ) {
        parent::__construct( $message, $code, $previous );
        $this->data        = $data;
        $this->timezone    = $timezone;
        $this->pathToCache = $pathToCache;
    }

}