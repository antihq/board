<?php

namespace App\Http\Controllers;

use App\Models\TeamInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class TeamInvitationAcceptController extends Controller
{
    public function __invoke(TeamInvitation $invitation): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $team = $invitation->team;

        $team->addMember($user);
        $invitation->delete();
        $user->switchTeam($team);

        return redirect('/dashboard');
    }
}
