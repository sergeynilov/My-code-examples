<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\Concerns\InteractsWithExceptionHandling;

use Tests\TestCase;
use App\Models\{Article, User};
use Illuminate\Support\Str;

class ArticlesCrudTest extends TestCase
{
    use InteractsWithExceptionHandling;
    use RefreshDatabase;

    protected static $wasSetup = false;
    protected static $loggedUser = null;

    public function setUp(): void
    {
        parent::setUp();
        if (! self::$wasSetup) {
            // Regenerate structure / fresh data only once at first test
            \Artisan::call(' migrate:fresh --seed');
            Artisan::call('config:clear');
            $databaseName = \DB::connection()->getDatabaseName();
            $result = Str::endsWith($databaseName, 'HttpTesting');
            if (! $result) {
                die('Invalid database "' . $databaseName . '" connected ');
            }
            self::$wasSetup   = true;
            self::$loggedUser = User::factory(User::class)->create();
        }
    }

    // 1) CREATE AND READ ARTICLE
    public function test_1_ArticleIsAdded()
    {
        // Test Data Setup
        $newArticleModelData = Article::factory(Article::class)->make([  // model only in memory
            'creator_id' => self::$loggedUser->id
        ]);

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->post(route('articles.store'), $newArticleModelData->toArray());
        $response
            ->assertStatus(HTTP_RESPONSE_OK_RESOURCE_CREATED) // 201
            ->assertJsonPath('article.title', $newArticleModelData->title);

        $insertedArticle = Article // READ ARTICLE CREATED ABOVE
            ::getByTitle($newArticleModelData->title)
            ->getCreatorId(self::$loggedUser->id)
            ->first();

        // Check Assert
        $this->assertNotNull($insertedArticle, '1_1 : Inserted article not found');
        $this->assertEquals(
            $insertedArticle->title,
            $newArticleModelData->title,
            '1_2 : Title read is not equal title on insert'
        );
    } // 1: testArticleIsAdded()


    // 2) CREATE ARTICLE WITH WRONG DATA - MUST RAISE VALIDATION ERRORS
    public function test_2_NegativeArticleGotFailureWithEmptyFieldsToBeAdded()
    {
        // Test Data Setup
        $newArticleModelData = Article::factory(Article::class)->make([  // model only in memory
            'creator_id' => null,  // Required fields are empty - BAD_REQUEST (400) must be returned
            'title'      => '',
            'text'       => null,
        ]);

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->post(route('articles.store'), $newArticleModelData->toArray());

        // Check Assert
        $response->assertStatus(HTTP_RESPONSE_BAD_REQUEST); // 400
    } // 2: NegativeArticleGotFailureWithEmptyFieldsToBeAdded()


    // 3) CREATE WITH INACTIVE USER - MUST RAISE VALIDATION ERRORS
    public function test_3_NegativeArticleGotFailureWithInactiveUserToBeAdded()
    {
        // Test Data Setup
        $loggedUser = clone(self::$loggedUser);

        $loggedUser->status = 'I'; // Inactive
        $newArticleModelData = Article::factory(Article::class)->make();  // model only in memory

        // Check Assert for custom Exception
        $this->withoutExceptionHandling();
        $this->expectException(\App\Exceptions\UserAccountManagerAccessException::class);
        $this->expectExceptionMessage('Your account must be active in "store" method !');

        // Test Action
         $this->actingAs($loggedUser, 'api')
            ->post(route('articles.store'), $newArticleModelData->toArray());
    } // 3: NegativeArticleGotFailureWithInactiveUserToBeAdded()


    // 4) CREATE / UPDATE AND READ ARTICLE
    public function test_4_ArticleIsUpdated()
    {
        // Test Data Setup
        $article = Article::factory(Article::class)->create([
            'creator_id' => self::$loggedUser->id
        ]);

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->put(route('articles.update', $article->id), [
                'title'        => $article->title . ' UPDATED',
                'text'         => ' UPDATED text ',
                'text_shortly' => ' UPDATED text shortly',
                'published'    => true,
            ]);

        // Check Assert
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_UPDATED)  // 205
             ->assertJsonPath('article.title', $article->title . ' UPDATED');

        $updatedArticle = Article // READ ARTICLE UPDATED ABOVE
            ::getByTitle($article->title . ' UPDATED')
            ->getCreatorId(self::$loggedUser->id)
            ->first();
        $this->assertNotNull($updatedArticle, '41 : updated article not found');
        $this->assertEquals(
            $updatedArticle->title,
            $article->title . ' UPDATED',
            '42 : Title read is not equal title on update'
        );
    } // 4: ArticleIsUpdated()


    // 5) CREATE / UPDATE WITH INVALID ID(NEGATIVE) - MUST RAISE VALIDATION ERRORS
    public function test_5_NegativeArticleFailuredBeUpdatedAsNotFound()
    {
        // Test Data Setup
        $article = Article::factory(Article::class)->create([
            'creator_id' => self::$loggedUser->id
        ]);

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->put(route('articles.update', -$article->id), [
                'title'        => $article->title . ' UPDATED',
                'text'         => ' UPDATED text ',
                'text_shortly' => ' UPDATED text shortly',
                'published'    => true,
            ]);

        // Check Assert
        $response->assertStatus(HTTP_RESPONSE_NOT_FOUND);  // 205
    } // 5: testNegativeArticleFailuredBeUpdatedAsNotFound()


    // 6) CREATE / UPDATE AND READ ARTICLE - MUST RAISE VALIDATION ERRORS
    public function test_6_NegativeArticleGotFailureToBeUpdated()
    {
        // Test Data Setup
        $article = Article::factory(Article::class)->create([
            'creator_id' => self::$loggedUser->id,
        ]);

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->put(route('articles.update', $article->id), [
                'title'        => $article->title . ' UPDATED',
                'text'         => '',  // Required fields are empty - BAD_REQUEST (400) must be returned
                'text_shortly' => ' UPDATED text shortly',
                'published'    => false,
            ]);

        // Check Assert
        $response->assertStatus(HTTP_RESPONSE_BAD_REQUEST);  // 400
    } // 6: testNegativeArticleGotFailureToBeUpdated()


    // 7) CREATE UNPUBLISHED_ARTICLE / PUBLISH AND READ PUBLISHED ARTICLE
    public function test_7_ArticleToPublish()
    {
        // Test Data Setup
        $newUnPublishedArticleModel = Article::factory(Article::class)->create([
            'published'  => false, // Create unpublished Article
            'creator_id' => self::$loggedUser->id,
        ]);

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->put(route('articles.activate', $newUnPublishedArticleModel->id), []);
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_UPDATED);  // 205

        $insertedArticle = Article // READ ARTICLE PUBLISHED ABOVE
            ::getByTitle($newUnPublishedArticleModel->title)
            ->getCreatorId(self::$loggedUser->id)
            ->getByPublished(true)
            ->first();

        // Check Assert
        $this->assertNotNull($insertedArticle, '71 : published article not found');
        $this->assertEquals($insertedArticle->published, true, '72 : Article is not Published');
    } // 7: testArticleToPublish()


    // 8) CREATE UNPUBLISHED_ARTICLE / PUBLISH AND READ WITH INVALID ID(NEGATIVE) - MUST RAISE VALIDATION ERRORS
    public function test_8_NegativeArticleFailuredBePublishedAsNotFound()
    {
        // Test Data Setup
        $newUnPublishedArticleModel = Article::factory(Article::class)->create([
            'published'  => false, // Create unpublished Article
            'creator_id' => self::$loggedUser->id,
        ]);

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->put(route('articles.activate', $newUnPublishedArticleModel->id), []);
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_UPDATED);  // 205

        $insertedArticle = Article // READ ARTICLE PUBLISHED ABOVE
            ::getByTitle($newUnPublishedArticleModel->title)
            ->getCreatorId(-self::$loggedUser->id)
            ->getByPublished(true)
            ->first();

        // Check Assert
        $this->assertNull($insertedArticle, '81 : published article was found');
    } // 8: testNegativeArticleFailuredBePublishedAsNotFound()


    // 9) CREATE PUBLISHED ARTICLE / UNPUBLISH AND READ UNPUBLISHED ARTICLE
    public function test_9_ArticleToUnpublish()
    {
        // Test Data Setup
        $newPublishedArticleModel = Article::factory(Article::class)->create([
            'published'  => true, // Create published Article
            'creator_id' => self::$loggedUser->id,
        ]);

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->put(route('articles.deactivate', $newPublishedArticleModel->id), []);
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_UPDATED);  // 205

        $insertedArticle = Article // READ ARTICLE UNPUBLISHED ABOVE
            ::getByTitle($newPublishedArticleModel->title)
            ->getCreatorId(self::$loggedUser->id)
            ->getByPublished(false)
            ->first();

        // Check Assert
        $this->assertNotNull($insertedArticle, '91 : unpublished article not found');
        $this->assertEquals($insertedArticle->published, false, '92 : Article is Published');
    } // 9: testArticleToUnpublish()


    // 10) CREATE PUBLISHED_ARTICLE / UNPUBLISH AND READ WITH INVALID ID(NEGATIVE) - MUST RAISE VALIDATION ERRORS
    public function test_10_NegativeArticleToUnpublishAsNotFound()
    {
        // Test Data Setup
        $newPublishedArticleModel = Article::factory(Article::class)->create([
            'published'  => true, // Create published Article
            'creator_id' => self::$loggedUser->id,
        ]);

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->put(route('articles.deactivate', -$newPublishedArticleModel->id), []);

        // Check Assert
        $response->assertStatus(HTTP_RESPONSE_NOT_FOUND);  // 205
    } // 10: testNegativeArticleToUnpublishAsNotFound()


    // 11) CREATE / DELETE ARTICLE
    public function test_11_ArticleIsDestroyed()
    {
        // Test Data Setup
        $article = Article::factory(Article::class)->create([
            'creator_id' => self::$loggedUser->id,
            'published'  => true, // Create published Article
        ]);

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->delete(route('articles.destroy', $article->id), []);
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_DELETED);  // 204

        $destroyedArticlesCount = Article // READ ARTICLE DESTROYED ABOVE
            ::getByTitle($article->title)
            ->getCreatorId(self::$loggedUser->id)
            ->getByPublished(true)
            ->count();

        // Check Assert
        $this->assertEquals(0, $destroyedArticlesCount, '11 : Destroyed article found');
    } // 11: testArticleIsDestroyed()


    // 12) CREATE PUBLISHED_ARTICLE / PUBLISH AND DELETED WITH INVALID ID(NEGATIVE) - MUST RAISE VALIDATION ERRORS
    public function test_12_NegativeArticleIsDestroyedAsNotFound()
    {
        // Test Data Setup
        $article = Article::factory(Article::class)->create([
            'creator_id' => self::$loggedUser->id,
            'published'  => true, // Create published Article
        ]);

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->delete(route('articles.destroy', -$article->id), []);

        // Check Assert
        $response->assertStatus(HTTP_RESPONSE_NOT_FOUND);  // 404
    } // 12: testArticleIsDestroyed()

    // 13) CREATE PUBLISHED ARTICLES AND READ/CHECK THEY ARE PUBLISHED
    public function test_13_FiltersOnlyPublished()
    {
        // Test Data Setup
        Article::factory(Article::class)->create([
            'creator_id' => self::$loggedUser->id,
            'published'  => true,
        ]);
        Article::factory(Article::class)->create([
            'creator_id' => self::$loggedUser->id,
            'published'  => true,
        ]);

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->post(route('articles.filter'), [
                'published' => true,
            ]);

        // Check Assert
        foreach ($response->original['articles']->items() as $next_key => $nextArticle) {
            if ($nextArticle instanceof \App\Http\Resources\ArticleResource) {
                $this->assertEquals($nextArticle->published, true, '13 : Article must be published');
            }
        }
        $response->assertStatus(HTTP_RESPONSE_OK);  // 200
    } // 13: FiltersOnlyPublished


    // 14) CREATE PUBLISHED ARTICLES AND READ/CHECK THEY ARE ARE FOUND BY NAME FILTERS
    public function test_14_FiltersOnlyPublishedFilteredByName()
    {
        // Test Data Setup
        $faker = \Faker\Factory::create();
        $articleName = 'Test Article ' . $faker->name .' Lorem Value';

        Article::factory(Article::class)->create([
            'title' => $faker->name . ' ' . $articleName . ' ' . $faker->name,
            'creator_id' => self::$loggedUser->id,
            'published'  => true,
        ]);
        Article::factory(Article::class)->create([
            'title' => $faker->name . ' ' . $articleName . ' ' . $faker->name,
            'creator_id' => self::$loggedUser->id,
            'published'  => true,
        ]);

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->post(route('articles.filter'), [
                'title' => $articleName,
                'published'  => true,
            ]);

        // Check Assert
        $response->assertStatus(HTTP_RESPONSE_OK);  // 200
        $this->assertEquals(count($response->original['articles']->items()), 2, '14 : Number of Articles found invalid');
    } // 14: FiltersOnlyPublishedFilteredByName


    // 15) CREATE PUBLISHED ARTICLES AND READ/CHECK THEY ARE ARE FOUND BY NAME FILTERS
    public function test_15_FiltersNotFoundByPublishedAndName()
    {
        // Test Data Setup
        $faker = \Faker\Factory::create();
        $articleName = 'Test Article ' . $faker->name .' Lorem Value';

        Article::factory(Article::class)->create([
            'title' => $faker->name . ' ' . $articleName . ' ' . $faker->name,
            'creator_id' => self::$loggedUser->id,
            'published'  => true,
        ]);
        Article::factory(Article::class)->create([
            'title' => $faker->name . ' ' . $articleName . ' ' . $faker->name,
            'creator_id' => self::$loggedUser->id,
            'published'  => true,
        ]);

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->post(route('articles.filter'), [
                'title' => 'Some noneexisting content',
                'published'  => true,
            ]);

        // Check Assert
        $response->assertStatus(HTTP_RESPONSE_OK);  // 200
        $this->assertEquals(count($response->original['articles']->items()), 0, '15 : Number of Articles found invalid');
    } // 15: FiltersNotFoundByPublishedAndName

    /////////// COMPLETED ////////


}
