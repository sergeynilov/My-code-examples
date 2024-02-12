<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use App\Providers\RouteServiceProvider;

class AuthTest extends TestCase
{
    protected static $wasSetup = false;
    protected static $isDebug = false;

    public function setUp(): void
    {
        parent::setUp();
        if ( ! self::$wasSetup) {
            Artisan::call('config:clear');
            $databaseName = \DB::connection()->getDatabaseName();
            $pos = strpos($databaseName, 'Testing');
            if ($pos === false) {
                die('Invalid database "' . $databaseName . '" connected ');
            }
            self::$wasSetup = true;
        }
    }


    public function test_example()
    {
        $response = $this->get('/');

        $response->assertStatus(HTTP_RESPONSE_OK); // 200
    }

    public function testIsLoginFormOpened()
    {
        // Test Data Setup

        // Test Action
        $response = $this->get(route('login'));

        // Check Assert
        $response->assertStatus(HTTP_RESPONSE_OK); // 200
        $response->assertViewIs('auth.login');
        $response->assertSessionHasNoErrors();
    }

    public function testAuthenticatedUserFailedOpenLoginForm()
    {
        // Test Data Setup
        $loggedAdmin = User::factory()->make();

        // Test Action
        $response = $this->actingAs($loggedAdmin)->get(route('login'));

        // Check Assert
        $response->assertStatus(302);  // Redirection status
        $response->assertRedirect(route('admin.dashboard.index'));
    }

    public function testUnauthenticatedUserFailedOpenDashboardPage()
    {
        // Test Data Setup

        // Test Action
        $response = $this->get(route('admin.dashboard.index'));

        // Assert
        $response->assertStatus(302);  // Redirection status
        $response->assertRedirect(route('login'));
        $response->assertSessionHasNoErrors();
    }


    public function testLoginWithInvalidPasswordFailured()
    {
        // Test Data Setup
        $loggedAdmin = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        // Test Action
        $response = $this->post(route('login'), [
            'email'    => $loggedAdmin->email,
            'password' => 'invalid-password',
        ]);

        // Check Assert
        $response->assertStatus(302);  // Redirection status
        $response->assertRedirect('/');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function testLoginWithRememberMeOn()
    {
        // Test Data Setup
        $loggedAdmin = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        // Test Action
        $response = $this->post(route('login'), [
            'email'    => $loggedAdmin->email,
            'password' => 'correct-password',
            'remember' => 'on',
        ]);

        $response->assertStatus(302);  // Redirection status
        $response->assertRedirect(route('admin.dashboard.index'));
        $response->assertSessionHasNoErrors();
        $response->assertSessionHasNoErrors();
    }


    public function testAdminForgetPasswordFormOpened()
    {
        // Test Data Setup

        // Test Action
        $response = $this->get(route('password.request'));

        // Check Assert
        $response->assertStatus(HTTP_RESPONSE_OK); // 200
        $response->assertViewIs('auth.forgot-password');
        $response->assertSessionHasNoErrors();
    }

    public function testAdminGotPasswordResetLinkEmail()
    {
        // Test Data Setup
        Notification::fake();

        $loggedAdmin = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        // Test Action
        $response = $this->post(route('password.email'), [
            'email' => $loggedAdmin->email,
        ]);

        // Check Assert
        $response->assertStatus(HTTP_RESPONSE_OK); // 200
        Notification::assertSentTo($loggedAdmin, ResetPassword::class, function ($notification) use ($loggedAdmin) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $loggedAdmin->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response->assertSessionHasNoErrors();
            return true;
        });
    }

    public function testEmailVerificationScreenCanBeRendered()
    {
        // Test Data Setup
        $loggedAdmin = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // Test Action
        $response = $this->actingAs($loggedAdmin)->get('/verify-email');

        // Check Assert
        $response->assertViewIs('auth.verify-email');
        $response->assertStatus(HTTP_RESPONSE_OK); // 200
    }

    public function testEmailCanBeVerified()
    {
        // Test Data Setup
        Event::fake();
        $loggedAdmin = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // Test Action
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $loggedAdmin->id, 'hash' => sha1($loggedAdmin->email)]
        );
        $response = $this->actingAs($loggedAdmin)->get($verificationUrl);

        // Check Assert
        Event::assertDispatched(Verified::class);
        $this->assertTrue($loggedAdmin->fresh()->hasVerifiedEmail());
        $response->assertRedirect(RouteServiceProvider::HOME.'?verified=1');
    }

    public function testEmailIsNotVerifiedWithInvalidHash()
    {
        // Test Data Setup
        $loggedAdmin = User::factory()->create([
            'email_verified_at' => null,
        ]);
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $loggedAdmin->id, 'hash' => sha1('wrong-email')]
        );

        // Test Action
        $this->actingAs($loggedAdmin)->get($verificationUrl);

        // Check Assert
        $this->assertFalse($loggedAdmin->fresh()->hasVerifiedEmail());
    }

    public function testRegistrationFormOpened()
    {
        // Test Data Setup
        $response = $this->get('/register');

        // Check Assert
        $response->assertStatus(200);
        $response->assertViewIs('auth.register');
        $response->assertSessionHasNoErrors();
    }


    public function testUserCanRegisteredWithoutDashboardAccess()
    {
        // Test Data Setup
        $newNotAdminUser = User::factory()->make()->toArray();
        $newNotAdminUser['is_admin'] = false;
        $newNotAdminUser['password'] = 'correct-password';
        $newNotAdminUser['password_confirmation'] = 'correct-password';

        // Test Action
        $response = $this->post('/register', $newNotAdminUser);

        // Check Assert
        $response->assertRedirect(RouteServiceProvider::HOME);
        $response->assertSessionHasNoErrors();

        $response = $this->post(route('login'), [
            'email'    => $newNotAdminUser['email'],
            'password' => 'correct-password',
        ]);

        // Test Action
        $response = $this->get(route('admin.dashboard.index'));

        // Check Assert
        $this->assertAuthenticated();
        $response->assertRedirect(route('logout.perform'));
    }

}
