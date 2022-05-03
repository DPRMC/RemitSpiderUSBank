<?php

namespace DPRMC\RemitSpiderUSBank\Collectors;

use DPRMC\RemitSpiderUSBank\Exceptions\Exception404Returned;
use HeadlessChromium\Clip;
use HeadlessChromium\Page;

/**
 *
 */
class Errors {

    /**
     * @param string $url
     * @param string $html
     *
     * @return bool
     * @throws \DPRMC\RemitSpiderUSBank\Exceptions\Exception404Returned
     */
    public static function is404(string $url, string $html): bool {
        $dom          = new \DOMDocument();
        @$dom->loadHTML( $html );
        $titleElements = $dom->getElementsByTagName( 'title' );

        /**
         * @var \DOMElement $element
         */
        foreach ( $titleElements as $element ):
            $title = $element->nodeValue;
            if(false !==  stripos($title,'404')):
                throw new Exception404Returned("404 was returned for " . $url);
            endif;
        endforeach;

        return false;
    }

}