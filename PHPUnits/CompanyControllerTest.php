<?php

namespace Modules\Companies\Tests\Unit\User;

use Mockery;
use Carbon\Carbon;
use Faker\Factory;
use Tests\TestCase;
use Illuminate\Support\Arr;
use Modules\Users\Models\UserModel;
use Illuminate\Support\Facades\Auth;
use Modules\VanillaCore\Models\Role;
use Modules\VanillaCore\Helpers\Helper;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Modules\Companies\Models\CompanyModel;
use Modules\VanillaCore\Helpers\ModuleHelper;
use Modules\Users\Repositories\UserRepository;
use Modules\Organisations\Models\Organisation;
use Modules\Address\Repositories\CityRepository;
use Cartalyst\Sentinel\Laravel\Facades\Sentinel;
use Modules\Brands\Repositories\BrandRepository;
use Modules\Address\Repositories\StateRepository;
use Modules\Address\Repositories\CountryRepository;
use Modules\Relationships\Models\RelationshipModel;
use Modules\VanillaCore\Repositories\TeamRepository;
use Modules\Companies\Repositories\CompanyRepository;
use Modules\VanillaCore\Repositories\ExcelRepository;
use Modules\VanillaCore\Repositories\EmailRepository;
use Modules\Users\Repositories\UserRepositoryEloquent;
use Modules\Notes\Repositories\CompanyNotesRepository;
use Modules\VanillaCore\Repositories\OptionRepository;
use Modules\Relationships\Models\RelationshipNameModel;
use Modules\VanillaCore\Repositories\ServiceRepository;
use Modules\Notes\Repositories\SaleOrderNotesRepository;
use Modules\VanillaCore\Repositories\SettingsRepository;
use Modules\SaleOrders\Repositories\SaleOrderRepository;
use Modules\Quotations\Repositories\QuotationRepository;
use Modules\Appliances\Repositories\AppliancesRepository;
use Modules\Payments\Repositories\PaymentAccountRepository;
use Modules\Companies\Repositories\CompanyStatusRepository;
use Modules\VanillaCore\Repositories\PaymentPeriodRepository;
use Modules\VanillaCore\Repositories\EmailRepositoryEloquent;
use Modules\VanillaCore\Repositories\ExcelRepositoryEloquent;
use Modules\Companies\Repositories\CompanyRepositoryEloquent;
use Modules\Companies\Repositories\CompanyPriorityRepository;
use Modules\Organisations\Repositories\OrganisationRepository;
use Modules\Relationships\Repositories\RelationshipsRepository;
use Modules\Companies\Http\Controllers\Users\CompanyController;
use Modules\VanillaCore\Repositories\SettingsRepositoryEloquent;
use Modules\Appliances\Repositories\SaleOrderApplianceRepository;
use Modules\Appliances\Repositories\QuotationApplianceRepository;
use Modules\Relationships\Repositories\RelationshipNamesRepository;
use Modules\Companies\Repositories\CompanyStatusRepositoryEloquent;
use Modules\Quotations\Repositories\QuotationPaymentAccountRepository;
use Modules\Organisations\Repositories\OrganisationRepositoryEloquent;

class CompanyControllerTest extends TestCase
{
    private $UserRepository;
    private $CityRepository;
    private $CallRepository;
    private $StateRepository;
    private $EmailRepository;
    private $ExcelRepository;
    private $CompanyRepository;
    private $InvoiceRepository;
    private $CountryRepository;
    private $MeetingRepository;
    private $SettingsRepository;
    private $QuotationRepository;
    private $SaleOrderRepository;
    private $OrganisationRepository;

    private $CompanyController;

    private $User;

    public function setUp(): void
    {
        parent::setUp();

        Artisan::call('module:seed', ['--class' => 'SettingsSeeder', 'module' => 'VanillaCore']);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        Auth::logout();
    }

    public function assembleControllerDependencies(): void
    {
        $this->UserRepository         = Mockery::mock(UserRepositoryEloquent::class);
        $this->ExcelRepository        = Mockery::mock(ExcelRepositoryEloquent::class);
        $this->EmailRepository        = Mockery::mock(EmailRepositoryEloquent::class);
        $this->CompanyRepository      = Mockery::mock(CompanyRepositoryEloquent::class);
        $this->SettingsRepository     = Mockery::mock(SettingsRepositoryEloquent::class);
        $this->OrganisationRepository = Mockery::mock(OrganisationRepositoryEloquent::class);
    }

    private function mockFunctions(CompanyModel $TestCompany)
    {
//        $this->UserRepository->shouldReceive('getAllAsArray')
//                             ->once()
//                             ->andReturn([1 => 'Test User']);
//
//        $this->CompanyRepository->shouldReceive('getAllAsArray')
//                                ->once()
//                                ->andReturn([1 => 'Test Company']);
//
//        $this->CompanyRepository->shouldReceive('getById')
//                                 ->with(object_get($TestCompany, 'id'))
//                                 ->once()
//                                 ->andReturn($TestCompany);
//
//        $this->CompanyRepository->shouldReceive('getAllExcept')
//                                 ->once()
//                                 ->andReturn(CompanyModel::orderByDesc('id')->first());
//
//        $this->CountryRepository->shouldReceive('getAllForDropdown')
//                                ->once()
//                                ->andReturn([1 => 'United Kingdom']);
//
//        $this->CountryRepository->shouldReceive('getAllForDropdown')
//                                ->once()
//                                ->andReturn([71 => 'United Kingdom']);
//
//        $this->CountryRepository->shouldReceive('getDefaultCountryId')
//                                ->once()
//                                ->andReturn(71);
//
//        $this->StateRepository->shouldReceive('getAllForDropdown')
//                              ->with(object_get($TestCompany, 'country_id'))
//                              ->once()
//                              ->andReturn([12 => 'Test County']);
//
//        $this->CityRepository->shouldReceive('getAllForDropdown')
//                             ->with(object_get($TestCompany, 'county_id'))
//                             ->once()
//                             ->andReturn([123 => 'Test City']);
//
//        $this->SettingsRepository->shouldReceive('getKey')
//                                 ->with('date_format')
//                                 ->andReturn('j F Y');
//
//        $this->SettingsRepository->shouldReceive('getKey')
//                                 ->with('date_time_format')
//                                 ->andReturn('j F Y H:i');
    }

    public function manifestCompanyController()
    {
        $this->CompanyController = new CompanyController(
            $this->UserRepository,
            $this->ExcelRepository,
            $this->EmailRepository,
            $this->CompanyRepository,
            $this->SettingsRepository,
            $this->OrganisationRepository
        );
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testExample()
    {
        $this->assertTrue(true);
    }

    /** @test */
    public function canLoadController()
    {
        $this->assembleControllerDependencies();
        $this->manifestCompanyController();

        $this->assertInstanceOf(CompanyController::class, $this->CompanyController);
    }

    /** @test */
    public function canOpenIndexView()
    {
        $TestUser = $this->logInByRole('admin');

        $this->assembleControllerDependencies();
        $this->manifestCompanyController();

        $response = $this->actingAs($TestUser)->get('companies')
                         ->assertStatus(200)
                         ->assertViewIs('companies::user.index')
                         ->assertViewHas('title', trans('companies::main.companies'));
    }

    /** @test */
    public function canShowTheCreateCompanyPage()
    {
        $TestUser = $this->logInByRole('admin');

        $this->assembleControllerDependencies();
        $this->manifestCompanyController();

        $response = $this->get('/companies/create')
                         ->assertStatus(200)
                         ->assertViewIs('companies::user.create')
                         ->assertViewHas('title', trans('companies::main.create_company'))
                         ->assertViewHas('action', trans('create'));
    }

    /** @test */
    public function canStoreNewCompany()
    {
        $this->logInByRole('admin');

        $this->assembleControllerDependencies();
        $this->manifestCompanyController();

        $NewCompany     = factory(CompanyModel::class)->make();
        $newCompanyData = $NewCompany->toArray();

        $newCompanyData['company_avatar'] = Storage::get($newCompanyData['company_avatar']);

        $response = $this->post('/companies', $newCompanyData);

        $ReturnedNewCompany   = CompanyModel::orderByDesc('id')->limit(1)->first();
        $returnedNewCompanyId = object_get($ReturnedNewCompany, 'id');

        $response->assertSessionHasNoErrors();

        $response->assertStatus(302)
                 ->assertRedirect("companies/$returnedNewCompanyId/edit");

        $this->assertEquals(object_get($NewCompany, 'email'), object_get($ReturnedNewCompany, 'email'), 'The email does not match the expected. Check if the validation has failed and if the New Company has been created.');
        $this->assertEquals(object_get($NewCompany, 'first_name'), object_get($ReturnedNewCompany, 'first_name'), 'The first_name does not match the expected. Check if the validation has failed and if the New Company has been created.');
        $this->assertEquals(object_get($NewCompany, 'last_name'), object_get($ReturnedNewCompany, 'last_name'), 'The last_name does not match the expected. Check if the validation has failed and if the New Company has been created.');
    }

    /** @test */
    public function canShowTheEditCompanyPage()
    {
        $this->logInByRole('admin');

        $TestCompany   = factory(CompanyModel::class)->create();
        $testCompanyId = object_get($TestCompany, 'id');

        $response = $this->get("/companies/$testCompanyId/edit")
                         ->assertSessionHasNoErrors()
                         ->assertStatus(200)
                         ->assertViewIs('companies::user.edit')
                         ->assertViewHas('title', trans('companies::main.edit').' - '.object_get($TestCompany, 'name'))
                         ->assertViewHas('action', trans('edit'));
    }

    /** @test */
    public function canUpdateCompanyDetails()
    {
        $this->logInByRole('admin');

        $this->assembleControllerDependencies();
        $this->manifestCompanyController();

        $TestCompany   = factory(CompanyModel::class)->create();
        $testCompanyId = object_get($TestCompany, 'id');

        $newCompanyData = [
            'name'           => 'UpdatedName',
            'email'          => 'updated'.time().'@email.com',
            'phone'          => '01234567890',
            'mobile'         => '07934567890',
            'website'        => 'https://www.testurl.com',
            'address_line_1' => '123 Test Road',
            'address_line_2' => 'Testingtown',
            'city_id'        => 99,
            'county_id'      => 99,
            'country_id'     => 99,
        ];

        $response = $this->put("/companies/$testCompanyId", $newCompanyData);

        $UpdatedCompany = CompanyModel::where('id', $testCompanyId)->first();

        $response->assertSessionHasNoErrors();

        $response->assertStatus(302)
                 ->assertRedirect("companies/$testCompanyId/edit");

        $this->assertEquals(Arr::get($newCompanyData, 'name'), object_get($UpdatedCompany, 'name'), 'The name does not match the expected.');
        $this->assertEquals(Arr::get($newCompanyData, 'email'), object_get($UpdatedCompany, 'email'), 'The email does not match the expected.');
        $this->assertEquals(Arr::get($newCompanyData, 'phone'), object_get($UpdatedCompany, 'phone'), 'The phone does not match the expected.');
        $this->assertEquals(Arr::get($newCompanyData, 'mobile'), object_get($UpdatedCompany, 'mobile'), 'The mobile does not match the expected.');
        $this->assertEquals(Arr::get($newCompanyData, 'address_line_1'), object_get($UpdatedCompany, 'address_line_1'), 'The address_line_1 does not match the expected.');
        $this->assertEquals(Arr::get($newCompanyData, 'address_line_2'), object_get($UpdatedCompany, 'address_line_2'), 'The address_line_2 does not match the expected.');
        $this->assertEquals(Arr::get($newCompanyData, 'city_id'), object_get($UpdatedCompany, 'city_id'), 'The city_id does not match the expected.');
        $this->assertEquals(Arr::get($newCompanyData, 'county_id'), object_get($UpdatedCompany, 'county_id'), 'The county_id does not match the expected.');
        $this->assertEquals(Arr::get($newCompanyData, 'country_id'), object_get($UpdatedCompany, 'country_id'), 'The country_id does not match the expected.');
    }

    /** @test */
    public function canShowTheShowCompanyPage()
    {
        $this->logInByRole('admin');

        $TestCompany   = factory(CompanyModel::class)->create();
        $testCompanyId = object_get($TestCompany, 'id');

        $response = $this->get("/companies/$testCompanyId")
                         ->assertSessionHasNoErrors()
                         ->assertStatus(200)
                         ->assertViewIs('companies::user.show')
                         ->assertViewHas('title', trans('companies::main.details').' - '.object_get($TestCompany, 'name'))
                         ->assertViewHas('action', trans('show'))
                         ->assertSee(object_get($TestCompany, 'name'))
                         ->assertSee(object_get($TestCompany, 'email'))
                         ->assertSee(object_get($TestCompany, 'phone'));
    }

    /** @test */
    public function canShowTheDeleteCompanyPage()
    {
        $this->logInByRole('admin');

        $TestCompany   = factory(CompanyModel::class)->create();
        $testCompanyId = object_get($TestCompany, 'id');

        $response = $this->get("/companies/$testCompanyId/delete")
                         ->assertSessionHasNoErrors()
                         ->assertStatus(200)
                         ->assertViewIs('companies::user.delete')
                         ->assertViewHas('title', trans('companies::main.delete').' - '.object_get($TestCompany, 'name'))
                         ->assertViewHas('action', trans('delete'))
                         ->assertSee(object_get($TestCompany, 'name'))
                         ->assertSee(object_get($TestCompany, 'phone'))
                         ->assertSee(object_get($TestCompany, 'email'));
    }

    /** @test */
    public function canDeleteACompany()
    {
        $this->logInByRole('admin');

        $this->assembleControllerDependencies();
        $this->manifestCompanyController();

        $TestCompany        = factory(CompanyModel::class)->create();
        $testCompanyId      = object_get($TestCompany, 'id');
        $testCompanyDetails = [
            'id'    => object_get($TestCompany, 'id'),
            'name'  => object_get($TestCompany, 'name'),
            'phone' => object_get($TestCompany, 'phone'),
            'email' => object_get($TestCompany, 'email'),
        ];


        $this->assertDatabaseHas('companies', $testCompanyDetails);

        $response = $this->delete("/companies/$testCompanyId");

        $response->assertSessionHasNoErrors();

        $response->assertStatus(302)
                 ->assertRedirect("/companies");

        $CheckCompanyMissing = CompanyModel::find($testCompanyId);
        $CheckCompanyDeleted = CompanyModel::where('id', $testCompanyId)->withTrashed()->first();

        $this->assertNotNull(object_get($CheckCompanyDeleted, 'deleted_at'), 'Company has not been correctly soft-deleted.');
        $this->assertNull($CheckCompanyMissing, 'Soft-delete is not correctly omitting this from searches.');
    }

    /** @test */
    public function canGetAdminCompanyDatatableData()
    {
        if ($TestUser = $this->logInByRole('admin'))
        {
            // Given - Initial Company Count
            $response     = $this->get('/companies/data');
            $json         = $response->content();
            $result       = json_decode($json);
            $initialCount = object_get($result, 'recordsTotal');

            $testCompaniesTrue = factory(CompanyModel::class, 3)->create();

            // When
            $response     = $this->get('/companies/data');
            $json         = $response->content();
            $result       = json_decode($json);
            $data         = object_get($result, 'data');
            $updatedCount = object_get($result, 'recordsTotal');

            // Then
            $response->assertSessionHasNoErrors();
            $this->assertNull(object_get($data, 'error'), 'Error Encountered');
            $this->assertEquals(3, $updatedCount - $initialCount, 'Incorrect Companies are being returned.');

            // Positive Test
            foreach ($testCompaniesTrue as $TestCompany)
            {
                $foundObjectsArray = Helper::findObjectInArrayOfObjects($data, $TestCompany->id, 'id');
                $FoundObject       = Helper::getFirstItemInArray($foundObjectsArray);
                $this->assertEquals(object_get($TestCompany, 'email'), object_get($FoundObject, 'email'), 'The email for the test company was not found in the response.');
            }
        }
    }

    /** @test */
    public function canGetUserDatatableData()
    {
        if ($TestUser = $this->logInByRole('user'))
        {
            // Given
            $Organisation             = $TestUser->getOrganisation();
            $organisationId           = object_get($Organisation, 'id');

            // Initial Company Count
            $response     = $this->get('/companies/data');
            $json         = $response->content();
            $result       = json_decode($json);
            $initialCount = object_get($result, 'recordsTotal');

            $testCompaniesTrue = factory(CompanyModel::class, 3)->create(
                [
                    'organisation_id'    => $organisationId,
                ]
            );
            $testCompaniesFalse = factory(CompanyModel::class, 3)->create(
                [
                    'organisation_id'    => $organisationId - 1,
                ]
            );

            // When
            $response     = $this->get('/companies/data');
            $json         = $response->content();
            $result       = json_decode($json);
            $data         = object_get($result, 'data');
            $updatedCount = object_get($result, 'recordsTotal');

            // Then
            $response->assertSessionHasNoErrors();
            $this->assertNull(object_get($data, 'error'), 'Error Encountered');
            $this->assertEquals(3, $updatedCount - $initialCount, 'Incorrect Companies are being returned.');

            // Positive Test
            foreach ($testCompaniesTrue as $TestCompany)
            {
                $foundObjectsArray = Helper::findObjectInArrayOfObjects($data, $TestCompany->id, 'id');
                $FoundObject       = Helper::getFirstItemInArray($foundObjectsArray);
                $this->assertEquals(object_get($TestCompany, 'email'), object_get($FoundObject, 'email'), 'The email for the test company was not found in the response.');
            }

            // Negative test
            foreach ($testCompaniesFalse as $TestCompany)
            {
                $foundObjectsArray = Helper::findObjectInArrayOfObjects($data, $TestCompany->id, 'id');
                $FoundObject       = Helper::getFirstItemInArray($foundObjectsArray);
                $this->assertNull($FoundObject, 'The wrong companies are showing up when they shouldnt be.');
            }
        }
    }

//    /** @test */
//    public function canGetFinanceDatatableData()
//    {
//        if ($TestUser = $this->logInByRole('finance'))
//        {
//            // Given
//            $pendingCompanyStatusId  = $this->CompanyStatusRepository->getPendingStatusId();
//            $completeCompanyStatusId = $this->CompanyStatusRepository->getCompleteStatusId();
//            $Organisation             = $TestUser->getOrganisation();
//            $organisationId           = object_get($Organisation, 'id');
//
//            // Initial Company Count
//            $response     = $this->get('/companies/data');
//            $json         = $response->content();
//            $result       = json_decode($json);
//            $initialCount = object_get($result, 'recordsTotal');
//
//            $testCompaniesTrue  = factory(CompanyModel::class, 3)->create(
//                [
//                    'company_status_id' => $pendingCompanyStatusId,
//                    'organisation_id'    => $organisationId,
//                ]
//            );
//            $testCompaniesFalse = factory(CompanyModel::class, 3)->create(
//                [
//                    'company_status_id' => $completeCompanyStatusId,
//                    'organisation_id'    => $organisationId,
//                ]
//            );
//
//            // When
//            $response     = $this->get('/companies/data');
//            $json         = $response->content();
//            $result       = json_decode($json);
//            $data         = object_get($result, 'data');
//            $updatedCount = object_get($result, 'recordsTotal');
//
//            // Then
//            $response->assertSessionHasNoErrors();
//            $this->assertNull(object_get($data, 'error'), 'Error Encountered');
//            $this->assertEquals(3, $updatedCount - $initialCount, 'Incorrect Companies are being returned.');
//
//            // Positive Test
//            foreach ($testCompaniesTrue as $TestCompany)
//            {
//                $foundObjectsArray = Helper::findObjectInArrayOfObjects($data, $TestCompany->id, 'id');
//                $FoundObject       = Helper::getFirstItemInArray($foundObjectsArray);
//                $this->assertEquals(object_get($TestCompany, 'email'), object_get($FoundObject, 'email'), 'The email for the test company was not found in the response.');
//            }
//
//            // Negative test
//            foreach ($testCompaniesFalse as $TestCompany)
//            {
//                $foundObjectsArray = Helper::findObjectInArrayOfObjects($data, $TestCompany->id, 'id');
//                $FoundObject       = Helper::getFirstItemInArray($foundObjectsArray);
//                $this->assertNull($FoundObject, 'The wrong companies are showing up when they shouldnt be.');
//            }
//        }
//    }


}
