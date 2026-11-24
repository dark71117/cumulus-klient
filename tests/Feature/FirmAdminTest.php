<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\PanelUser;
use App\Support\CustomerContext;
use App\Support\PanelAccess;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FirmAdminTest extends TestCase
{
    public function test_company_admin_cannot_open_firms(): void
    {
        $client = $this->makeClient(['login' => 'firma', 'nazwa' => 'FIRMA']);
        $this->makePanelUser($client->id, [
            'login' => 'szef',
            'poziom' => PanelAccess::LEVEL_ADMIN,
        ]);
        $this->post('/klient', ['login' => 'szef', 'password' => 'tajne']);

        $this->get('/klient')->assertOk()->assertDontSee('id="firmsAdmin"', false);
        $this->get('/klient/firmy')->assertForbidden();
    }

    public function test_super_admin_adds_company_then_users_within_its_modules(): void
    {
        $home = $this->makeClient(['login' => 'test', 'nazwa' => 'TEST']);
        $this->makePanelUser($home->id, [
            'login' => 'szef',
            'poziom' => PanelAccess::LEVEL_SUPER,
        ]);
        $this->post('/klient', ['login' => 'szef', 'password' => 'tajne']);

        $this->get('/klient/firmy')
            ->assertOk()
            ->assertSee('Dodaj firmę', false)
            ->assertSee('>Konta<', false);

        $this->post('/klient/firmy', [
            'id' => 0,
            'nazwa' => 'FIRMAXX',
            'login' => 'firmaxx',
            'haslo' => 'firmaxxhaslo',
            'aktywny' => 1,
            'mod_tabela' => 1,
            'mod_mapa' => 1,
            'mod_gfs' => 1,
            'mod_analiza' => 1,
        ])->assertRedirect(route('klient.firms'));

        $firm = Client::query()->where('nazwa', 'FIRMAXX')->first();
        $this->assertNotNull($firm);
        $this->assertSame(1, (int) $firm->aktywny);
        $this->assertSame(0, (int) $firm->mod_dodatki);
        $this->assertSame(1, (int) $firm->mod_tabela);

        $this->get('/klient/uzytkownicy?firma='.$firm->id)
            ->assertOk()
            ->assertSee('FIRMAXX')
            ->assertSee('name="mod_tabela"', false)
            ->assertSee('name="mod_analiza"', false)
            ->assertDontSee('name="mod_dodatki"', false);

        $this->post('/klient/uzytkownicy', [
            'firma' => $firm->id,
            'rodzaj' => 'user',
            'id' => 0,
            'login' => 'userxx',
            'nazwa' => 'User XX',
            'poziom' => PanelAccess::LEVEL_USER,
            'aktywny' => 1,
            'haslo' => 'hasloxx',
            'mod_tabela' => 1,
            'mod_mapa' => 1,
            'mod_gfs' => 1,
            'mod_dodatki' => 1,
        ])->assertRedirect(route('klient.users'));

        $limited = PanelUser::query()->where('login', 'userxx')->first();
        $this->assertSame($firm->id, (int) $limited->id_klient);
        $this->assertSame(1, (int) $limited->mod_tabela);
        $this->assertSame(1, (int) $limited->mod_mapa);
        $this->assertSame(1, (int) $limited->mod_gfs);
        $this->assertSame(0, (int) $limited->mod_analiza);
        $this->assertSame(0, (int) $limited->mod_dodatki);

        $this->post('/klient/uzytkownicy', [
            'firma' => $firm->id,
            'rodzaj' => 'user',
            'id' => 0,
            'login' => 'userxy',
            'nazwa' => 'User XY',
            'poziom' => PanelAccess::LEVEL_USER,
            'aktywny' => 1,
            'haslo' => 'hasloxy',
            'mod_tabela' => 1,
            'mod_mapa' => 1,
            'mod_gfs' => 1,
            'mod_analiza' => 1,
        ])->assertRedirect(route('klient.users'));

        $full = PanelUser::query()->where('login', 'userxy')->first();
        $this->assertSame(1, (int) $full->mod_tabela);
        $this->assertSame(1, (int) $full->mod_mapa);
        $this->assertSame(1, (int) $full->mod_gfs);
        $this->assertSame(1, (int) $full->mod_analiza);
        $this->assertNotSame($home->id, (int) $full->id_klient);

        $this->post('/klient/firmy', [
            'id' => $firm->id,
            'nazwa' => 'FIRMAXX',
            'login' => 'firmaxx',
            'aktywny' => 0,
            'mod_tabela' => 1,
        ])->assertRedirect(route('klient.firms'));
        $this->assertSame(0, (int) $firm->fresh()->aktywny);

        $this->post('/klient/logout');
        $this->from('/klient')->post('/klient', [
            'login' => 'userxy',
            'password' => 'hasloxy',
        ])->assertSessionHasErrors('login');
    }

    public function test_system_admin_renames_company_and_limits_modules(): void
    {
        $admin = $this->makeClient(['login' => 'admin', 'nazwa' => 'admin', 'IMGW' => 0]);
        $client = $this->makeClient([
            'login' => 'test',
            'nazwa' => 'TEST',
            'IMGW' => 1,
            'mapaWarunkow' => 1,
            'mod_tabela' => 1,
            'mod_mapa' => 1,
            'mod_gfs' => 1,
            'mod_analiza' => 1,
            'mod_dodatki' => 1,
        ]);
        $user = $this->makePanelUser($client->id, [
            'login' => 'ania',
            'mod_tabela' => 1,
            'mod_mapa' => 1,
            'mod_gfs' => 1,
        ]);
        $this->actingAs($admin);
        CustomerContext::put($client);

        $this->get('/klient')->assertOk()->assertSee('id="firmsAdmin"', false);
        $this->get('/klient/firmy')
            ->assertOk()
            ->assertSee('TEST')
            ->assertSee('NOWA FIRMA', false);

        $this->post('/klient/firmy', [
            'id' => $client->id,
            'nazwa' => 'Port Lotniczy',
            'login' => 'test',
            'haslo' => '',
            'mod_tabela' => 1,
            'mod_analiza' => 1,
        ])->assertRedirect(route('klient.firms'));

        $fresh = $client->fresh();
        $this->assertSame('Port Lotniczy', $fresh->nazwa);
        $this->assertSame('test', $fresh->login);
        $this->assertSame(1, (int) $fresh->mod_tabela);
        $this->assertSame(0, (int) $fresh->mod_mapa);
        $this->assertSame(0, (int) $fresh->mod_gfs);
        $this->assertSame(1, (int) $fresh->IMGW);
        $this->assertSame(0, (int) $user->fresh()->mod_mapa);
        $this->assertSame(1, (int) $user->fresh()->mod_tabela);
        $this->assertSame('Port Lotniczy', CustomerContext::get()['nazwa']);
        $this->get('/klient/uzytkownicy')
            ->assertOk()
            ->assertSee('name="mod_tabela"', false)
            ->assertDontSee('name="mod_mapa"', false);

        $this->post('/klient/uzytkownicy', [
            'rodzaj' => 'user',
            'id' => $user->id,
            'login' => 'ania',
            'nazwa' => 'Anna',
            'poziom' => PanelAccess::LEVEL_USER,
            'aktywny' => 1,
            'haslo' => '',
            'mod_tabela' => 1,
            'mod_mapa' => 1,
            'mod_gfs' => 1,
        ])->assertRedirect(route('klient.users'));
        $this->assertSame(0, (int) $user->fresh()->mod_mapa);
        $this->assertSame(0, (int) $user->fresh()->mod_gfs);

        $this->post('/klient/firmy', [
            'id' => 0,
            'nazwa' => 'Nowa Firma',
            'login' => 'nowa',
            'haslo' => 'tajnehaslo',
            'mod_mapa' => 1,
        ])->assertRedirect(route('klient.firms'));
        $created = Client::query()->where('login', 'nowa')->first();
        $this->assertNotNull($created);
        $this->assertSame('Nowa Firma', $created->nazwa);
        $this->assertSame(1, (int) $created->mod_mapa);
        $this->assertSame(0, (int) $created->mod_tabela);
        $this->assertSame(1, (int) $created->mapaWarunkow);
        $this->assertTrue(Hash::check('tajnehaslo', $created->haslo));
    }

    public function test_logged_in_user_loses_module_removed_from_company(): void
    {
        $client = $this->makeClient([
            'login' => 'firma',
            'nazwa' => 'FIRMA',
            'IMGW' => 1,
            'mapaWarunkow' => 1,
            'mod_tabela' => 1,
            'mod_mapa' => 0,
        ]);
        $this->makePanelUser($client->id, [
            'login' => 'ania',
            'nazwa' => 'Anna Test',
            'mod_tabela' => 1,
            'mod_mapa' => 1,
            'mod_gfs' => 0,
            'mod_analiza' => 0,
            'mod_dodatki' => 0,
        ]);
        $this->post('/klient', ['login' => 'ania', 'password' => 'tajne']);

        $html = $this->get('/klient')->assertOk()->getContent();
        $this->assertStringContainsString('class="t1a nav-btn" id="imgwTableNew2Tab"', $html);
        $this->assertStringContainsString('class="t1a nav-btn hidden" id="imgwMapNew2Tab"', $html);
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
}
