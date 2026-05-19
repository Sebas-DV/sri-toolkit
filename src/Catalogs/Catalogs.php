<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Catalogs;

use MTZ\Toolkit\Catalogs\Data\CatalogMetadata;

final class Catalogs
{
    private const SOURCE = 'Official offline technical sheet v2.26';
    private const UPDATED_AT = '2024-03-05';

    private static ?CatalogRegistry $registry = null;

    public static function registry(): CatalogRegistry
    {
        if (self::$registry === null)
        {
            self::$registry = self::freshRegistry();
        }

        return self::$registry;
    }

    public static function freshRegistry(): CatalogRegistry
    {
        $registry = new CatalogRegistry();

        $registry->registerDefault('identification-types', self::IDENTIFICATION_TYPES, new CatalogMetadata(
            source: self::SOURCE . ', Table 6',
            updatedAt: self::UPDATED_AT,
        ));

        $registry->registerDefault('document-types', self::DOCUMENT_TYPES, new CatalogMetadata(
            source: self::SOURCE . ', Table 3',
            updatedAt: self::UPDATED_AT,
        ));

        $registry->registerDefault('payment-methods', self::PAYMENT_METHODS, new CatalogMetadata(
            source: self::SOURCE . ', Table 24',
            updatedAt: self::UPDATED_AT,
        ));

        $registry->registerDefault('vat-rates', self::VAT_RATES, new CatalogMetadata(
            source: self::SOURCE . ', Table 17',
            updatedAt: self::UPDATED_AT,
            notes: 'VAT 15% and VAT 5% were added by technical sheet v2.26.',
        ));

        $registry->registerDefault('vat-withholding', self::VAT_WITHHOLDING, new CatalogMetadata(
            source: self::SOURCE . ', Table 20',
            updatedAt: self::UPDATED_AT,
        ));

        $registry->registerDefault('tax-codes', self::TAX_CODES, new CatalogMetadata(
            source: self::SOURCE . ', Tables 16 and 19',
            updatedAt: self::UPDATED_AT,
        ));

        $registry->registerDefault('support-codes', self::SUPPORT_CODES, new CatalogMetadata(
            source: 'ATS catalog referenced by the official offline technical sheet v2.26',
            updatedAt: self::UPDATED_AT,
            notes: 'Commonly used subset; extend it with override().',
        ));

        $registry->registerDefault('ice-rates', self::ICE_RATES, new CatalogMetadata(
            source: self::SOURCE . ', Table 18 and ICE annex technical sheet',
            updatedAt: self::UPDATED_AT,
            notes: 'ICE percentage codes; rates must be calculated with current regulation.',
        ));

        return $registry;
    }

    /**
     * @var array<array-key, array{code: string, description: string}>
     */
    public const IDENTIFICATION_TYPES = [
        '04' => ['code' => '04', 'description' => 'Taxpayer ID'],
        '05' => ['code' => '05', 'description' => 'National ID'],
        '06' => ['code' => '06', 'description' => 'Passport'],
        '07' => ['code' => '07', 'description' => 'Final consumer'],
        '08' => ['code' => '08', 'description' => 'Foreign ID'],
    ];

    /**
     * @var array<array-key, array{code: string, description: string}>
     */
    public const DOCUMENT_TYPES = [
        '01' => ['code' => '01', 'description' => 'Invoice'],
        '03' => ['code' => '03', 'description' => 'Purchase settlement'],
        '04' => ['code' => '04', 'description' => 'Credit note'],
        '05' => ['code' => '05', 'description' => 'Debit note'],
        '06' => ['code' => '06', 'description' => 'Delivery guide'],
        '07' => ['code' => '07', 'description' => 'Withholding receipt'],
    ];

    /**
     * @var array<array-key, array{code: string, description: string}>
     */
    public const PAYMENT_METHODS = [
        '01' => ['code' => '01', 'description' => 'No financial system used'],
        '15' => ['code' => '15', 'description' => 'Debt compensation'],
        '16' => ['code' => '16', 'description' => 'Debit card'],
        '17' => ['code' => '17', 'description' => 'Electronic money'],
        '18' => ['code' => '18', 'description' => 'Prepaid card'],
        '19' => ['code' => '19', 'description' => 'Credit card'],
        '20' => ['code' => '20', 'description' => 'Other financial system method'],
        '21' => ['code' => '21', 'description' => 'Title endorsement'],
    ];

    /**
     * @var array<array-key, array{code: string, description: string, rate: int|float}>
     */
    public const VAT_RATES = [
        '0' => ['code' => '0', 'description' => 'VAT 0%', 'rate' => 0],
        '2' => ['code' => '2', 'description' => 'VAT 12%', 'rate' => 12],
        '3' => ['code' => '3', 'description' => 'VAT 14%', 'rate' => 14],
        '4' => ['code' => '4', 'description' => 'VAT 15%', 'rate' => 15],
        '5' => ['code' => '5', 'description' => 'VAT 5%', 'rate' => 5],
        '6' => ['code' => '6', 'description' => 'Not subject to tax', 'rate' => 0],
        '7' => ['code' => '7', 'description' => 'VAT exempt', 'rate' => 0],
        '8' => ['code' => '8', 'description' => 'Differentiated VAT', 'rate' => 0],
        '10' => ['code' => '10', 'description' => 'VAT 13%', 'rate' => 13],
    ];

    /**
     * @var array<array-key, array{code: string, description: string, rate: int|float}>
     */
    public const VAT_WITHHOLDING = [
        '9' => ['code' => '9', 'description' => 'VAT withholding 10%', 'rate' => 10],
        '10' => ['code' => '10', 'description' => 'VAT withholding 20%', 'rate' => 20],
        '1' => ['code' => '1', 'description' => 'VAT withholding 30%', 'rate' => 30],
        '11' => ['code' => '11', 'description' => 'VAT withholding 50%', 'rate' => 50],
        '2' => ['code' => '2', 'description' => 'VAT withholding 70%', 'rate' => 70],
        '3' => ['code' => '3', 'description' => 'VAT withholding 100%', 'rate' => 100],
        '7' => ['code' => '7', 'description' => 'Zero withholding', 'rate' => 0],
        '8' => ['code' => '8', 'description' => 'Withholding does not apply', 'rate' => 0],
    ];

    /**
     * @var array<array-key, array{code: string, description: string}>
     */
    public const TAX_CODES = [
        '1' => ['code' => '1', 'description' => 'Income tax'],
        '2' => ['code' => '2', 'description' => 'VAT'],
        '3' => ['code' => '3', 'description' => 'ICE'],
        '5' => ['code' => '5', 'description' => 'IRBPNR'],
        '6' => ['code' => '6', 'description' => 'ISD'],
    ];

    /**
     * @var array<array-key, array{code: string, description: string}>
     */
    public const SUPPORT_CODES = [
        '01' => ['code' => '01', 'description' => 'VAT tax credit'],
        '02' => ['code' => '02', 'description' => 'Income tax cost or expense'],
        '03' => ['code' => '03', 'description' => 'Fixed asset with VAT tax credit'],
        '04' => ['code' => '04', 'description' => 'Fixed asset cost or expense'],
        '05' => ['code' => '05', 'description' => 'Travel, lodging, and food expense settlement'],
        '06' => ['code' => '06', 'description' => 'Inventory with VAT tax credit'],
        '07' => ['code' => '07', 'description' => 'Inventory cost or expense'],
        '08' => ['code' => '08', 'description' => 'Reimbursable expense payment'],
        '09' => ['code' => '09', 'description' => 'Insurance claim reimbursement'],
        '10' => ['code' => '10', 'description' => 'Dividend, benefit, or profit distribution'],
        '00' => ['code' => '00', 'description' => 'Special cases without a matching support code'],
    ];

    /**
     * @var array<array-key, array{code: string, description: string}>
     */
    public const ICE_RATES = [
        '3011' => ['code' => '3011', 'description' => 'ICE blond cigarettes'],
        '3021' => ['code' => '3021', 'description' => 'ICE dark cigarettes'],
        '3023' => ['code' => '3023', 'description' => 'ICE tobacco products and substitutes except cigarettes'],
        '3031' => ['code' => '3031', 'description' => 'ICE alcoholic beverages'],
        '3041' => ['code' => '3041', 'description' => 'ICE industrial beer'],
        '3043' => ['code' => '3043', 'description' => 'ICE craft beer'],
        '3053' => ['code' => '3053', 'description' => 'ICE soft drinks with high sugar content'],
        '3054' => ['code' => '3054', 'description' => 'ICE soft drinks with low sugar content'],
        '3073' => ['code' => '3073', 'description' => 'ICE motor vehicles up to 20000 USD retail price'],
        '3075' => ['code' => '3075', 'description' => 'ICE motor vehicles from 30000 to 40000 USD retail price'],
        '3077' => ['code' => '3077', 'description' => 'ICE motor vehicles over 40000 and up to 50000 USD retail price'],
        '3078' => ['code' => '3078', 'description' => 'ICE motor vehicles over 50000 and up to 60000 USD retail price'],
        '3079' => ['code' => '3079', 'description' => 'ICE motor vehicles over 60000 and up to 70000 USD retail price'],
        '3080' => ['code' => '3080', 'description' => 'ICE motor vehicles over 70000 USD retail price'],
        '3081' => ['code' => '3081', 'description' => 'ICE aircraft, trikes, yachts, and recreational boats'],
        '3092' => ['code' => '3092', 'description' => 'ICE prepaid television services'],
        '3093' => ['code' => '3093', 'description' => 'ICE corporate phone services'],
        '3101' => ['code' => '3101', 'description' => 'ICE energy drinks'],
        '3111' => ['code' => '3111', 'description' => 'ICE non-alcoholic beverages'],
        '3610' => ['code' => '3610', 'description' => 'ICE perfumes and eau de toilette'],
        '3620' => ['code' => '3620', 'description' => 'ICE video games'],
        '3630' => ['code' => '3630', 'description' => 'ICE firearms, sporting weapons, and ammunition'],
        '3640' => ['code' => '3640', 'description' => 'ICE incandescent bulbs'],
        '3660' => ['code' => '3660', 'description' => 'ICE dues, memberships, affiliations, and shares'],
        '3671' => ['code' => '3671', 'description' => 'ICE gas water heaters and heating systems'],
        '3680' => ['code' => '3680', 'description' => 'ICE plastic bags'],
        '3681' => ['code' => '3681', 'description' => 'ICE mobile phone services for individuals'],
        '3682' => ['code' => '3682', 'description' => 'ICE heated tobacco consumables and nicotine liquids'],
    ];
}
