<?php

namespace App\Providers;

use App\Models;
use App\Policies;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Notifications\DatabaseNotification;
use Laravel\Sanctum\PersonalAccessToken;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Models\Address::class => Policies\AddressPolicy::class,
        Models\Administrator::class => Policies\AdministratorPolicy::class,
        Models\AdminRole::class => Policies\AdminRolePolicy::class,
        Models\AdminRolePermission::class => Policies\AdminRolePermissionPolicy::class,
        Models\Attachment::class => Policies\AttachmentPolicy::class,
        Models\BlackList::class => Policies\BlackListPolicy::class,
        Models\Category::class => Policies\CategoryPolicy::class,
        Models\Content::class => Policies\ContentPolicy::class,
        DatabaseNotification::class => Policies\DatabaseNotificationPolicy::class,
        Models\Examine::class => Policies\ExaminePolicy::class,
        Export::class => Policies\ExportPolicy::class,
        Models\FailedJob::class => Policies\FailedJobPolicy::class,
        Import::class => Policies\ImportPolicy::class,
        Models\JobBatch::class => Policies\JobBatchPolicy::class,
        Models\LoginRecord::class => Policies\LoginRecordPolicy::class,
        Models\Module::class => Policies\ModulePolicy::class,
        Models\OperationLog::class => Policies\OperationLogPolicy::class,
        PersonalAccessToken::class => Policies\PersonalAccessTokenPolicy::class,
        Models\Region::class => Policies\RegionPolicy::class,
        Models\Role::class => Policies\RolePolicy::class,
        Models\RolePermission::class => Policies\RolePermissionPolicy::class,
        Models\Sensitive::class => Policies\SensitivePolicy::class,
        Models\Setting::class => Policies\SettingPolicy::class,
        Models\SmsCode::class => Policies\SmsCodePolicy::class,
        Models\Staffer::class => Policies\StafferPolicy::class,
        Models\System::class => Policies\SystemPolicy::class,
        Models\User::class => Policies\UserPolicy::class,
        Models\UserInfo::class => Policies\UserInfoPolicy::class,
    ];
}
