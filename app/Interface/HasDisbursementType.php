<?php
namespace App\Interface;

interface HasDisbursementType
{
    public function isCheckDisbursement();

    public function isPayrollDisbursement();
}
