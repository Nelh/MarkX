<?php

namespace App\Spiders;

use DateTime;
use Generator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use RoachPHP\Downloader\Middleware\RequestDeduplicationMiddleware;
use RoachPHP\Extensions\LoggerExtension;
use RoachPHP\Extensions\StatsCollectorExtension;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;
use RoachPHP\Spider\ParseResult;
use App\Models\News;
use Symfony\Component\DomCrawler\Crawler;
use Carbon\Carbon;

class NewsSpider extends BasicSpider
{
    public array $startUrls = [
        'https://www.washingtonpost.com/latest-headlines'
    ];

    public array $downloaderMiddleware = [
        RequestDeduplicationMiddleware::class,
    ];

    public array $spiderMiddleware = [
        //
    ];

    public array $itemProcessors = [
        //
    ];

    public array $extensions = [
        LoggerExtension::class,
        StatsCollectorExtension::class,
    ];

    public int $concurrency = 2;

    public int $requestDelay = 1;

    /**
     * @param Response $response
     * @return Generator<ParseResult>
     */
    public function parse(Response $response): Generator
    {
        $nodeValues = $response->filter('.chain .table0 .card')->each(function (Crawler $node, $i) {
            $title = $node->filter('.left .card-left .headline h2 a')->count() ? $node->filter('.left .card-left .headline h2 a')->text("") : "";
            $description = $node->filter('.left .card-left .lh-fronts-tiny a')->count() ? $node->filter('.left .card-left .lh-fronts-tiny a')->text("") : "";
            $url = $node->filter('.left .card-left a')->count() ? $node->filter('.left .card-left a')->link()->getUri() : "";
            $image_url = $node->filter('.left .card-right .relative .dib a img')->count() ? $node->filter('.left .card-right .relative .dib a img')->image()->getUri() : "";
            $snippet = $node->filter('.left .card-left .label-kicker a')->count() ? $node->filter('.left .card-left .label-kicker a')->text("") : "";
            $published_at = $this->convertDate($node->filter('.left .card-left .byline .timestamp')->count() ? $node->filter('.left .card-left .byline .timestamp')->text("") : "");


            return [
                'title' => $title,
                'identified' => Str::limit(Str::slug($title, "-"), 20, ''),
                'description' => $description,
                'url' => $url,
                'image_url' => $image_url,
                'snippet' => $snippet,
                'published_at' => $published_at,
                'source' => 'Washington post'
            ];
        });

        $nodeValues = array_reverse(array_values($nodeValues));

        try {
            foreach($nodeValues as $value) {
                $record = News::query()->where('identified', '=', $value['identified'])->first();
                if($record === null) {
                    News::query()->create($value);
                }
            }

        } catch (QueryException $e) {
            \Log::error('Error saving data: ' . $e->getMessage());
        }

        yield $this->item($nodeValues);
    }


    public function convertDate(string $time): string
    {
        if (str_contains($time, 'Updated')) {
            $time = str_replace("Updated", "", $time);
        }
        return date('Y-m-d H:i:s', strtotime($time));
    }
}
