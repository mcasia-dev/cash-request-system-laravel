<?php
namespace App\Enums\CashRequest;

use App\Traits\EnumsWithOptions;

enum StatusRemarks: string
{
    use EnumsWithOptions;


    case REQUEST_SUBMITTED                           = "Request Submitted";
    case DEPARTMENT_HEAD_APPROVED_REQUEST            = "Department Head Approved Request";
    case DEPARTMENT_HEAD_REJECTED_REQUEST            = "Department Head Rejected Request";
    case PRESIDENT_APPROVED_REQUEST                  = "President Approved Request";
    case PRESIDENT_REJECTED_REQUEST                  = "President Rejected Request";
    case TREASURY_MANAGER_APPROVED_REQUEST           = "Treasury Manager Approved Request";
    case TREASURY_MANAGER_REJECTED_REQUEST           = "Treasury Manager Rejected Request";
    case TREASURY_SUPERVISOR_APPROVED_REQUEST        = "Treasury Supervisor Approved Request";
    case TREASURY_SUPERVISOR_REJECTED_REQUEST        = "Treasury Supervisor Rejected Request";
    case TREASURY_MANAGER_RELEASED_CASH_REQUESTED    = "Treasury Manager Released Cash Requested";
    case TREASURY_SUPERVISOR_RELEASED_CASH_REQUESTED = "Treasury Supervisor Released Cash Requested";
    case SALES_CHANNEL_MANAGER_APPROVED_REQUEST      = "Sales Channel Manager Approved Request";
    case SALES_CHANNEL_MANAGER_REJECTED_REQUEST      = "Sales Channel Manager Rejected Request";
    case NATIONAL_SALES_MANAGER_APPROVED_REQUEST     = "National Sales Manager Approved Request";
    case NATIONAL_SALES_MANAGER_REJECTED_REQUEST     = "National Sales Manager Rejected Request";
    case FOR_FINANCE_VERIFICATION                    = "For Finance Verification";
    case FOR_PAYMENT_PROCESSING                      = "For Payment Processing";
    case FOR_RELEASING                               = "For Releasing";
    case FOR_LIQUIDATION                             = "For Liquidation";
    case LIQUIDATED                                  = "Liquidated";
}
