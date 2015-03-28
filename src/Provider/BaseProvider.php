<?php
/**
 * @author   Jokūbas Ramanauskas
 *
 * @since    2015-03-15
 */

namespace Provider;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Class BaseProvider.
 */
abstract class BaseProvider
{
    /**
     * @param $url
     *
     * @return bool|Crawler
     */
    protected function getData($url)
    {
        $content = $this->fetchData($url);
        if (false !== $content) {
            $crawler = $this->getDomCrawler($content);

            return $crawler;
        } else {
            return false;
        }
    }

    /**
     * @param string $content HTML document
     *
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function getDomCrawler($content)
    {
        $crawler = new Crawler($content);

        return $crawler;
    }

    /**
     * @param $url
     *
     * @return bool|string HTML document
     */
    protected function fetchData($url)
    {
        try {
            $content = file_get_contents($url);
        }
        catch (\Exception $ex) {
            return false;
        }

        return $content;
    }
}
