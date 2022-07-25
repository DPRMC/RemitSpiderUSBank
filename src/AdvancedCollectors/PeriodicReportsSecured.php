<?php

namespace DPRMC\RemitSpiderUSBank\AdvancedCollectors;



/**
 *
 */
class PeriodicReportsSecured extends AbstractCollector {


    const TAB_TEXT = 'Periodic Reports - Secured';


    protected function _clickElements( array $elements, string $pathToSaveFiles ): array {
        $links = [];
        foreach ( $elements as $element ):
            try {
                $href         = $element->getAttribute( 'href' );
                //$dateFromHref = $this->_getDateFromHref( $href );
                //$dateString   = $dateFromHref->format( 'Y-m-d' );
                //$this->Debug->_debug( "clicking on (" . $dateString . "): " . $href );
                //$dateFromHref               = $this->_getDateFromHref( $href );
                //$dateString                 = $dateFromHref->format( 'Y-m-d' );
                //$factorLinks[ $dateString ] = $href;
                //$filenameParts              = explode( '?', basename( $href ) );
                //$filePathToTestFor          = $pathToSaveFiles . DIRECTORY_SEPARATOR . $filenameParts[ 0 ];
                //
                //if ( file_exists( $filePathToTestFor ) ):
                //    continue;
                //endif;
                //
                //$element->click(); // New
                //sleep( 1 );
            } catch ( \Exception $exception ) {
                $this->Debug->_debug( "EXCEPTION: " . $exception->getMessage() );
            }

        endforeach;
        return $links;
    }



}
