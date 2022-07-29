<?php

namespace DPRMC\RemitSpiderUSBank\AdvancedCollectors;


use Carbon\Carbon;
use DPRMC\RemitSpiderUSBank\Exceptions\ExceptionWeDoNotHaveAccessToPeriodicReportsSecured;
use DPRMC\RemitSpiderUSBank\Helpers\Debug;
use HeadlessChromium\Page;

/**
 *
 */
class PeriodicReportsSecured extends AbstractCollector {

    protected string $tabText = 'Periodic Reports - Secured';
    protected string $querySelectorForLinks = '#results-table > tbody > tr';

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

        // If we don't have access throw an Exception.
        $alertString = "You do not have access to this deal or feature.";
        $html = $page->getHtml();
        if( str_contains($html, $alertString) ):
            throw new ExceptionWeDoNotHaveAccessToPeriodicReportsSecured("We do not have access.",
                                                                         null,
                                                                         null,
                                                                         $dealId,
                                                                         $alertString,
                                                                         $html);
        else:
            $debug->_debug("We do have acccess to 'Periodic Reports - Secured' documents for Deal ID: " . $dealId);
        endif;


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
                $anchorNode = $tdWithLink->querySelector( 'a' );
                $href       = $anchorNode->getAttribute( 'href' );

                $documentId   = $this->_getDocumentIdFromHref( $href );
                $fileType     = $this->_getFileTypeFromHref( $href );
                $dateOfReport = Carbon::createFromFormat( 'm/d/Y', $tdValues[ self::DATE_INDEX ] );


                $filePathWithDealIdAndDocumentId = $pathToSaveFiles . DIRECTORY_SEPARATOR .
                                                   $documentId;
                $page->setDownloadPath( $filePathWithDealIdAndDocumentId );

                if ( file_exists( $filePathWithDealIdAndDocumentId ) ):

                    $this->Debug->_debug( $filePathWithDealIdAndDocumentId . " EXISTS. skip it!" );
                    continue;
                else:
                    $this->Debug->_debug( $filePathWithDealIdAndDocumentId . " does not exist. DOWNLOAD IT!" );
                endif;

                // Download the file.
                $this->Debug->_debug( "clicking on (" . $dateOfReport->toDateString() . " / " . $fileType . "): " . $href . "\n" );
                $anchorNode->click();
                sleep( 1 );


                $links[ $documentId ] = [
                    self::LABEL_DOCUMENT_ID    => $documentId,
                    self::LABEL_FILE_TYPE      => $fileType,
                    self::LABEL_URL            => $href,
                    self::LABEL_DATE_OF_REPORT => $dateOfReport,
                    self::LABEL_REPORT_NAME    => $tdValues[ self::NAME_INDEX ],
                ];
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
