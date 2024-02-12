<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\Concerns\InteractsWithExceptionHandling;
use Tests\TestCase;
use App\Models\{Language, Post, PostTag, PostTranslation, Tag};
use Illuminate\Support\Str;

class PostsCrudTest extends TestCase
{
    use InteractsWithExceptionHandling;

    protected static $wasSetup = false;

    public function setUp(): void
    {
        parent::setUp();
        if (! self::$wasSetup) {
            // Regenerate structure / fresh data only once at first test
            Artisan::call(' migrate:fresh --seed');
            Artisan::call('config:clear');
            $databaseName = \DB::connection()->getDatabaseName();
            $result = Str::endsWith($databaseName, 'HttpTesting');
            if (! $result) {  // Check valid database for tests
                die('Invalid database "' . $databaseName . '" connected ');
            }
            self::$wasSetup = true;
        }
    } // public function setUp(): void

    /**
     * 1) Create post translation fields from factory, save 2 models in storage, Search post from storage by title field and compare title of post
     */
    public function test_1_PostIsAdded()
    {
        // Test Data Setup
        $faker = \Faker\Factory::create();
        $language = $faker->randomElement(Language::all());

        $postTranslationModel = PostTranslation  // model only in memory
        ::factory()
            ->languagePrefix($language->prefix)->make([
                'language_id' => $language->id,
            ]);

        // Test Action
        $response = $this
            ->postJson(route('posts.store'), $postTranslationModel->toArray());
        $response
            ->assertStatus(HTTP_RESPONSE_OK_RESOURCE_CREATED); // 201

        // READ POST CREATED ABOVE
        $insertedPostTranslation = PostTranslation::getBySearch(search: $postTranslationModel->title, partial: false)
            ->getByLanguageId($language->id)
            ->first();

        // Check Assert
        $this->assertNotNull($insertedPostTranslation, '11 : Inserted post not found');
        $this->assertEquals(
            $insertedPostTranslation->title,
            $postTranslationModel->title,
            '12 : Title read is not equal title on insert'
        );
    } // 1: testPostIsAdded()

    /**
     * 2) Create post from factory, save model in storage, Update model, Search post from storage by title field and compare title of post
     */
    public function test_2_PostIsUpdated()
    {
        // Test Data Setup
        $faker = \Faker\Factory::create();
        $language = $faker->randomElement(Language::all());

        // Create a new Post for testing
        $post = Post::create(['created_at' => Carbon::now(config('app.timezone'))]);
        $postTranslationModel = PostTranslation::factory()
            ->languagePrefix($language->prefix)->create([
                'language_id' => $language->id,
                'post_id' => $post->id,
            ]);

        // Create a new tag for testing for related PostTag
        $tag = Tag::create(['name' => 'Test Tag ' . $faker->text(20)]);
        PostTag::factory()->create([
                'tag_id' => $tag->id,
                'post_id' => $post->id,
            ]);

        // Test Action
        $response = $this
            ->putJson(route('posts.update', $post->id), [
                'language_id' => $language->id,
                'post_id' => $post->id,
                'title' => $postTranslationModel->title . ' UPDATED',
                'description' => $postTranslationModel->description . ' UPDATED description ',
                'content' => $postTranslationModel->content . ' UPDATED content ',
            ]);

        // Check Assert
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_UPDATED);  // 205
        $post->load('postTranslations.language');
        $post->load('postTags.tag');

        // READ POST CREATED ABOVE
        $updatedPost = PostTranslation::getBySearch(search: $postTranslationModel->title . ' UPDATED', partial: false)
            ->getByPostId($post->id)
            ->getByLanguageId($language->id)
            ->first();
        $this->assertNotNull($updatedPost, '21 : updated post not found');
        $this->assertEquals(
            $updatedPost->title,
            $post->postTranslations[0]['title'],
            '22 : title read is not equal title on update'
        );
    }
    // 2: PostIsUpdated()

    /**
     * Create post from factory, save model in storage, Try to update model with negative ID - Must return unprocessable_entity response
     */
    public function test_3_NegativePostFailuredBeUpdatedAsNotFound()
    {
        // Test Data Setup
        $faker = \Faker\Factory::create();
        $language = $faker->randomElement(Language::all());

        // Create a new Post for testing
        $post = Post::create(['created_at' => Carbon::now(config('app.timezone'))]);
        $postTranslationModel = PostTranslation::factory()
            ->languagePrefix($language->prefix)->create([
                'language_id' => $language->id,
                'post_id' => $post->id,
            ]);

        // Test Action
        $response = $this
            ->putJson(route('posts.update', -$postTranslationModel->post_id), [
                'language_id' => $language->id,
                'title' => $postTranslationModel->title . ' UPDATED',
                'content' => ' content text ',
                'description' => ' description text ',
            ]);

        // Check Assert
        $response->assertStatus(HTTP_RESPONSE_UNPROCESSABLE_ENTITY);  // 422
    }
    // 3: testNegativePostFailuredBeUpdatedAsNotFound()

    /**
     * 4) Create post from factory, save model in storage, delete post - Must return not found response
     */
    public function test_4_PostIsDestroyed()
    {
        // Test Data Setup
        $faker = \Faker\Factory::create();
        $language = $faker->randomElement(Language::all());

        // Create a new Post for testing
        $post = Post::create(['created_at' => Carbon::now(config('app.timezone'))]);
        PostTranslation::factory()
            ->languagePrefix($language->prefix)->create([
                'language_id' => $language->id,
                'post_id' => $post->id,
            ]);

        // Test Action
        $response = $this
            ->delete(route('posts.destroy', $post->id), []);

        // Check Assert
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_DELETED);  // 204

        $response = $this
            ->get(route('posts.show', $post->id));
        $response->assertStatus(HTTP_RESPONSE_NOT_FOUND);  // 404

    }
    // 4: testPostIsDestroyed()

    /**
     * 5) Create post from factory, save model in storage, delete post, restore_post
     */
    public function test_5_PostIsDestroyedAndRestored()
    {
        // Test Data Setup
        $post = Post::factory()->create([]);

        $post->delete();

        // Test Action
        $response = $this
            ->putJson(route('posts.restore', $post->id), []);

        // Check Assert
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_UPDATED);  // 204
    } // 5: testPostIsDestroyedAndRestored()

    /**
     * 6) Create post from factory, save model in storage, Try to delete model with negative ID - Must return not found response
     */
    public function test_6_NegativePostIsDestroyedAsNotFound()
    {
        // Test Data Setup
        $faker = \Faker\Factory::create();
        $language = $faker->randomElement(Language::all());

        // Create a new Post for testing
        $post = Post::create(['created_at' => Carbon::now(config('app.timezone'))]);
        $postTranslationModel = PostTranslation::factory()
            ->languagePrefix($language->prefix)->create([
                'language_id' => $language->id,
                'post_id' => $post->id,
            ]);

        // Test Action
        $response = $this
            ->delete(route('posts.destroy', -$postTranslationModel->id), []);

        // Check Assert
        $response->assertStatus(HTTP_RESPONSE_NOT_FOUND);  // 404
    } // 6: testPostIsDestroyed()

    /**
     * 7) Create post from factory, save model in storage, Try to read/check they area found by title filters
     */
    public function test_7_FiltersWithLocale()
    {
        // Test Data Setup
        $faker = \Faker\Factory::create();
        $postSearch = 'Test Post ' . $faker->name . ' Lorem Value';

        $language = $faker->randomElement(Language::all());

        // Create 2 new Posts for testing
        $post = Post::create(['created_at' => Carbon::now(config('app.timezone'))]);
        PostTranslation::factory()
            ->languagePrefix($language->prefix)->create([
                'language_id' => $language->id,
                'post_id' => $post->id,
                'title' => $language->prefix . ' : ' . $postSearch,
                'description' => $language->prefix . ' : ' . $postSearch,
                'content' => $language->prefix . ' : ' . $postSearch,
            ]);
        $post2 = Post::create(['created_at' => Carbon::now(config('app.timezone'))]);
        PostTranslation::factory()
            ->languagePrefix($language->prefix)->create([
                'language_id' => $language->id,
                'post_id' => $post2->id,
                'title' => $language->prefix . ' : ' . $postSearch,
                'description' => $language->prefix . ' : ' . $postSearch,
                'content' => $language->prefix . ' : ' . $postSearch,
            ]);

        // Create a new post tag for testing for related PostTag
        $tag = Tag::create(['name' => 'Test Tag ' . $faker->text(20)]);
        PostTag::factory()->create([
                'tag_id' => $tag->id,
                'post_id' => $post->id,
            ]);

        // Test Action
        $response = $this
            ->postJson(route('posts.filter'), [
                'search' => $postSearch,
                'language_id' => $language->id,
            ]);

        $response->assertStatus(HTTP_RESPONSE_OK);  // 200
        $this->assertEquals($response->original['posts']->count(), 2, '17 : Number of Posts found invalid');
    }
    // 7: FiltersWithLocale

    /**
     * 8) Create post from factory, save model in storage, Read Post from storage and read post
     */
    public function test_8_PostIsShown()
    {
        // Test Data Setup
        $faker = \Faker\Factory::create();
        $post = Post::create(['created_at' => Carbon::now(config('app.timezone'))]);
        $language = $faker->randomElement(Language::all());
        PostTranslation::factory()
            ->languagePrefix($language->prefix)->create([
                'language_id' => $language->id,
                'post_id' => $post->id,
            ]);

        // Test Action
        $response = $this
            ->get(route('posts.show', $post->id));

        // Check Assert
        $response->assertStatus(HTTP_RESPONSE_OK);  // 200
    }
    // 8: PostIsShown()


}
