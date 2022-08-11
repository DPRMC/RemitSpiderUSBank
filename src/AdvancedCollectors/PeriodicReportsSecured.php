<?php

namespace DPRMC\RemitSpiderUSBank\AdvancedCollectors;


use Carbon\Carbon;
use DPRMC\RemitSpiderUSBank\Exceptions\ExceptionWeDoNotHaveAccessToPeriodicReportsSecured;
use DPRMC\RemitSpiderUSBank\Helpers\Debug;
use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;
use HeadlessChromium\Page;
use Illuminate\Support\Facades\Storage;

/**
 *
 */
class PeriodicReportsSecured extends AbstractCollector {

    protected string $tabText               = 'Periodic Reports - Secured';
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

    const LABEL_LOCAL_PATH = 'local_path'; // TODO add this tomorrow.

    // For get contents via get
    const BODY     = 'body';
    const FILENAME = 'filename';
    const HEADERS  = 'headers';


    protected function _clickElements( array  $elements,
                                       string $pathToSaveFiles,
                                       Page   $page,
                                       Debug  $debug,
                                       int    $dealId,
                                       array  $misc = [] ): array {

        // If we don't have access throw an Exception.
        $alertString = "You do not have access to this deal or feature.";
        $html        = $page->getHtml();
        if ( str_contains( $html, $alertString ) ):
            throw new ExceptionWeDoNotHaveAccessToPeriodicReportsSecured( "We do not have access.",
                                                                          0,
                                                                          NULL,
                                                                          $dealId,
                                                                          $alertString,
                                                                          $html );
        else:
            $debug->_debug( "We do have access to 'Periodic Reports - Secured' documents for Deal ID: " . $dealId );
        endif;

        $this->Debug->_debug( "---path to save files set to: " . $pathToSaveFiles );

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

                $finalReportName = $this->_getFinalFileName( $dealId,
                                                             $dateOfReport->toDateString(),
                                                             $tdValues[ self::NAME_INDEX ],
                                                             $documentId,
                                                             $fileType );

                // Since there is no *easy* way for Headless Chromium to let us know the name of the downloaded file...
                // My solution is to create a temporary unique directory to set as the download path for Headless Chromium.
                // After the download, there should be only one file in there.
                // Get the name of that file, and munge it as I see fit.
                $md5OfHREF               = md5( $href );                                        // This should always be unique.
                $absolutePathToStoreFile = $pathToSaveFiles . DIRECTORY_SEPARATOR . $md5OfHREF; // This DIR will end up having one file.

                if ( Storage::exists( $absolutePathToStoreFile ) ):
                    $directoryDeleted = Storage::deleteDirectory( $absolutePathToStoreFile );
                    if ( FALSE == $directoryDeleted ):
                        throw new \Exception( "EXCEPTION: Unable to delete the temp directory: " . $absolutePathToStoreFile );
                    endif;
                endif;

                $directoryCreated = Storage::makeDirectory( $absolutePathToStoreFile );
                if ( FALSE == $directoryCreated ):
                    throw new \Exception( "EXCEPTION: Unable to create the temp directory: " . $absolutePathToStoreFile );
                endif;
                $this->Debug->_debug( "  " . $absolutePathToStoreFile . " was JUST made! Download the file and leave it there!" );


                // SET THE DOWNLOAD PATH
                $this->Debug->_debug( "  Setting download path to our new directory at: " . $absolutePathToStoreFile );
                $page->setDownloadPath( $absolutePathToStoreFile );


                // This downloads a file from $absoluteHREF into $absolutePathToStoreFile
                $absoluteHREF = RemitSpiderUSBank::BASE_URL . $href;
                $this->Debug->_debug( "  Downloading a file from: " . $absoluteHREF );
                $page->navigate( $absoluteHREF );

                $checkCount = 0;
                do {
                    $checkCount++;
                    $this->Debug->_debug( "  Checking for the " . $checkCount . " time." );
                    sleep( 1 );
                    $files = scandir( $absolutePathToStoreFile );
                } while ( count( $files ) < 3 );

                array_shift( $files ); // Remove .
                array_shift( $files ); // Remove ..

                if ( !isset( $files[ 0 ] ) ):
                    throw new \Exception( "  A secured doc was NOT placed in: " . $absolutePathToStoreFile );
                endif;
                $fileName = $files[ 0 ];
                $this->Debug->_debug( "  Done checking. I found the file: " . $fileName );

                $contents = file_get_contents( $absolutePathToStoreFile . DIRECTORY_SEPARATOR . $fileName );


                $bytesWritten = file_put_contents( $pathToSaveFiles . DIRECTORY_SEPARATOR . $finalReportName, $contents );
                Storage::deleteDirectory( $absolutePathToStoreFile );

                if ( FALSE === $bytesWritten ):
                    throw new \Exception( "  Unable to write file to " . $absolutePathToStoreFile );
                else:
                    $this->Debug->_debug( "  " . $bytesWritten . " bytes written into " . $pathToSaveFiles . DIRECTORY_SEPARATOR . $finalReportName );
                endif;

                sleep( 1 );

                $links[ $documentId ] = [
                    self::LABEL_DOCUMENT_ID    => $documentId,
                    self::LABEL_FILE_TYPE      => $fileType,
                    self::LABEL_URL            => $href,
                    self::LABEL_DATE_OF_REPORT => $dateOfReport,
                    self::LABEL_REPORT_NAME    => $tdValues[ self::NAME_INDEX ],
                ];

            } catch ( \Exception $exception ) {
                $this->Debug->_debug( "  EXCEPTION: " . $exception->getMessage() );
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


    /**
     * dealid-date-name-document-id.filetype
     *
     * @param int    $dealId
     * @param string $dateOfReport
     * @param string $dirtyFilename
     * @param int    $documentId
     * @param string $fileType
     *
     * @return string
     */
    protected function _getFinalFileName( int    $dealId,
                                          string $dateOfReport,
                                          string $dirtyFilename,
                                          int    $documentId,
                                          string $fileType ): string {
        $cleanReportName = $this->_getCleanReportName( $dirtyFilename );

        $finalReportName = $dealId . '_' . $dateOfReport . '_' . $cleanReportName . '_' . $documentId . '.' . $fileType;
        $finalReportName = strtolower( $finalReportName );

        return $finalReportName;
    }


    /**
     *
     * @param string $reportName Ex: 8722_CREFC  Bond Level File.csv
     *
     * @return string Ex: 8722_crefc-bond-level-file.csv
     */
    protected function _getCleanReportName( string $reportName ): string {
        // Make it all lowercase.
        $reportName = strtolower( $reportName );

        // Remove tabs and new lines
        $reportName = str_replace( "\t", '', $reportName );
        $reportName = str_replace( "\n", '', $reportName );

        // Replace any multiple spaces with a single space.
        $pattern    = '/\s{2,}/';
        $reportName = preg_replace( $pattern, ' ', $reportName );

        // Any single spaces that are left, replace with a dash.
        $reportName = str_replace( ' ', '-', $reportName );

        return $reportName;
    }


}
