<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;

class ParseCmd extends Command
{
    protected $signature = 'parse:cats';

    protected $url = 'https://www.mixtcar.ru';

    protected $description = 'Command description';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $crawler = new Crawler(file_get_contents($this->url));
        $categoryData = $this->getCategories($crawler);
        //$products = $this->fetchProducts(array_slice($categoryData['thirdCategories'],0,3));
        dd($categoryData['categories']);
    }

    public function getCategories($crawler)
    {

        // $prepareStr = fn($node) => str_replace("\n", '', $node->text());
        $prepareStr = function ($node) {
            return str_replace("\n", '', $node->text());
            //return $node->text();
        };
        $categories = [];
        $subCategories = [];
        $thirdCategories = [];
        $crawler->filter('.block__list.list--first .block__list--item.item--drop--first')->each(function (Crawler $node, $i) use (&$categories, &$subCategories, &$thirdCategories, $prepareStr) {
            if ($i > 4) {
                return $node;
            }
            $link = $node->filter('a.link--drop--first');

            $categories[$prepareStr($link)] = $link->attr('href');
            $subCatList = $node->filter('.block__list.list--second .block__list--item.item--drop--second');
            $subCatList->each(function (Crawler $node, $i) use (&$subCategories, &$thirdCategories, $prepareStr) {
                $link = $node->filter('.link--drop--second');
                //dd($link->text());
                $subCategories[$prepareStr($link)] = $link->attr('href');
                $thirdCatList = $node->filter('.block__list.list--third');
                if (!$thirdCatList) {
                    $thirdCatList[$prepareStr($link)] = $link->attr('href');
                } else {
                    $thirdCatList->filter('li.block__list--item')->each(function (Crawler $node, $i) use (&$thirdCategories, $prepareStr) {
                        $link = $node->filter('a.item__link');
                        $thirdCategories[$prepareStr($link)] = $link->attr('href');
                    });
                }
            });
            return $node;
        });
        dd($categories);
        return [
            'categories' => $categories,
            'subCategories' => $subCategories,
            'thirdCategories' => $thirdCategories
        ];
    }

    public function fetchProducts($categories)
    {

        $products = [];
        //$prepareStr = fn($node) => str_replace("\n", '', $node);
        $prepareStr = function ($node) {
            return str_replace("\n", '', $node);
        };
        $checkOrEmpty = function ($node) {
            return $node->count() ? $node->first()->text() : '';
        };
        $splitHashOrEmpty = function ($node) {
            return $node && $node->count() ? explode(': ', $node->first()->text())[1] : '';
        };
        $paginateLastPage = function ($crawler) {
            return $crawler->filter('.sort-b__paging-list')->first()->filter('.sort-b__paging-item')->last();
        };

        $fetchProducts = function ($crawler)use ($prepareStr, $checkOrEmpty, $splitHashOrEmpty) {
            return $crawler->filter('.product_inner_wrap')->each(function (Crawler $node) use ($prepareStr, $checkOrEmpty, $splitHashOrEmpty) {
                return [
                    'title' => $prepareStr($checkOrEmpty($node->filter('a.pruduct_grid_title'))),
                    'exists' => $prepareStr($checkOrEmpty($node->filter('.pruduct_grid_data .store'))),
                    'brand' => $prepareStr($splitHashOrEmpty($node->filter('.brand'))),
                    'code' => $prepareStr($splitHashOrEmpty($node->filter('.code'))),
                    'cost' => $prepareStr($checkOrEmpty($node->filter('.pruduct_grid_btn_wrap .product_rice .product_rice_val')))
                ];
            });
        };
        foreach ($categories as $category => $link) {
            $crawler = new Crawler(file_get_contents($this->url . $link));
            $lastPage = $paginateLastPage($crawler);
            $lastPageLink = $lastPage->filter('a.sort-b__paging-links');
            $products[] = $fetchProducts($crawler);

            if (!$lastPageLink->count()) continue;
            $lastPageNum = (int)$lastPageLink->text();
            $lastPageLink = $lastPageLink->attr('href');
            $pageLink = explode('PAGEN_1=', $lastPageLink)[0] . 'PAGEN_1=';
            for ($i = 2; $i <= $lastPageNum; $i++) {
                $crawler = new Crawler(file_get_contents($this->url . $pageLink . $i));
                $products[] = $fetchProducts($crawler);
                $this->info($i);
            }
        }

        $output = [];
        foreach ($products as $product) {
            array_push($output, ...$product);
        }

        return $output;
    }
}
