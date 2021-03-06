<?php

namespace DPRMC\RemitSpiderUSBank\Helpers;


use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;

class Cache {


    /**
     * @param \DPRMC\RemitSpiderUSBank\RemitSpiderUSBank $spider
     *
     * @return array
     */
    public static function getDealIdsFromFiles(RemitSpiderUSBank $spider): array {
        $dealIds = [];
        $spider->FileIndex->loadFromCache();
        $files = $spider->FileIndex->getObjects();
        /**
         * @var \DPRMC\RemitSpiderUSBank\Objects\File $file
         */
        foreach($files as $file):
            $dealIds[] = $file->getDealId();
        endforeach;
        return $dealIds;
    }


    /**
     * @param \DPRMC\RemitSpiderUSBank\RemitSpiderUSBank $spider
     *
     * @return array
     */
    public static function getDealIdsFromHistoryLinks(RemitSpiderUSBank $spider): array {
        $dealIds = [];
        $spider->HistoryLinks->loadFromCache();
        $historyLinksByDeal = $spider->HistoryLinks->getObjects();

        return array_keys($historyLinksByDeal);
    }


    /**
     * @param \DPRMC\RemitSpiderUSBank\RemitSpiderUSBank $spider
     *
     * @return array
     */
    public static function getDealIdsFromDeals(RemitSpiderUSBank $spider): array {
        $dealIds = [];
        $spider->Deals->loadFromCache();
        $deals = $spider->Deals->getObjects();
        /**
         * @var \DPRMC\RemitSpiderUSBank\Objects\Deal $deal
         */
        foreach($deals as $deal):
            $dealIds[] = $deal->getDealId();
        endforeach;
        return $dealIds;
    }


    /**
     * @param \DPRMC\RemitSpiderUSBank\RemitSpiderUSBank $spider
     *
     * @return string
     */
    public static function getFileCacheAsString(RemitSpiderUSBank $spider): string {
        $pathToCache = $spider->FileIndex->getPathToCache();
        return file_get_contents($pathToCache);
    }


    /**
     * @param \DPRMC\RemitSpiderUSBank\RemitSpiderUSBank $spider
     *
     * @return string
     */
    public static function getPortfolioCacheAsString(RemitSpiderUSBank $spider): string {
        $pathToCache = $spider->Portfolios->getPathToCache();
        return file_get_contents($pathToCache);
    }


    /**
     * @param \DPRMC\RemitSpiderUSBank\RemitSpiderUSBank $spider
     *
     * @return string
     */
    public static function getDealCacheAsString(RemitSpiderUSBank $spider): string {
        $pathToCache = $spider->Deals->getPathToCache();
        return file_get_contents($pathToCache);
    }


    /**
     * @param \DPRMC\RemitSpiderUSBank\RemitSpiderUSBank $spider
     *
     * @return string
     */
    public static function getHistoryLinkCacheAsString(RemitSpiderUSBank $spider): string {
        $pathToCache = $spider->HistoryLinks->getPathToCache();
        return file_get_contents($pathToCache);
    }

}