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
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function getData($url)
    {
        $content = $this->fetchData($url);
        dump($url);
        $crawler = $this->getDomCrawler($content);

        return $crawler;
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
     * @return string HTML document
     */
    protected function fetchData($url)
    {
        $content = file_get_contents($url);

        return $content;
    }
}
