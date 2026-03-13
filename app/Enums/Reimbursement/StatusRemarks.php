<?php

namespace App\Enums\Reimbursement;

use App\Traits\EnumsWithOptions;

enum StatusRemarks: string
{
    use EnumsWithOptions;

    case REIMBURSEMENT_SUBMITTED = "Reimbursement Submitted";
    case DEPARTMENT_HEAD_APPROVED = "Department Head Approved";
    case DEPARTMENT_HEAD_REJECTED = "Department Head Rejected";
    case TREASURY_REJECTED = "Treasury Rejected Reimbursement";
    case FOR_ACCOUNTING_VERIFICATION = "For Accounting Verification";
    case ACCOUNTING_OVERRIDE_COMPLETED = "Accounting Override Completed";
    case ACCOUNTING_MANAGER_APPROVED = "Accounting Manager Approved";
    case ACCOUNTING_STAFF_APPROVED = "Accounting Staff Approved";
    case FOR_PAYMENT_PROCESSING = "For Payment Processing";
    case FOR_RELEASING = "For Releasing";
    case REIMBURSEMENT_RELEASED = "Reimbursement Released";

}
