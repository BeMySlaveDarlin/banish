<?php

declare(strict_types=1);

namespace App\Domain\Admin\Enum;

enum AdminActionType: string
{
    case CONFIG_UPDATE = 'config_update';
    case UNBAN_USER = 'unban_user';
    case USER_LIST_VIEW = 'user_list_view';
    case USER_DETAILS_VIEW = 'user_details_view';
    case AUTH_LOGIN = 'auth_login';
    case AUTH_LOGOUT = 'auth_logout';
}
