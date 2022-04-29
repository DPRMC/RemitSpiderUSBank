<?php

namespace DPRMC\RemitSpiderUSBank\Helpers;

use Carbon\Carbon;
use HeadlessChromium\Page;

/**
 * There is a lot of common code among every piece of data retrieved from US Bank.
 */
abstract class BaseData {
    protected Page  $Page;
    protected Debug $Debug;

    protected Carbon $startTime;
    protected Carbon $stopTime;
    protected string $lastRunStatus;

    protected string $timezone;

    protected string $pathToCache;

    protected array $data;

    const META            = 'meta';
    const DATA            = 'data';
    const START_TIME      = 'startTime';
    const STOP_TIME       = 'stopTime';
    const LAST_RUN_STATUS = 'lastRunStatus';

    /**
     * @return void
     */
    public function loadFromCache() {
        $stringCache = file_get_contents( $this->pathToCache );
        $arrayCache  = json_decode( $stringCache, TRUE );

        $this->data          = $arrayCache[ self::DATA ];
        $this->startTime     = unserialize( $arrayCache[ self::META ][ self::START_TIME ] );
        $this->stopTime      = unserialize( $arrayCache[ self::META ][ self::STOP_TIME ] );
        $this->lastRunStatus = $arrayCache[ self::META ][ self::LAST_RUN_STATUS ];
    }


    /**
     * @param array $data
     *
     * @return void
     * @throws \Exception
     */
    protected function _cacheData(array $data){
        $dataToWrite = [
            self::META => [
                self::START_TIME      => serialize( $this->startTime ),
                self::STOP_TIME       => serialize( $this->stopTime ),
                self::LAST_RUN_STATUS => 'ok',
            ],
            self::DATA => [
                $data,
            ],
        ];

        $writeSuccess = file_put_contents( $this->pathToCache, json_encode( $dataToWrite ) );
        if ( FALSE === $writeSuccess ):
            throw new \Exception( "Unable to write US Bank Portfolio IDs to cache file: " . $this->pathToCache );
        endif;
    }


    /**
     * When you catch an exception during the run of this class, record the failure in cache.
     *
     * @param \Exception $exception
     *
     * @return void
     * @throws \Exception
     */
    protected function _cacheFailure( \Exception $exception ): void {

        $stringCache = file_get_contents( $this->pathToCache );
        $arrayCache  = json_decode( $stringCache, TRUE );

        $arrayCache[ self::META ] = [
            self::START_TIME      => serialize( $this->startTime ),
            self::STOP_TIME       => serialize( $this->stopTime ),
            self::LAST_RUN_STATUS => $exception->getMessage(),
        ];

        $writeSuccess = file_put_contents( $this->pathToCache, json_encode( $arrayCache ) );
        if ( FALSE === $writeSuccess ):
            throw new \Exception( "Unable to write US Bank data to cache file: " . $this->pathToCache );
        endif;
    }


}