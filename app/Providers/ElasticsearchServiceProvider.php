<?php

namespace App\Providers;

use Elastic\Elasticsearch\{Client, ClientBuilder};
use Illuminate\Support\ServiceProvider;

class ElasticsearchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $hosts = [];
        foreach (config('elasticsearch.hosts') as $host) {
            $hosts[] = $host['host'] . ':' . $host['port'];
        }

        $this->app->singleton(Client::class, function ($app) use ($hosts) {
            return ClientBuilder::create()
                ->setHosts($hosts)
                ->setBasicAuthentication(config('elasticsearch.user'), config('elasticsearch.password'))
                ->build();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
