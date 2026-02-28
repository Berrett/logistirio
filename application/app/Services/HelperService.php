<?php

namespace App\Services;

class HelperService
{
    public static function formatCurrency($amount): float
    {
        return round((float) str_replace(',', '.', preg_replace('/[^0-9.,]/', '', $amount)), 2);
    }
}
