<?php

namespace Tests\Feature;

use App\Models\PanelUser;
use App\Support\CustomerContext;
use App\Support\PanelAccess;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserAdminTest extends TestCase
{
    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/klient/uzytkownicy')->assertRedirect('/klient');
        $this->get('/klient/profil')->assertRedirect('/klient');
    }

    public function test_shared_login_is_regular_user(): void
    {
        $client = $this->makeClient(['login' => 'firma', 'nazwa' => 'FIRMA', 'IMGW' => 1, 'mapaWarunkow' => 1]);
        $this->post('/klient', ['login' => 'firma', 'password' => 'secret'])->assertRedirect('/klient');

        $this->get('/klient')->assertOk()->assertDontSee('id="usersAdmin"', false);
        $this->get('/klient/uzytkownicy')->assertForbidden();
        $this->assertSame(PanelAccess::LEVEL_USER, PanelAccess::actorLevel());
    }

    public function test_system_admin_can_create_user_and_edit_shared_account(): void
    {
        $admin = $this->makeClient(['login' => 'admin', 'nazwa' => 'admin', 'IMGW' => 0]);
        $client = $this->makeClient(['login' => 'firma', 'nazwa' => 'FIRMA', 'IMGW' => 1, 'mapaWarunkow' => 1]);
        $this->actingAs($admin);
        CustomerContext::put($client);

        $page = $this->get('/klient/uzytkownicy')->assertOk();
        $page->assertSee('Konto wspólne')
            ->assertSee('Dodaj użytkownika')
            ->assertSee('users-modal', false)
            ->assertSee('firma');

        $this->post('/klient/uzytkownicy', [
            'rodzaj' => 'user',
            'id' => 0,
            'login' => 'ania',
            'nazwa' => 'Anna',
            'poziom' => PanelAccess::LEVEL_USER,
            'haslo' => 'tajne',
            'aktywny' => 1,
            'mod_tabela' => 1,
            'mod_analiza' => 1,
        ])->assertRedirect(route('klient.users'));

        $user = PanelUser::query()->where('login', 'ania')->first();
        $this->assertNotNull($user);
        $this->assertSame($client->id, (int) $user->id_klient);
        $this->assertSame('Anna', $user->nazwa);
        $this->assertSame(1, (int) $user->mod_tabela);
        $this->assertSame(0, (int) $user->mod_mapa);
        $this->assertSame(0, (int) $user->mod_gfs);
        $this->assertTrue(Hash::check('tajne', $user->haslo));

        $this->post('/klient/uzytkownicy', [
            'rodzaj' => 'firma',
            'id' => $client->id,
            'login' => 'firma2',
            'poziom' => PanelAccess::LEVEL_USER,
            'haslo' => 'nowehaslo',
            'mod_tabela' => 1,
        ])->assertRedirect(route('klient.users'));

        $fresh = $client->fresh();
        $this->assertSame('firma2', $fresh->login);
        $this->assertSame(1, (int) $fresh->poziom_konta);
        $this->assertSame(0, (int) $fresh->mod_mapa);
        $this->assertTrue(Hash::check('nowehaslo', $fresh->haslo));

        $this->post('/klient/logout');
        $this->from('/klient')->post('/klient', [
            'login' => 'firma',
            'password' => 'secret',
        ])->assertSessionHasErrors('login');
        $this->post('/klient', [
            'login' => 'firma2',
            'password' => 'nowehaslo',
        ])->assertRedirect('/klient');
        $html = $this->get('/klient')->assertOk()->getContent();
        $this->assertStringContainsString('class="t1a nav-btn hidden" id="imgwMapNew2Tab"', $html);
        $this->assertStringContainsString('class="t1a nav-btn" id="imgwTableNew2Tab"', $html);
    }

    public function test_login_must_not_copy_company_login(): void
    {
        $admin = $this->makeClient(['login' => 'admin', 'nazwa' => 'admin']);
        $client = $this->makeClient(['login' => 'firma', 'nazwa' => 'FIRMA']);
        $this->actingAs($admin);
        CustomerContext::put($client);

        $this->from('/klient/uzytkownicy')->post('/klient/uzytkownicy', [
            'id' => 0,
            'login' => 'firma',
            'poziom' => 1,
            'haslo' => 'tajne',
            'aktywny' => 1,
        ])->assertRedirect('/klient/uzytkownicy')->assertSessionHasErrors('login');
        $this->assertSame(0, PanelUser::query()->count());
    }

    public function test_panel_user_login_limits_menu_and_content(): void
    {
        $client = $this->makeClient([
            'login' => 'firma',
            'nazwa' => 'FIRMA',
            'IMGW' => 1,
            'mapaWarunkow' => 1,
        ]);
        $this->makePanelUser($client->id, [
            'login' => 'ania',
            'mod_tabela' => 1,
            'mod_mapa' => 0,
            'mod_gfs' => 0,
            'mod_analiza' => 0,
            'mod_dodatki' => 0,
        ]);

        $this->post('/klient', ['login' => 'ania', 'password' => 'tajne'])
            ->assertRedirect('/klient');

        $page = $this->get('/klient')->assertOk();
        $html = $page->getContent();
        $this->assertStringContainsString('class="t1a nav-btn" id="imgwTableNew2Tab"', $html);
        $this->assertStringContainsString('class="t1a nav-btn hidden" id="imgwMapNew2Tab"', $html);
        $this->assertStringContainsString('class="t1a nav-btn hidden" id="analizaTab"', $html);
        $this->assertStringContainsString('class="t1a nav-btn hidden" id="gfsMapTab"', $html);
        $this->assertStringContainsString('class="t1a nav-btn hidden" id="addonsTab"', $html);
        $page->assertSee('Mój profil', false);
        $page->assertDontSee('id="usersAdmin"', false);
        $this->assertSame('FIRMA', CustomerContext::get()['nazwa']);
        $this->assertSame('ania', session('panel_user.login'));

        $this->post('/klient/content', ['tab' => 'imgwMapNew2Tab'])->assertForbidden();
        $this->post('/klient/content', ['tab' => 'gfsMapTab'])->assertForbidden();
        $this->post('/klient/analiza', ['mode' => 'hour'])->assertForbidden();
        $this->get('/klient/gfs/klatki/2026.11.24.00/Polska/synop3')->assertForbidden();
        $this->get('/klient/uzytkownicy')->assertForbidden();

        $tabs = $this->postJson('/klient/actualtabs')->json();
        $this->assertSame(1, $tabs['imgwTableNew2Tab']['active']);
        $this->assertSame(0, $tabs['imgwMapNew2Tab']['active']);
        $this->assertSame(0, $tabs['analizaTab']['active']);
    }

    public function test_inactive_panel_user_cannot_login(): void
    {
        $client = $this->makeClient(['login' => 'firma']);
        $this->makePanelUser($client->id, ['login' => 'ania', 'aktywny' => 0]);

        $this->from('/klient')->post('/klient', [
            'login' => 'ania',
            'password' => 'tajne',
        ])->assertRedirect('/klient')->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_user_can_change_own_login_and_password(): void
    {
        $client = $this->makeClient(['login' => 'firma', 'nazwa' => 'FIRMA']);
        $this->makePanelUser($client->id, ['login' => 'ania']);
        $this->post('/klient', ['login' => 'ania', 'password' => 'tajne']);

        $this->from('/klient/profil')->post('/klient/profil', [
            'login' => 'ania2',
            'haslo' => 'nowehaslo',
            'haslo_confirmation' => 'inne',
            'obecne' => 'tajne',
        ])->assertSessionHasErrors('haslo');

        $this->from('/klient/profil')->post('/klient/profil', [
            'login' => 'ania2',
            'haslo' => 'nowehaslo',
            'haslo_confirmation' => 'nowehaslo',
            'obecne' => 'zle',
        ])->assertSessionHasErrors('obecne');

        $this->post('/klient/profil', [
            'login' => 'ania2',
            'haslo' => 'nowehaslo',
            'haslo_confirmation' => 'nowehaslo',
            'obecne' => 'tajne',
        ])->assertRedirect(route('klient.profile'));

        $this->post('/klient/logout');
        $this->post('/klient', [
            'login' => 'ania',
            'password' => 'tajne',
        ])->assertSessionHasErrors('login');
        $this->post('/klient', [
            'login' => 'ania2',
            'password' => 'nowehaslo',
        ])->assertRedirect('/klient');
        $this->assertSame('ania2', session('panel_user.login'));
    }

    public function test_administrator_cannot_touch_super_admin(): void
    {
        $client = $this->makeClient(['login' => 'firma', 'nazwa' => 'FIRMA']);
        $admin = $this->makePanelUser($client->id, [
            'login' => 'adminlok',
            'poziom' => PanelAccess::LEVEL_ADMIN,
        ]);
        $super = $this->makePanelUser($client->id, [
            'login' => 'szef',
            'poziom' => PanelAccess::LEVEL_SUPER,
        ]);
        $this->loginPanel('adminlok');

        $this->from('/klient/uzytkownicy')->post('/klient/uzytkownicy', [
            'id' => 0,
            'login' => 'nowy',
            'poziom' => PanelAccess::LEVEL_SUPER,
            'haslo' => 'tajne',
            'aktywny' => 1,
        ])->assertSessionHasErrors('poziom');

        $this->post('/klient/uzytkownicy/usun', ['id' => $super->id])->assertForbidden();
        $this->get('/klient/uzytkownicy?edycja='.$super->id)->assertForbidden();
        $this->assertNotNull($super->fresh());

        $this->post('/klient/uzytkownicy/usun', ['id' => $admin->id])->assertForbidden();
        $this->assertNotNull($admin->fresh());
    }

    public function test_super_admin_can_edit_and_delete_users(): void
    {
        $client = $this->makeClient(['login' => 'firma', 'nazwa' => 'FIRMA']);
        $this->makePanelUser($client->id, [
            'login' => 'szef',
            'poziom' => PanelAccess::LEVEL_SUPER,
        ]);
        $user = $this->makePanelUser($client->id, ['login' => 'ania']);
        $this->loginPanel('szef');

        $this->post('/klient/uzytkownicy', [
            'id' => $user->id,
            'login' => 'ania',
            'nazwa' => 'Anna',
            'poziom' => PanelAccess::LEVEL_USER,
            'haslo' => '',
            'aktywny' => 1,
            'mod_mapa' => 1,
        ])->assertRedirect(route('klient.users'));

        $fresh = $user->fresh();
        $this->assertSame(1, (int) $fresh->mod_mapa);
        $this->assertSame(0, (int) $fresh->mod_tabela);
        $this->assertTrue($fresh->passwordMatches('tajne'));

        $this->post('/klient/uzytkownicy/usun', ['id' => $user->id])->assertRedirect(route('klient.users'));
        $this->assertNull($user->fresh());

        $self = PanelUser::query()->where('login', 'szef')->first();
        $this->post('/klient/uzytkownicy', [
            'rodzaj' => 'user',
            'id' => $self->id,
            'login' => 'szef',
            'nazwa' => 'Szef zmiany',
            'poziom' => PanelAccess::LEVEL_SUPER,
            'haslo' => '',
            'aktywny' => 1,
            'mod_tabela' => 1,
            'mod_mapa' => 1,
            'mod_gfs' => 1,
            'mod_analiza' => 1,
            'mod_dodatki' => 1,
        ])->assertRedirect(route('klient.users'));
        $this->assertSame('Szef zmiany', $self->fresh()->nazwa);
    }

    public function test_system_admin_manages_selected_client_only(): void
    {
        $this->makeClient(['login' => 'admin', 'nazwa' => 'admin', 'IMGW' => 0]);
        $alpha = $this->makeClient(['login' => 'a', 'nazwa' => 'ALPHA']);
        $beta = $this->makeClient(['login' => 'b', 'nazwa' => 'BETA']);
        $this->post('/klient', ['login' => 'admin', 'password' => 'secret']);

        $this->get('/klient/uzytkownicy')
            ->assertOk()
            ->assertSee('Wybierz klienta');

        $this->post('/klient/setcustomer', ['id' => $beta->id])->assertOk();
        $this->post('/klient/uzytkownicy', [
            'id' => 0,
            'login' => 'beta1',
            'poziom' => PanelAccess::LEVEL_ADMIN,
            'haslo' => 'tajne',
            'aktywny' => 1,
            'mod_gfs' => 1,
        ])->assertRedirect(route('klient.users'));

        $created = PanelUser::query()->where('login', 'beta1')->first();
        $this->assertSame($beta->id, (int) $created->id_klient);
        $this->assertNotSame($alpha->id, (int) $created->id_klient);
    }

    public function test_deactivated_user_loses_session(): void
    {
        $client = $this->makeClient(['login' => 'firma', 'nazwa' => 'FIRMA']);
        $user = $this->makePanelUser($client->id, ['login' => 'ania']);
        $this->loginPanel('ania');
        $user->aktywny = 0;
        $user->save();

        $this->get('/klient')->assertRedirect('/klient');
        $this->assertGuest();
    }

    private function makePanelUser(int $clientId, array $overrides = []): PanelUser
    {
        return PanelUser::query()->create(array_merge([
            'id_klient' => $clientId,
            'login' => 'ania',
            'haslo' => Hash::make('tajne'),
            'auth_key' => bin2hex(random_bytes(16)),
            'nazwa' => '',
            'poziom' => PanelAccess::LEVEL_USER,
            'aktywny' => 1,
            'mod_tabela' => 1,
            'mod_mapa' => 1,
            'mod_gfs' => 1,
            'mod_analiza' => 1,
            'mod_dodatki' => 1,
        ], $overrides));
    }

    private function loginPanel(string $login): void
    {
        $this->post('/klient', [
            'login' => $login,
            'password' => 'tajne',
        ])->assertRedirect('/klient');
    }
}
