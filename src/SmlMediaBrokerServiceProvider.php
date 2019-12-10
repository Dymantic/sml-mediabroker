<?php


namespace Dymantic\SmlMediaBroker;


use Illuminate\Support\ServiceProvider;

class SmlMediaBrokerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (! class_exists('CreateMultilingualPostsMediaModelsTable')) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_multilingual_posts_media_models_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_multilingual_posts_media_models_table.php'),
            ], 'migrations');
        }
    }

    public function register()
    {

    }
}