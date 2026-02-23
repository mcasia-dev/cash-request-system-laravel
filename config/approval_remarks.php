<?php

use App\Enums\CashRequest\StatusRemarks;
use App\Enums\NatureOfRequestEnum;

return [
    'defaults' => [
        'approved'       => 'Approved',
        'rejected'       => 'Rejected',
        'released'       => 'Released',
        'no_permissions' => 'No permissions',
    ],

    'contexts' => [
        'approval' => [
            'approve_permissions' => [
                'can-approve-as-department-head'        => StatusRemarks::DEPARTMENT_HEAD_APPROVED_REQUEST->value,
                'can-approve-as-president'              => StatusRemarks::PRESIDENT_APPROVED_REQUEST->value,
                'can-approve-as-treasury-manager'       => StatusRemarks::TREASURY_MANAGER_APPROVED_REQUEST->value,
                'can-approve-as-treasury-supervisor'    => StatusRemarks::TREASURY_SUPERVISOR_APPROVED_REQUEST->value,
                'can-approve-as-sales-channel-manager'  => StatusRemarks::SALES_CHANNEL_MANAGER_APPROVED_REQUEST->value,
                'can-approve-as-national-sales-manager' => StatusRemarks::NATIONAL_SALES_MANAGER_APPROVED_REQUEST->value,
            ],
            'reject_permissions' => [
                'can-reject-as-department-head'        => StatusRemarks::DEPARTMENT_HEAD_REJECTED_REQUEST->value,
                'can-reject-as-president'              => StatusRemarks::PRESIDENT_REJECTED_REQUEST->value,
                'can-reject-as-treasury-manager'       => StatusRemarks::TREASURY_MANAGER_REJECTED_REQUEST->value,
                'can-reject-as-treasury-supervisor'    => StatusRemarks::TREASURY_SUPERVISOR_REJECTED_REQUEST->value,
                'can-reject-as-sales-channel-manager'  => StatusRemarks::SALES_CHANNEL_MANAGER_REJECTED_REQUEST->value,
                'can-reject-as-national-sales-manager' => StatusRemarks::NATIONAL_SALES_MANAGER_REJECTED_REQUEST->value,
            ],
        ],

        'treasury' => [
            'approve_permissions' => [
                'can-approve-as-treasury-manager'    => StatusRemarks::TREASURY_MANAGER_APPROVED_REQUEST->value,
                'can-approve-as-treasury-supervisor' => StatusRemarks::TREASURY_SUPERVISOR_APPROVED_REQUEST->value,
            ],
            'reject_permissions' => [
                'can-reject-as-treasury-manager'    => StatusRemarks::TREASURY_MANAGER_REJECTED_REQUEST->value,
                'can-reject-as-treasury-supervisor' => StatusRemarks::TREASURY_SUPERVISOR_REJECTED_REQUEST->value,
            ],
        ],

        'finance' => [
            'approve_permissions' => [
                'can-approve-as-finance-staff' => StatusRemarks::FINANCE_DEPARTMENT_APPROVED_REQUEST->value,
            ],
            'reject_permissions' => [
                'can-reject-as-finance-staff' => StatusRemarks::FINANCE_DEPARTMENT_REJECTED_REQUEST->value,
            ],
        ],

        'release' => [
            'release_permissions' => [
                'treasury-manager-can-release-cash-request'    => StatusRemarks::TREASURY_MANAGER_RELEASED_CASH_REQUESTED->value,
                'treasury-supervisor-can-release-cash-request' => StatusRemarks::TREASURY_SUPERVISOR_RELEASED_CASH_REQUESTED->value,
            ],
        ],
    ],

    'role_approve' => [
        'super_admin'            => StatusRemarks::SUPER_ADMIN_APPROVED_REQUEST->value,
        'department_head'        => StatusRemarks::DEPARTMENT_HEAD_APPROVED_REQUEST->value,
        'president'              => StatusRemarks::PRESIDENT_APPROVED_REQUEST->value,
        'treasury_manager'       => StatusRemarks::TREASURY_MANAGER_APPROVED_REQUEST->value,
        'treasury_supervisor'    => StatusRemarks::TREASURY_SUPERVISOR_APPROVED_REQUEST->value,
        'sales_channel_manager'  => StatusRemarks::SALES_CHANNEL_MANAGER_APPROVED_REQUEST->value,
        'national_sales_manager' => StatusRemarks::NATIONAL_SALES_MANAGER_APPROVED_REQUEST->value,
    ],

    'role_reject' => [
        'super_admin'            => StatusRemarks::SUPER_ADMIN_REJECTED_REQUEST->value,
        'department_head'        => StatusRemarks::DEPARTMENT_HEAD_REJECTED_REQUEST->value,
        'president'              => StatusRemarks::PRESIDENT_REJECTED_REQUEST->value,
        'treasury_manager'       => StatusRemarks::TREASURY_MANAGER_REJECTED_REQUEST->value,
        'treasury_supervisor'    => StatusRemarks::TREASURY_SUPERVISOR_REJECTED_REQUEST->value,
        'sales_channel_manager'  => StatusRemarks::SALES_CHANNEL_MANAGER_REJECTED_REQUEST->value,
        'national_sales_manager' => StatusRemarks::NATIONAL_SALES_MANAGER_REJECTED_REQUEST->value,
    ],

    'final_by_nature' => [
        NatureOfRequestEnum::CASH_ADVANCE->value => StatusRemarks::FOR_FINANCE_VERIFICATION->value,
        '*'                                     => StatusRemarks::FOR_PAYMENT_PROCESSING->value,
    ],
];
