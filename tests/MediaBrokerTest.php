<?php


namespace Dymantic\SmlMediaBroker\Tests;


use Dymantic\MultilingualPosts\Image;
use Dymantic\MultilingualPosts\Post;
use Dymantic\SmlMediaBroker\SmlMediaBroker;
use Dymantic\SmlMediaBroker\MediaModel;
use Dymantic\SmlMediaBroker\Tests\TestCase;
use Illuminate\Http\UploadedFile;

class MediaLibraryMediaBrokerTest extends TestCase
{
    /**
     *@test
     */
    public function it_sets_a_title_image()
    {
        $broker = new SmlMediaBroker();
        $post = Post::create(['title' => 'test post']);

        $image = $broker->setTitleImage($post, UploadedFile::fake()->image('testpic.png'));

        $mediaModel = MediaModel::where('post_id', $post->id)->first();

        $this->assertCount(1, $mediaModel->getMedia(Post::TITLE_IMAGES));
        $title_image = $mediaModel->getFirstMedia(Post::TITLE_IMAGES);

        $this->assertEquals($title_image->getUrl(), $image->src);
    }

    /**
     *@test
     */
    public function setting_a_title_image_overwrites_any_previous_one()
    {
        $broker = new SmlMediaBroker();
        $post = Post::create(['title' => 'test post']);

        $broker->setTitleImage($post, UploadedFile::fake()->image('testpic.png'));

        $mediaModel = MediaModel::where('post_id', $post->id)->first();
        $this->assertCount(1, $mediaModel->getMedia(Post::TITLE_IMAGES));

        $image = $broker->setTitleImage($post, UploadedFile::fake()->image('testpic_two.png'));
        $this->assertCount(1, $mediaModel->fresh()->getMedia(Post::TITLE_IMAGES));

    }

    /**
     *@test
     */
    public function standard_conversions_are_generated_if_no_config()
    {
        config(['multilingual-posts.conversions' => null]);

        $post = Post::create(['title' => 'test post']);
        $broker = new SmlMediaBroker();
        $image = $broker->setTitleImage($post, UploadedFile::fake()->image('testpic.png', 3000, 2000));

        $mediaModel = MediaModel::where('post_id', $post->id)->first();
        $this->assertCount(1, $mediaModel->getMedia(Post::TITLE_IMAGES));
        $title_image = $mediaModel->getFirstMedia(Post::TITLE_IMAGES);

        $this->assertTrue($title_image->hasGeneratedConversion('web'), 'web conversion not generated');
        $this->assertTrue($title_image->hasGeneratedConversion('thumb'), 'thumb conversion not generated');
        $this->assertTrue($title_image->hasGeneratedConversion('banner'), 'banner conversion not generated');

        $thumbSize = getimagesize($title_image->getPath('thumb'));
        $this->assertEquals(400, $thumbSize[0]);
        $this->assertEquals(300, $thumbSize[1]);

        $webSize = getimagesize($title_image->getPath('web'));
        $this->assertEquals(800, $webSize[0]);
        $this->assertEquals(533, $webSize[1]);

        $bannerSize = getimagesize($title_image->getPath('banner'));
        $this->assertEquals(1400, $bannerSize[0]);
        $this->assertEquals(933, $bannerSize[1]);


        $this->assertEquals($title_image->getUrl('web'), $image->getUrl('web'));
        $this->assertEquals($title_image->getUrl('thumb'), $image->getUrl('thumb'));
        $this->assertEquals($title_image->getUrl('banner'), $image->getUrl('banner'));
    }

    /**
     *@test
     */
    public function conversions_can_be_generated_from_config()
    {
        config(['multilingual-posts.conversions' => [
            ['name' => 'thumb', 'manipulation' => 'crop', 'width' => 300, 'height' => 200, 'title' => true, 'post' => false],
            ['name' => 'web', 'manipulation' => 'fit', 'width' => 1600, 'height' => 1000, 'title' => true, 'post' => false],
            ['name' => 'banner', 'manipulation' => 'crop', 'width' => 2000, 'height' => 1000, 'title' => true, 'post' => false],
        ]]);

        $post = Post::create(['title' => 'test title']);
        $broker = new SmlMediaBroker();
        $broker->setTitleImage($post, UploadedFile::fake()->image('testpic.png', 3000, 2000));

        $mediaModel = MediaModel::where('post_id', $post->id)->first();
        $this->assertCount(1, $mediaModel->getMedia(Post::TITLE_IMAGES));
        $image = $mediaModel->getFirstMedia(Post::TITLE_IMAGES);

        $this->assertTrue($image->hasGeneratedConversion('web'), 'web conversion not generated');
        $this->assertTrue($image->hasGeneratedConversion('thumb'), 'thumb conversion not generated');
        $this->assertTrue($image->hasGeneratedConversion('banner'), 'banner conversion not generated');

        $thumbSize = getimagesize($image->getPath('thumb'));
        $this->assertEquals(300, $thumbSize[0]);
        $this->assertEquals(200, $thumbSize[1]);

        $webSize = getimagesize($image->getPath('web'));
        $this->assertEquals(1500, $webSize[0]);
        $this->assertEquals(1000, $webSize[1]);

        $bannerSize = getimagesize($image->getPath('banner'));
        $this->assertEquals(2000, $bannerSize[0]);
        $this->assertEquals(1000, $bannerSize[1]);
    }

    /**
     *@test
     */
    public function can_fetch_current_title_image()
    {
        $post = Post::create(['title' => 'test title']);
        $broker = new SmlMediaBroker();
        $broker->setTitleImage($post, UploadedFile::fake()->image('testpic.png'));

        $title_image = $broker->titleImage($post);

        $mediaModel = MediaModel::where('post_id', $post->id)->first();
        $this->assertCount(1, $mediaModel->getMedia(Post::TITLE_IMAGES));
        $image = $mediaModel->getFirstMedia(Post::TITLE_IMAGES);

        $this->assertInstanceOf(Image::class, $title_image);
        $this->assertEquals($image->getUrl(), $title_image->src);
        $this->assertEquals($image->getUrl('web'), $title_image->getUrl('web'));
        $this->assertEquals($image->getUrl('thumb'), $title_image->getUrl('thumb'));
        $this->assertEquals($image->getUrl('banner'), $title_image->getUrl('banner'));
    }

    /**
     *@test
     */
    public function attach_body_images()
    {
        $post = Post::create(['title' => 'test title']);
        $broker = new SmlMediaBroker();

        $image = $broker->attachImage($post, UploadedFile::fake()->image('testpic.png'));

        $mediaModel = MediaModel::where('post_id', $post->id)->first();
        $this->assertCount(1, $mediaModel->getMedia(Post::BODY_IMAGES));
        $spatie_image = $mediaModel->getFirstMedia(Post::BODY_IMAGES);

        $this->assertEquals($spatie_image->getUrl(), $image->src);
    }

    /**
     *@test
     */
    public function standard_body_conversions_are_generated_if_no_config()
    {
        config(['multilingual-posts.conversions' => null]);

        $post = Post::create(['title' => 'test title']);
        $broker = new SmlMediaBroker();
        $image = $broker->attachImage($post, UploadedFile::fake()->image('testpic.png', 3000, 2000));

        $mediaModel = MediaModel::where('post_id', $post->id)->first();
        $this->assertCount(1, $mediaModel->getMedia(Post::BODY_IMAGES));
        $spatie_image = $mediaModel->getFirstMedia(Post::BODY_IMAGES);

        $this->assertTrue($spatie_image->hasGeneratedConversion('web'), 'web conversion not generated');
        $this->assertTrue($spatie_image->hasGeneratedConversion('thumb'), 'thumb conversion not generated');

        $thumbSize = getimagesize($spatie_image->getPath('thumb'));
        $this->assertEquals(400, $thumbSize[0]);
        $this->assertEquals(300, $thumbSize[1]);

        $webSize = getimagesize($spatie_image->getPath('web'));
        $this->assertEquals(800, $webSize[0]);
        $this->assertEquals(533, $webSize[1]);

    }

    /**
     *@test
     */
    public function body_conversions_can_be_generated_from_config()
    {
        config(['multilingual-posts.conversions' => [
            ['name' => 'web', 'manipulation' => 'crop', 'width' => 1600, 'height' => 1000, 'title' => false, 'post' => true],
            ['name' => 'thumb', 'manipulation' => 'fit', 'width' => 400, 'height' => 400, 'title' => false, 'post' => true],
        ]]);

        $post = Post::create(['title' => 'test title']);
        $broker = new SmlMediaBroker();
        $image = $broker->attachImage($post, UploadedFile::fake()->image('testpic.png', 3000, 2000));

        $mediaModel = MediaModel::where('post_id', $post->id)->first();
        $this->assertCount(1, $mediaModel->getMedia(Post::BODY_IMAGES));
        $spatie_image = $mediaModel->getFirstMedia(Post::BODY_IMAGES);

        $this->assertTrue($spatie_image->hasGeneratedConversion('web'), 'web conversion not generated');
        $this->assertTrue($spatie_image->hasGeneratedConversion('thumb'), 'thumb conversion not generated');

        $webSize = getimagesize($spatie_image->getPath('web'));

        $this->assertEquals(1600, $webSize[0]);
        $this->assertEquals(1000, $webSize[1]);

        $thumbSize = getimagesize($spatie_image->getPath('thumb'));

        $this->assertEquals(400, $thumbSize[0]);
        $this->assertEquals(267, $thumbSize[1]);

        $this->assertInstanceOf(Image::class, $image);
        $this->assertEquals($spatie_image->getUrl(), $image->src);
        $this->assertEquals($spatie_image->getUrl('web'), $image->getUrl('web'));
        $this->assertEquals($spatie_image->getUrl('thumb'), $image->getUrl('thumb'));
    }
}