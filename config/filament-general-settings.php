<?php

return [
    'show_application_tab' => true,
    'show_logo_and_favicon' => true,
    'show_analytics_tab' => false,
    'show_seo_tab' => false,
    'show_email_tab' => false,
    'show_social_networks_tab' => false,
    'expiration_cache_config_time' => 60,
    'show_custom_tabs' => true,
    'custom_tabs' => [
        'more_configs' => [
            'label' => 'Aging',
            'icon' => 'heroicon-o-plus-circle',
            'columns' => 1,
            'fields' => [
                'aging_field' => [
                    'type' => \Joaopaulolndev\FilamentGeneralSettings\Enums\TypeFieldEnum::Text->value,
                    'label' => 'Number of days',
                    'placeholder' => 'Number of days that will added in aging',
                    'required' => true,
                    'rules' => 'required|string|max:255',
                ],
            ]
        ],
    ]
];
