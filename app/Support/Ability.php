<?php

namespace App\Support;

final class Ability
{
    public const USERS_CREATE = 'users.create';

    public const USERS_UPDATE = 'users.update';

    public const USERS_DELETE = 'users.delete';

    public const TENANTS_CREATE = 'tenants.create';

    public const TENANTS_VIEW = 'tenants.view';

    public const TENANTS_SETTINGS_VIEW = 'tenants.settings.view';

    public const TENANTS_SETTINGS_UPDATE = 'tenants.settings.update';

    public const TENANTS_SETTINGS_DELETE = 'tenants.settings.delete';

    public const TENANTS_USERS_VIEW = 'tenants.users.view';

    public const TENANTS_USERS_ATTACH = 'tenants.users.attach';

    public const TENANTS_USERS_DETACH = 'tenants.users.detach';
}
