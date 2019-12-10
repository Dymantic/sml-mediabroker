<?php


namespace Dymantic\SmlMediaBroker;


use Dymantic\MultilingualPosts\Post;
use Spatie\MediaLibrary\Models\Media;
use Dymantic\MultilingualPosts\Image;
use Dymantic\MultilingualPosts\MediaBroker;
use Dymantic\MultilingualPosts\ImageConversions;

class SmlMediaBroker implements MediaBroker
{

    public function setTitleImage($post, $file): Image
    {
        $mediaModel = $this->getMediaModel($post->id);
        $mediaModel->clearMediaCollection(Post::TITLE_IMAGES);

        $spatie_image = $mediaModel->addMedia($file)->toMediaCollection(Post::TITLE_IMAGES);

        return new Image($spatie_image->getUrl(), $this->getImageConversions($spatie_image));
    }

    private function getImageConversions(Media $image)
    {
        return ImageConversions::configured()
                               ->filter(function($conversion) use ($image) {
                                   return in_array($image->collection_name, $conversion->collections);
                               })
                               ->flatMap(function($conversion) use ($image) {
                                   return [$conversion->name => $this->attemptToGetConversionName($image, $conversion->name)];
                               })->all();
    }

    private function attemptToGetConversionName($image, $conversion)
    {
        $src = "";
        try {
            $src = $image->getUrl($conversion);
        } catch (\Exception $e) {}

        return $src;
    }

    public function titleImage($post): Image
    {
        $mediaModel = $this->getMediaModel($post->id);
        $image = $mediaModel->getFirstMedia(Post::TITLE_IMAGES);

        if(!$image) {
            return new Image();
        }

        return new Image($image->getUrl(), $this->getImageConversions($image));
    }

    public function attachImage($post, $file): Image
    {
        $mediaModel = $this->getMediaModel($post->id);

        $image = $mediaModel->addMedia($file)->toMediaCollection(Post::BODY_IMAGES);

        return new Image($image->getUrl(), $this->getImageConversions($image));
    }

    private function getMediaModel($post_id)
    {
        return MediaModel::firstOrCreate(['post_id' => $post_id]);
    }
}