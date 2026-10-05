<?php

use Asyntai\Search\State;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->connectStore();
});

function adminWithPermissions(array $permissions): Admin
{
    $role = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => $permissions,
    ]);

    return Admin::factory()->create(['role_id' => $role->id]);
}

it('should list the three permissions in the role tree', function () {
    // Act.
    $keys = collect(config('acl'))->pluck('key')->all();

    // Assert.
    expect($keys)->toContain('asyntai-search', 'asyntai-search.connection', 'asyntai-search.settings');
});

it('should open the page for an admin whose role has all permissions', function () {
    // Arrange.
    $this->loginAsAdmin();

    // Act and Assert.
    get(route('admin.asyntai_search.index'))
        ->assertOk()
        ->assertSee('id="asyntai-disconnect"', false)
        ->assertSee('id="asyntai-save"', false);
});

it('should refuse the page to a role without the AI Search permission', function () {
    // Arrange.
    $this->loginAsAdmin(adminWithPermissions(['sales', 'sales.orders', 'sales.orders.view']));

    // Act.
    $status = get(route('admin.asyntai_search.index'))->getStatusCode();

    // Assert.
    expect($status)->toBeIn([401, 403]);
});

it('should hide the menu entry from a role without the AI Search permission', function () {
    // Arrange.
    $this->loginAsAdmin(adminWithPermissions(['sales', 'sales.orders', 'sales.orders.view']));

    // Act and Assert.
    expect(bouncer()->hasPermission('asyntai-search'))->toBeFalse();

    get(route('admin.sales.orders.index'))
        ->assertOk()
        ->assertDontSee(route('admin.asyntai_search.index'), false);
});

it('should refuse every action to a role without the AI Search permission', function () {
    // Arrange.
    $this->loginAsAdmin(adminWithPermissions(['sales', 'sales.orders', 'sales.orders.view']));

    // Act and Assert.
    foreach (['prepare', 'poll', 'finish', 'disconnect', 'refresh', 'settings'] as $action) {
        $status = postJson(route('admin.asyntai_search.' . $action), ['state' => str_repeat('x', 40)])
            ->getStatusCode();

        expect($status)->toBeIn([401, 403], $action);
    }

    expect(State::feedToken())->toBe($this->feedToken);
});

it('should let a view-only role see the page but change nothing', function () {
    // Arrange.
    $this->loginAsAdmin(adminWithPermissions(['asyntai-search']));

    // Act and Assert.
    get(route('admin.asyntai_search.index'))
        ->assertOk()
        ->assertDontSee('id="asyntai-disconnect"', false)
        ->assertDontSee('id="asyntai-save"', false);

    postJson(route('admin.asyntai_search.refresh'))->assertOk();

    foreach (['prepare', 'poll', 'finish', 'disconnect', 'settings'] as $action) {
        $status = postJson(route('admin.asyntai_search.' . $action), ['state' => str_repeat('x', 40)])
            ->getStatusCode();

        expect($status)->toBeIn([401, 403], $action);
    }

    expect(State::siteId())->toBe($this->siteId);
});

it('should let a settings role save settings but not disconnect', function () {
    // Arrange.
    $this->loginAsAdmin(adminWithPermissions(['asyntai-search', 'asyntai-search.settings']));

    // Act and Assert.
    postJson(route('admin.asyntai_search.settings'), ['placement' => 'manual'])
        ->assertOk();

    expect(State::placement())->toBe('manual');

    expect(postJson(route('admin.asyntai_search.disconnect'))->getStatusCode())->toBeIn([401, 403]);

    expect(State::feedToken())->toBe($this->feedToken);
});

it('should let a connection role disconnect but not save settings', function () {
    // Arrange.
    $this->loginAsAdmin(adminWithPermissions(['asyntai-search', 'asyntai-search.connection']));

    // Act and Assert.
    expect(postJson(route('admin.asyntai_search.settings'), ['placement' => 'manual'])->getStatusCode())
        ->toBeIn([401, 403]);

    postJson(route('admin.asyntai_search.disconnect'))->assertOk();

    expect(State::feedToken())->toBe('');
});
