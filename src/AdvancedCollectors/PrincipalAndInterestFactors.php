<?php

namespace DPRMC\RemitSpiderUSBank\AdvancedCollectors;


use Carbon\Carbon;
use DPRMC\RemitSpiderUSBank\Exceptions\ExceptionUnableToFindPrincipalAndInterestTab;
use DPRMC\RemitSpiderUSBank\Helpers\Debug;
use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;
use HeadlessChromium\Page;

/**
 *
 */
class PrincipalAndInterestFactors extends AbstractCollector {

    /**
     * @param array  $elements
     * @param string $pathToSaveFiles
     *
     * @return array
     */
    protected function _clickElements( array $elements, string $pathToSaveFiles ): array {
        $factorLinks = [];
        foreach ( $elements as $element ):
            try {
                $href         = $element->getAttribute( 'href' );
                $dateFromHref = $this->_getDateFromHref( $href );
                $dateString   = $dateFromHref->format( 'Y-m-d' );
                $this->Debug->_debug( "clicking on (" . $dateString . "): " . $href );
                $dateFromHref               = $this->_getDateFromHref( $href );
                $dateString                 = $dateFromHref->format( 'Y-m-d' );
                $factorLinks[ $dateString ] = $href;
                $filenameParts              = explode( '?', basename( $href ) );
                $filePathToTestFor          = $pathToSaveFiles . DIRECTORY_SEPARATOR . $filenameParts[ 0 ];

                if ( file_exists( $filePathToTestFor ) ):
                    continue;
                endif;

                $element->click(); // New
                sleep( 1 );
            } catch ( \Exception $exception ) {
                $this->Debug->_debug( "EXCEPTION: " . $exception->getMessage() );
            }

        endforeach;
        return $factorLinks;
    }


    protected function _getDateFromHref( $href ): Carbon {
        $pattern = '/(\d{2}-\d{2}-\d{4})/';
        $found   = preg_match( $pattern, $href, $matches );
        if ( 1 !== $found ):
            throw new \Exception( "Could not find the date in this href: " . $href );
        endif;

        return Carbon::createFromFormat( 'm-d-Y', $matches[ 1 ] );
    }


    protected function _getDealIdFromDealLinkSuffix( string $dealLinkSuffix ): string {
        $parts = explode( '/', $dealLinkSuffix );
        if ( 2 != count( $parts ) ):
            throw new \Exception( "There was a problem getting the deal id from: " . $dealLinkSuffix );
        endif;
        return $parts[ 0 ];
    }

    protected function _getDealNameFromDealLinkSuffix( string $dealLinkSuffix ): string {
        $parts = explode( '/', $dealLinkSuffix );
        if ( 2 != count( $parts ) ):
            throw new \Exception( "There was a problem getting the deal id from: " . $dealLinkSuffix );
        endif;
        return $parts[ 1 ];
    }


    /**
     * Example factorLink:
     * /TIR/public/deals/10101/downloadcomponentfactorsummaries/lxs-2007-6-05-25-2007.csv?OWASP_CSRFTOKEN=N8ZM-SJK6-P7PA-QT26-JA6A-TL2Q-NSCU-DUQA
     *
     * @param string $factorLink
     *
     * @return string
     */
    protected function _getFilenameFromFactorLink( string $factorLink ): string {
        $parts       = explode( '/', $factorLink );
        $endingParts = explode( '?', $parts[ 5 ] );
        return $endingParts[ 0 ];
    }


}
