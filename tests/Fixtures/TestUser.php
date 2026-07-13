<?php

namespace Tests\Fixtures;

use EloquentWorks\Exile\Traits\Bannable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

final class TestUser extends Authenticatable
{
    use Bannable;
    use Notifiable;

    protected $table = 'users';

    /** @var list<string> */
    protected $fillable = ['name', 'email'];
}
