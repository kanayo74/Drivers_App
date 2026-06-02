<?php

namespace App\Http\Controllers\Admin;

// Minimal shim so legacy references to "User" inside the
// App\Http\Controllers\Admin namespace resolve to the real model.
// Recommended: replace usages with "use App\Models\User;" in each controller.
class User extends \App\Models\User
{
    // Intentionally empty — inherits everything from the Eloquent model.
}