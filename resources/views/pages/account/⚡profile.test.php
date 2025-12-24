<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('displays the profile page', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $team = $user->teams()->where('personal', true)->first();
    actingAs($user)->get(route('teams.account.profile', $team))->assertOk();
});

it('updates the profile information', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $team = $user->teams()->where('personal', true)->first();

    $component = Livewire::actingAs($user)->test('pages::account.profile', ['team' => $team])
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $component->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

it('keeps email verification status unchanged when email address is unchanged', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $team = $user->teams()->where('personal', true)->first();

    $component = Livewire::actingAs($user)->test('pages::account.profile', ['team' => $team])
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $component->assertHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

it('uploads a profile photo', function () {
    Storage::fake('public');

    $user = User::factory()->withPersonalTeam()->create();

    $team = $user->teams()->where('personal', true)->first();

    $photo = UploadedFile::fake()->image('profile.jpg', 200, 200);

    $component = Livewire::actingAs($user)->test('pages::account.profile', ['team' => $team])
        ->set('photo', $photo)
        ->call('updateProfileInformation');

    $component->assertHasNoErrors();

    $user->refresh();

    expect($user->profile_photo_path)->not->toBeNull();

    Storage::disk('public')->assertExists($user->profile_photo_path);
});

it('removes a profile photo', function () {
    Storage::fake('public');

    $user = User::factory()->withPersonalTeam()->create([
        'profile_photo_path' => 'profile-photos/test.jpg',
    ]);

    $team = $user->teams()->where('personal', true)->first();

    Storage::disk('public')->put($user->profile_photo_path, 'test content');

    Livewire::actingAs($user)->test('pages::account.profile', ['team' => $team])
        ->call('removePhoto');

    $user->refresh();

    expect($user->profile_photo_path)->toBeNull();

    Storage::disk('public')->assertMissing('profile-photos/test.jpg');
});

it('replaces an existing profile photo', function () {
    Storage::fake('public');

    $user = User::factory()->withPersonalTeam()->create([
        'profile_photo_path' => 'profile-photos/old.jpg',
    ]);

    $team = $user->teams()->where('personal', true)->first();

    Storage::disk('public')->put($user->profile_photo_path, 'old content');

    $newPhoto = UploadedFile::fake()->image('new-profile.jpg', 200, 200);

    Livewire::actingAs($user)->test('pages::account.profile', ['team' => $team])
        ->set('photo', $newPhoto)
        ->call('updateProfileInformation');

    $user->refresh();

    Storage::disk('public')->assertMissing('profile-photos/old.jpg');
    Storage::disk('public')->assertExists($user->profile_photo_path);
});
