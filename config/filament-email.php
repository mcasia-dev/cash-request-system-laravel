<?php

use RickDBCN\FilamentEmail\Filament\Resources\EmailResource;
use RickDBCN\FilamentEmail\Models\Email;

return [

    'resource' => [
        'class' => EmailResource::class,
        'model' => Email::class,
        'cluster' => null,
        'group' => "Logs",
        'sort' => null,
        'icon' => null,
        'default_sort_column' => 'created_at',
        'default_sort_direction' => 'desc',
        'datetime_format' => 'Y-m-d H:i:s',
        'table_search_fields' => [
            'subject',
            'from',
            'to',
            'cc',
            'bcc',
        ],
        'has_title_case_model_label' => false,
    ],

    'keep_email_for_days' => 60,

    'label' => "Email Logs",

    'prune_enabled' => true,

    'prune_crontab' => '0 0 * * *',

    'can_access' => [
        'role' => [
            'super_admin',
            'Super Admin',
        ],
    ],

    'pagination_page_options' => [
        10, 25, 50, 'all',
    ],

    'attachments_disk' => 'local',
    'store_attachments' => true,

    // Custom tenancy options
    // 'tenant_model' => \App\Models\Team::class,
    // 'scope_to_tenant' => true

];
