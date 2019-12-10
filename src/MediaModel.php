<?php


namespace Dymantic\SmlMediaBroker;


use Dymantic\MultilingualPosts\ImageConversions;
use Dymantic\MultilingualPosts\PostImageConversion;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\MediaLibrary\Models\Media;

class MediaModel extends Model implements HasMedia
{
    use HasMediaTrait;

    protected $table = 'multilingual_posts_media_models';

    protected $fillable = ['post_id'];

    public function registerMediaConversions(Media $media = null)
    {
        ImageConversions::configured()->each(function ($conversion) {
            $this->addConversion($conversion);
        });
    }

    private function addConversion(PostImageConversion $conversion)
    {
        $manipulations = [
            'crop' => Manipulations::FIT_CROP,
            'max' => Manipulations::FIT_MAX,
        ];
        $manipulation = $manipulations[$conversion->manipulation] ?? Manipulations::FIT_MAX;

        if($conversion->optimize) {
            return $this->addMediaConversion($conversion->name)
                        ->fit($manipulation, $conversion->width, $conversion->height)
                        ->optimize()
                        ->performOnCollections(...$conversion->collections);
        }

        return $this->addMediaConversion($conversion->name)
                    ->fit($manipulation, $conversion->width, $conversion->height)
                    ->performOnCollections(...$conversion->collections);
    }

    private function defaultConversions()
    {
        return [
            ['name'         => 'thumb',
             'manipulation' => 'crop',
             'width'        => 400,
             'height'       => 300,
             'title'        => true,
             'post'         => true
            ],
            ['name'         => 'web',
             'manipulation' => 'fit',
             'width'        => 800,
             'height'       => 1800,
             'title'        => true,
             'post'         => true
            ],
            ['name'         => 'banner',
             'manipulation' => 'fit',
             'width'        => 1400,
             'height'       => 1000,
             'title'        => true,
             'post'         => false
            ],
        ];
    }
}