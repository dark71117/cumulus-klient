<?php

namespace Tests\Feature;

use App\Models\IpRange;
use App\Support\CustomerContext;
use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_guest_sees_login_form(): void
    {
        $this->get('/klient')->assertOk()->assertSee('Zaloguj');
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->makeClient();
        $this->from('/klient')->post('/klient', [
            'login' => 'test',
            'password' => 'bad',
        ])->assertRedirect('/klient')->assertSessionHasErrors('login');
    }

    public function test_valid_login_opens_panel(): void
    {
        $this->makeClient();
        $this->post('/klient', [
            'login' => 'test',
            'password' => 'secret',
        ])->assertRedirect('/klient');

        $page = $this->get('/klient')->assertOk();
        $page->assertSee('wyłączność strony dla')->assertSee('Wyloguj')->assertSee('app-sidebar');
        $this->assertSame(1, substr_count($page->getContent(), '>Wyloguj</button>'));
        $this->assertSame('TEST', CustomerContext::get()['nazwa']);
    }

    public function test_blocked_ip_range_rejects_login(): void
    {
        $client = $this->makeClient();
        IpRange::query()->create([
            'id_klient' => $client->id,
            'opis' => 'blokada testowa',
            'ip1' => '127.0.0.1',
            'ip2' => '127.0.0.1',
        ]);
        $this->from('/klient')->post('/klient', [
            'login' => 'test',
            'password' => 'secret',
        ])->assertSessionHasErrors('login');
    }

    public function test_admin_can_impersonate_client(): void
    {
        $admin = $this->makeClient([
            'login' => 'admin',
            'nazwa' => 'admin',
            'IMGW' => 0,
        ]);
        $target = $this->makeClient([
            'login' => 'firma',
            'nazwa' => 'FIRMA',
            'haslo' => $admin->haslo,
        ]);
        $this->actingAs($admin);
        CustomerContext::put($admin);
        $this->post('/klient/setcustomer', ['id' => $target->id])->assertOk();
        $this->assertSame('FIRMA', CustomerContext::get()['nazwa']);
        $this->assertTrue(auth()->id() === $admin->id);
    }

    public function test_admin_with_single_client_is_loaded_automatically(): void
    {
        $this->makeClient([
            'login' => 'admin',
            'nazwa' => 'admin',
            'IMGW' => 0,
        ]);
        $this->makeClient([
            'login' => 'testuser',
            'nazwa' => 'TEST',
        ]);
        $this->post('/klient', [
            'login' => 'admin',
            'password' => 'secret',
        ])->assertRedirect('/klient');

        $this->get('/klient')
            ->assertOk()
            ->assertSee('wyłączność strony dla')
            ->assertSee('TEST');
        $this->assertSame('TEST', CustomerContext::get()['nazwa']);
        $this->assertSame('admin', auth()->user()->nazwa);
    }

    public function test_admin_with_multiple_clients_does_not_auto_pick(): void
    {
        $this->makeClient(['login' => 'admin', 'nazwa' => 'admin']);
        $this->makeClient(['login' => 'a', 'nazwa' => 'ALPHA']);
        $this->makeClient(['login' => 'b', 'nazwa' => 'BETA']);
        $this->post('/klient', [
            'login' => 'admin',
            'password' => 'secret',
        ])->assertRedirect('/klient');

        $this->get('/klient')->assertOk();
        $this->assertSame('admin', CustomerContext::get()['nazwa']);
    }

    public function test_logout_does_not_overwrite_auth_key(): void
    {
        $client = $this->makeClient(['auth_key' => str_repeat('a', 32)]);
        $this->actingAs($client);
        CustomerContext::put($client);

        $this->post('/klient/logout')->assertRedirect('/klient');
        $this->assertGuest();
        $this->assertSame(str_repeat('a', 32), $client->fresh()->auth_key);
    }
}
