<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Category;
use Tests\TestCase;
use App\Models\Settings;
use Carbon\Carbon;
use App\Models\Page;
use App\Models\User;

class PagesCrudTest extends TestCase
{
    use RefreshDatabase;

    protected static $wasSetup = false;
    protected static $loggedUser = null;
    protected static $is_debug = false;

    public function setUp(): void
    {
        parent::setUp();
        if ( ! self::$wasSetup) {
            self::$wasSetup   = true;
            self::$loggedUser = \App\Models\User::factory(User::class)->create();
        }
        $this->actingAs(self::$loggedUser, 'api');
    }

    public function testPageIsAdded() // 1) CREATE AND READ PAGE
    {
        $newPageObject                  = \App\Models\Page::factory(Page::class)->make([
            'creator_id' => self::$loggedUser->id
        ]);
        $faker                          = \Faker\Factory::create();
        $category_id                    = $faker->randomElement(Category::all())['id'];
        $newPageObject->page_categories = $category_id;

        $response = $this->post(route('pages.store'), $newPageObject->toArray());
        $response
            ->assertStatus(HTTP_RESPONSE_OK_RESOURCE_CREATED) // 201
            ->assertJsonPath('page.title', $newPageObject->title);

        $insertedPage = Page::getSimilarPageByCreatorId($newPageObject->title, self::$loggedUser->id, null,
            false);
        $this->assertNotNull($insertedPage, '11 : Inserted page not found');
        $this->assertEquals($insertedPage->title, $newPageObject->title,
            '12 : Title read is not equal title on insert');
    }


    public function testPageIsUpdated()  // 2) CREATE / UPDATE AND READ PAGE
    {
        $newPageModel = \App\Models\Page::factory(Page::class)->create([
            'creator_id' => self::$loggedUser->id
        ]); // only unpublished page can be updated
        $faker       = \Faker\Factory::create();
        $category_id = $faker->randomElement(Category::all())['id'];

        $response = $this->put(route('pages.update', $newPageModel->id), [
            'title'            => $newPageModel->title . ' UPDATED',
            'content'          => ' UPDATED content ',
            'content_shortly'  => ' UPDATED content shortly',
            'is_homepage'      => false,
            'price'            => 9912.34,
            'published'        => true,
            //            'image'            => 'about.jpg',
            'meta_description' => $newPageModel->title . ' meta description sed do eiusmod  tempor incididunt UPDATED',
            'meta_keywords'    => json_encode([$newPageModel->title, $newPageModel->title . ' UPDATED']),
            'page_categories'  => $category_id,
        ]);
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_UPDATED);  // 205

        // READ PAGE UPDATED ABOVE
        $updatedPage = Page::getSimilarPageByCreatorId($newPageModel->title . ' UPDATED', self::$loggedUser->id, null,
            false);

        $this->assertNotNull($updatedPage, '21 : updated page not found');
        $this->assertEquals($updatedPage->title, $newPageModel->title . ' UPDATED',
            '22 : Title read is not equal title on update');
    }

    public function testNegativePageIsUpdated()  // 3) CREATE / UPDATE AND READ PAGE - MUST RAISE ERROR
    {
        $newPageModel = \App\Models\Page::factory(Page::class)->create([
            'creator_id' => self::$loggedUser->id,
            'published'  => true  // Owner of the page can update only unpublished page
        ]); // only unpublished page can be updated
        $faker       = \Faker\Factory::create();
        $category_id = $faker->randomElement(Category::all())['id'];

        $response = $this->put(route('pages.update', $newPageModel->id), [
            'title'            => $newPageModel->title . ' UPDATED',
            'content'          => ' UPDATED content ',
            'content_shortly'  => ' UPDATED content shortly',
            'is_homepage'      => false,
            'price'            => 9912.34,
            'published'        => true,
            //            'image'            => 'about.jpg',
            'meta_description' => $newPageModel->title . ' meta description sed do eiusmod  tempor incididunt UPDATED',
            'meta_keywords'    => json_encode([$newPageModel->title, $newPageModel->title . ' UPDATED']),
            'page_categories'  => $category_id,
        ]);
        $response->assertStatus(HTTP_RESPONSE_BAD_REQUEST, '31 : must be negative test');  // 400
    }


    public function testNegativePageIsUpdatedWithWrongUser()  // 4) CREATE / UPDATE WITH WRONG USER - MUST RAISE ERROR
    {
        $anotherUser  = \App\Models\User::factory(User::class)->create();
        $newPageModel = \App\Models\Page::factory(Page::class)->create([
            'creator_id' => self::$loggedUser->id,
        ]);

        $this->actingAs($anotherUser, 'api');

        $response = $this->put(route('pages.update', $newPageModel->id), [
            'title'   => $newPageModel->title . ' UPDATED',
            'content' => ' UPDATED content ',
        ]);
        $response->assertStatus(HTTP_RESPONSE_UNAUTHORIZED);
    }

    public function testPageIsPublishedNegative(
    )  // 5) CREATE / PUBLISH WITH WRONG USER -  - MUST RAISE ERROR 401 error
    {
        $newPageModel = \App\Models\Page::factory(Page::class)->create([
            'creator_id' => self::$loggedUser->id
        ]);

        $response = $this->put(route('pages.publish', $newPageModel->id), []);
        $response->assertStatus(HTTP_RESPONSE_UNAUTHORIZED);  // 401
    }

    public function testPageIsPublished()  // 6) CREATE / PUBLISH(under admin access) AND READ PAGE
    {
        $newPageModel = \App\Models\Page::factory(Page::class)->create([
            'creator_id' => self::$loggedUser->id
        ]);

        $php_unit_tests_logged_admin_user_id = (int)config('app.php_unit_tests_logged_admin_user_id');
        $adminUser                           = User::find($php_unit_tests_logged_admin_user_id);
        $this->assertNotNull($adminUser, '61 : Admin User not found');
        $this->actingAs($adminUser, 'api');

        $response = $this->put(route('pages.publish', $newPageModel->id), []);
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_UPDATED);  // 205

        // READ PAGE PUBLISHED ABOVE
        $publishedPage = Page::getSimilarPageByCreatorId($newPageModel->title, self::$loggedUser->id, null,
            false);

        $this->assertNotNull($publishedPage, '62 : updated page not found');
        $this->assertEquals($publishedPage->published, true, '63 : Page is not testPageIsPublished');
    }

    public function testNegativePageIsPublished(
    )  // 7) CREATE / PUBLISH AND READ PAGE - MUST RAISE ERROR 400 error code
    {
        $newPageModel = \App\Models\Page::factory(Page::class)->create([
            'creator_id' => self::$loggedUser->id,
            'published'  => true  // Only published page can be unpublished
        ]);

        $php_unit_tests_logged_admin_user_id = (int)config('app.php_unit_tests_logged_admin_user_id');
        $adminUser                           = User::find($php_unit_tests_logged_admin_user_id);
        $this->assertNotNull($adminUser, '71 : Admin User not found');
        $this->actingAs($adminUser, 'api');

        $response = $this->put(route('pages.publish', $newPageModel->id),);
        $response->assertStatus(HTTP_RESPONSE_BAD_REQUEST);  // 400
    }

    public function testPageIsUnpublished()  // 8) CREATE / UNPUBLISH AND READ PAGE
    {
        $newPageModel = \App\Models\Page::factory(Page::class)->create([
            'creator_id' => self::$loggedUser->id,
            'published'  => true  // Only published page can be unpublished page
        ]);

        $php_unit_tests_logged_admin_user_id = (int)config('app.php_unit_tests_logged_admin_user_id');
        $adminUser                           = User::find($php_unit_tests_logged_admin_user_id);
        $this->assertNotNull($adminUser, '81 : Admin User not found');
        $this->actingAs($adminUser, 'api');

        $response = $this->put(route('pages.unpublish', $newPageModel->id), []);
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_UPDATED);  // 205

        // READ PAGE UNPUBLISHED ABOVE
        $unpublishedPage = Page::getSimilarPageByCreatorId($newPageModel->title, self::$loggedUser->id,
            null, false);

        $this->assertNotNull($unpublishedPage, '82 : updated page not found');
        $this->assertEquals($unpublishedPage->published, false, '83 : Page is not testPageIsUnpublished');
    }

    public function testNegativePageIsUnpublished()  // 9) CREATE / UNPUBLISH AND READ PAGE - MUST RAISE ERROR
    {
        $newPageModel = \App\Models\Page::factory(Page::class)->create([
            'creator_id' => self::$loggedUser->id,
            'published'  => false  // Only published page can be unpublished page
        ]);

        $php_unit_tests_logged_admin_user_id = (int)config('app.php_unit_tests_logged_admin_user_id');
        $adminUser                           = User::find($php_unit_tests_logged_admin_user_id);
        $this->assertNotNull($adminUser, '91 : Admin User not found');
        $this->actingAs($adminUser, 'api');

        $response = $this->put(route('pages.unpublish', $newPageModel->id), []);
        $response->assertStatus(HTTP_RESPONSE_BAD_REQUEST);  // 400

    }


    public function testPageIsDestroyed()  // 10) CREATE / DELETE PAGE
    {
        $newPageModel = \App\Models\Page::factory(Page::class)->create([
            'creator_id' => self::$loggedUser->id
        ]);

        $response = $this->delete(route('pages.destroy', $newPageModel->id), []);
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_DELETED);  // 204

        // READ PAGE DESTROYED ABOVE
        $destroyed_pages_count = Page::getSimilarPageByCreatorId($newPageModel->title, self::$loggedUser->id,
            null, true);

        $this->assertEquals(0, $destroyed_pages_count, '10 : Destroyed page found');
    }


    public function testGetFilteredOnlyPublished() // 11) CREATE PUBLISHED PAGES AND READ/CHECK THEY ARE PUBLISHED
    {
        \App\Models\Page::factory(Page::class)->create([
            'creator_id' => self::$loggedUser->id,
            'published' => true,
        ]);
        \App\Models\Page::factory(Page::class)->create([
            'creator_id' => self::$loggedUser->id,
            'published' => true,
        ]);

        $response = $this->post(route('pages.filter'), [
            'filter_published' => true,
            'per_page'=>1000
        ]);
        foreach ($response->original as $next_key => $nextPage) {
            $this->assertEquals($nextPage->published, true, '11 : Page must be published');
        }
        $response->assertStatus(HTTP_RESPONSE_OK);  // 200
    } // 11) CREATE PUBLISHED PAGE AND READ/CHECK THEY ARE PUBLISHED

    public function testGetFilteredOnlyIsHomepage() // 12) CREATE HOME PAGES AND READ/CHECK THEY ARE PUBLISHED
    {
        \App\Models\Page::factory(Page::class)->create([
            'creator_id' => self::$loggedUser->id,
            'is_homepage' => true,
        ]);
        \App\Models\Page::factory(Page::class)->create([
            'creator_id' => self::$loggedUser->id,
            'is_homepage' => true,
        ]);
        $response = $this->post(route('pages.filter'), [
            'filter_is_homepage' => true,
            'per_page'=>1000
        ]);
        foreach ($response->original as $next_key => $nextPage) {
            $this->assertEquals($nextPage->is_homepage, true, '12 : Page must be is homepage');
        }
        $response->assertStatus(HTTP_RESPONSE_OK); // 12) CREATE HOME PAGES AND READ/CHECK THEY ARE PUBLISHED
    }
}

