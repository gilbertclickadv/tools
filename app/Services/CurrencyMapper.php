<?php

namespace App\Services;

class CurrencyMapper
{
    /**
     * Map of ISO-3166 2-letter country codes to Currency Metadata.
     */
    private static array $countryToCurrency = [
        'US' => ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'],
        'GB' => ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£'],
        'EU' => ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        'FR' => ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        'DE' => ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        'IT' => ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        'ES' => ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        'NL' => ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        'BE' => ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        'GR' => ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        'IE' => ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        'AT' => ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        'PT' => ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        'FI' => ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        'SK' => ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        'SI' => ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        'CY' => ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        'EE' => ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        'LV' => ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        'LT' => ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        'MT' => ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        'JP' => ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥'],
        'CN' => ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥'],
        'IN' => ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹'],
        'CA' => ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => '$'],
        'AU' => ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => '$'],
        'CH' => ['code' => 'CHF', 'name' => 'Swiss Franc', 'symbol' => 'CHF'],
        'NZ' => ['code' => 'NZD', 'name' => 'New Zealand Dollar', 'symbol' => '$'],
        'SE' => ['code' => 'SEK', 'name' => 'Swedish Krona', 'symbol' => 'kr'],
        'NO' => ['code' => 'NOK', 'name' => 'Norwegian Krone', 'symbol' => 'kr'],
        'DK' => ['code' => 'DKK', 'name' => 'Danish Krone', 'symbol' => 'kr'],
        'RU' => ['code' => 'RUB', 'name' => 'Russian Ruble', 'symbol' => '₽'],
        'BR' => ['code' => 'BRL', 'name' => 'Brazilian Real', 'symbol' => 'R$'],
        'ZA' => ['code' => 'ZAR', 'name' => 'South African Rand', 'symbol' => 'R'],
        'KR' => ['code' => 'KRW', 'name' => 'South Korean Won', 'symbol' => '₩'],
        'SG' => ['code' => 'SGD', 'name' => 'Singapore Dollar', 'symbol' => '$'],
        'HK' => ['code' => 'HKD', 'name' => 'Hong Kong Dollar', 'symbol' => '$'],
        'MX' => ['code' => 'MXN', 'name' => 'Mexican Peso', 'symbol' => '$'],
        'TR' => ['code' => 'TRY', 'name' => 'Turkish Lira', 'symbol' => '₺'],
        'ID' => ['code' => 'IDR', 'name' => 'Indonesian Rupiah', 'symbol' => 'Rp'],
        'MY' => ['code' => 'MYR', 'name' => 'Malaysian Ringgit', 'symbol' => 'RM'],
        'PH' => ['code' => 'PHP', 'name' => 'Philippine Peso', 'symbol' => '₱'],
        'TH' => ['code' => 'THB', 'name' => 'Thai Baht', 'symbol' => '฿'],
        'VN' => ['code' => 'VND', 'name' => 'Vietnamese Dong', 'symbol' => '₫'],
        'PK' => ['code' => 'PKR', 'name' => 'Pakistani Rupee', 'symbol' => '₨'],
        'AE' => ['code' => 'AED', 'name' => 'UAE Dirham', 'symbol' => 'د.إ'],
        'SA' => ['code' => 'SAR', 'name' => 'Saudi Riyal', 'symbol' => 'ر.س'],
        'IL' => ['code' => 'ILS', 'name' => 'Israeli New Shekel', 'symbol' => '₪'],
        'PL' => ['code' => 'PLN', 'name' => 'Polish Zloty', 'symbol' => 'zł'],
        'UA' => ['code' => 'UAH', 'name' => 'Ukrainian Hryvnia', 'symbol' => '₴'],
        'RO' => ['code' => 'RON', 'name' => 'Romanian Leu', 'symbol' => 'lei'],
        'HU' => ['code' => 'HUF', 'name' => 'Hungarian Forint', 'symbol' => 'Ft'],
        'CZ' => ['code' => 'CZK', 'name' => 'Czech Koruna', 'symbol' => 'Kč'],
        'CO' => ['code' => 'COP', 'name' => 'Colombian Peso', 'symbol' => '$'],
        'CL' => ['code' => 'CLP', 'name' => 'Chilean Peso', 'symbol' => '$'],
        'PE' => ['code' => 'PEN', 'name' => 'Peruvian Sol', 'symbol' => 'S/.'],
        'AR' => ['code' => 'ARS', 'name' => 'Argentine Peso', 'symbol' => '$'],
        'EG' => ['code' => 'EGP', 'name' => 'Egyptian Pound', 'symbol' => 'E£'],
        'NG' => ['code' => 'NGN', 'name' => 'Nigerian Naira', 'symbol' => '₦'],
        'KE' => ['code' => 'KES', 'name' => 'Kenyan Shilling', 'symbol' => 'KSh'],
        'MA' => ['code' => 'MAD', 'name' => 'Moroccan Dirham', 'symbol' => 'د.م.'],
        'TW' => ['code' => 'TWD', 'name' => 'New Taiwan Dollar', 'symbol' => 'NT$'],
        'BD' => ['code' => 'BDT', 'name' => 'Bangladeshi Taka', 'symbol' => '৳'],
    ];

    /**
     * Map country code to currency details.
     */
    public static function map(string $countryCode): array
    {
        $countryCode = strtoupper(trim($countryCode));
        return self::$countryToCurrency[$countryCode] ?? ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'];
    }
}
