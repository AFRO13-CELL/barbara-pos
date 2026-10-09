<?php
namespace App\Support;
use App\Models\Setting;

class Money
{
    public static function fmt($amount): string
    {
        return Setting::get('currency_symbol', '₵') . ' ' . number_format((float) $amount, 2);
    }
}
