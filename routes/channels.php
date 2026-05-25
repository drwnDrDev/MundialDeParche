<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Presence channel for the global quinela room
// Laravel maps this to 'presence-quinela' on the client
Broadcast::channel('quinela', function (User $user) {
    if (! $user->is_active) {
        return false;
    }

    return [
        'id'     => $user->id,
        'name'   => $user->name,
        'avatar' => $user->avatar,
    ];
});

// Private channel per user (points, lock notifications)
// Inactive users cannot log in, so this callback is only reachable by authenticated users.
Broadcast::channel('user.{id}', function (User $user, $id) {
    return $user->id === (int) $id;
});
