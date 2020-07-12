<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;

class test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:site';
    protected $url = 'https://www.mixtcar.ru';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $crawler = new Crawler(file_get_contents($this->url));//Весь контент
        $categories = $this->getCategories($crawler);
        $products = $this->getProducts($categories);
    }


    public function getCategories($crawler)
    {
        $categories = [];
        $secCategories = [];
        $thirdCategories = [];
        $crawler->filter('.block__list.list--first .block__list--item.item--drop--first')->each(function ($node, $i) use (&$categories, &$secCategories, &$thirdCategories) {

            if ($i > 4) {
                return $node;
            }
            $link = $node->filter('a.item__link.link--drop--first');
            $temp['link'] = $link->attr('href');

            $secCategories = $this->getSecondCategories($node);
            $temp['sec'] = $secCategories;
            $categories[$link->text()]= $temp;

            return $node;

        });
        //dd($categories);
        return $categories;
    }

    public function getSecondCategories($node)
    {
        $secCategories = [];
        $thirdCategories = [];

        $node->filter('.block__list.list--second .block__list--item.item--drop--second')->each(function ($node) use (&$secCategories, &$thirdCategories) {
            $link = $node->filter('a.item__link.link--drop--second');
            $temp[$link->text()] = $link->attr('href');
            if ($node->filter('.block__list.list--third .block__list--item')->count()) {
                $thirdCategories = $this->getThirdCategories($node);

            } else {
                $thirdCategories[$link->text()] = $link->attr('href');
            }
            $temp['third'] = $thirdCategories;
            $secCategories[]= $temp;

        });


        return $secCategories;

    }

    public function getThirdCategories($node)
    {
        $thirdCategories = [];


        $node->filter('.block__list.list--third .block__list--item')->each(function ($node) use (&$thirdCategories) {
            $link = $node->filter('a.item__link');
            $thirdCategories[$link->text()] = $link->attr('href');
        });

        //dd($thirdCategories);
        return $thirdCategories;
    }
//$categories[
//    $secCatigories[
//        $thirdCategories[
//            ],]
//    ]
    public function getProducts($categories){
        foreach($categories as $category ){
            foreach($category['sec'] as $secCategory){
                foreach($secCategory['third'] as $thirdCategory => $link){
                    $crawler = new Crawler(file_get_contents($this->url . $link));

                    $lastPage =$crawler->filter('.sort-b__paging-list')->first()->filter('.sort-b__paging-item')->last();

                    $lastPageLink = $lastPage->filter('a.sort-b__paging-links');

                    if(!$lastPageLink->count()) continue;

                    $lastPageLinkNum = (int)$lastPageLink->text();


                    $lastPageLink = $lastPageLink->attr('href');
                    $pageLink = explode('PAGEN_1=', $lastPageLink)[0] . 'PAGEN_1=';

                    $products[] = getDesc($crawler);

                    for($i=2; $i<=$lastPageLinkNum; $i++){
                        $crawler = new Crawler(file_get_contents($this->url . $pageLink . $i));
                        //$this->info($i);

                        $products[] = getDesc($crawler);


//
                    }

                }
            }

        }
    }

    public function getDesc($crawler){

        $node = $crawler->filter('.pruduct_grid_item .product_inner_wrap');

        $title = $node->filter('a.pruduct_grid_title')->text();

        $desc = $node->filter('.pruduct_grid_data');

        $stoks = $desc->filter('.store')->text();

        $brand = $desc->filter('.brand')->text();

        $code = $desc->filter('.code')->text();

       return $output = [
            'title' => $title,
            'stoks' => $stoks,
            'brand' => $brand,
            'code' => $code,
        ];
    }



//    public function fetchCategories($node,$link,$list,$item){
//
//        $categories=[];
//
//        $node->filter($list.' ')
//    }

}
