<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->role = Role::firstOrCreate(['name' => 'admin']);
});

function makeWebUser(Role $role, bool $isActive = true): User
{
    return User::create([
        'name' => 'Web User',
        'email' => uniqid().'@pos.com',
        'password' => Hash::make('password'),
        'role_id' => $role->id,
        'is_active' => $isActive,
    ]);
}

test('login page shows form', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('name="email"', false)
        ->assertSee('name="password"', false);
});

test('valid credentials log in and redirect', function () {
    $user = makeWebUser($this->role);

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

test('wrong password is rejected', function () {
    $user = makeWebUser($this->role);

    $this->from('/login')
        ->post('/login', ['email' => $user->email, 'password' => 'wrong'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('inactive user cannot log in', function () {
    $user = makeWebUser($this->role, isActive: false);

    $this->from('/login')
        ->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});
