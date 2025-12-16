<?php

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('allows a user to accept a team invitation and join', function () {
    $team = Team::factory()->create();
    $invitedUser = User::factory()->create();
    $invitation = TeamInvitation::factory()
        ->create([
            'team_id' => $team->id,
            'email' => $invitedUser->email,
        ]);

    $signedUrl = url()->signedRoute('teams.invitations.accept', $invitation);
    $response = actingAs($invitedUser)->get($signedUrl);

    expect($team->members()->where('user_id', $invitedUser->id)->exists())->toBeTrue();
    expect($invitedUser->refresh()->currentTeam->is($team))->toBeTrue();
    expect(TeamInvitation::find($invitation->id))->toBeNull();
    $response->assertRedirect('/dashboard');
});
