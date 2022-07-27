<?php

namespace DPRMC\RemitSpiderUSBank\AdvancedCollectors;


use Carbon\Carbon;
use DPRMC\RemitSpiderUSBank\Helpers\Debug;
use HeadlessChromium\Page;

/**
 *
 */
class PeriodicReportsSecured extends AbstractCollector {


    const TAB_TEXT                 = 'Periodic Reports - Secured';
    const QUERY_SELECTOR_FOR_LINKS = '#results-table > tbody > tr';

    const NAME_INDEX      = 0;
    const DATE_INDEX      = 1;
    const FILE_TYPE_INDEX = 2; // This one has the link.
    const HISTORY_LINK    = 3; // Not used

    const LABEL_DOCUMENT_ID    = 'document_id';
    const LABEL_FILE_TYPE      = 'file_type';
    const LABEL_URL            = 'url';
    const LABEL_DATE_OF_REPORT = 'date_of_report';
    const LABEL_REPORT_NAME    = 'report_name';

    protected function _clickElements( array  $elements,
                                       string $pathToSaveFiles,
                                       Page   $page,
                                       Debug  $debug,
                                       int    $dealId ): array {
        $links = [];


        /**
         * @var \HeadlessChromium\Dom\Node $node
         */
        foreach ( $elements as $node ):
            try {
                $tds = $node->querySelectorAll( 'td' );

                $tdValues = [];
                /**
                 * @var \HeadlessChromium\Dom\Node $tdNode
                 */
                foreach ( $tds as $i => $tdNode ):
                    $tdValues[ $i ] = trim( $tdNode->getText() );
                endforeach;

                /**
                 * @var \HeadlessChromium\Dom\Node $tdWithLink
                 */
                $tdWithLink = $tds[ self::FILE_TYPE_INDEX ];
                $href       = $tdWithLink->getAttribute( 'href' );
                $documentId = $this->_getDocumentIdFromHref( $href );
                $fileType   = $this->_getFileTypeFromHref( $href );
                $this->Debug->_debug( "clicking on (" . $tdValues[ self::DATE_INDEX ] . " / " .
                                      $tdValues[ self::FILE_TYPE_INDEX ] . "): " . $href );

                $completeFilePath = $pathToSaveFiles . DIRECTORY_SEPARATOR .
                                    $dealId . DIRECTORY_SEPARATOR .
                                    $documentId;
                $page->setDownloadPath( $completeFilePath );

                if ( file_exists( $completeFilePath ) ):
                    echo "\n\n";
                    echo $completeFilePath;
                    echo "\n\n";
                    continue;
                endif;

                // Download the file.

                $tds[ self::FILE_TYPE_INDEX ]->click();


                $dateOfReport         = Carbon::createFromFormat( 'm/d/Y', $tdValues[ self::DATE_INDEX ] );
                $links[ $documentId ] = [
                    self::LABEL_DOCUMENT_ID    => $documentId,
                    self::LABEL_FILE_TYPE      => $fileType,
                    self::LABEL_URL            => $href,
                    self::LABEL_DATE_OF_REPORT => $dateOfReport,
                    self::LABEL_REPORT_NAME    => $tdValues[ self::NAME_INDEX ],
                ];

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


    protected function _getDocumentIdFromHref( $href ): int {
        $pattern = "/populateReportDocument\/(\d*)\//";
        $found   = preg_match( $pattern, $href, $matches );
        if ( FALSE === $found ):
            throw new \Exception( "Unable to find the Document ID in " . $href );
        endif;

        return $matches[ 1 ];
    }

    protected function _getFileTypeFromHref( $href ): string {
        $pattern = "/populateReportDocument\/\d*\/(.*)/";
        $found   = preg_match( $pattern, $href, $matches );
        if ( FALSE === $found ):
            throw new \Exception( "Unable to find the File Type in " . $href );
        endif;

        return $matches[ 1 ];
    }


}
