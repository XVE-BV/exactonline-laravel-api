<?php

declare(strict_types=1);

use XVE\ExactonlineLaravelApi\Mcp\Support\Anonymizer;

it('anonymizes account/contact name fields', function () {
    $result = (new Anonymizer)->anonymize([
        'Name' => 'Acme Corp',
        'FirstName' => 'John',
        'LastName' => 'Doe',
        'OrderedByName' => 'Acme Corp',
        'InvoiceToName' => 'Acme Corp',
    ]);

    expect($result['Name'])->not->toBe('Acme Corp')->toStartWith('Anoniem-');
    expect($result['FirstName'])->not->toBe('John')->toStartWith('Anoniem-');
    expect($result['LastName'])->not->toBe('Doe')->toStartWith('Anoniem-');
    expect($result['OrderedByName'])->not->toBe('Acme Corp');
    expect($result['InvoiceToName'])->not->toBe('Acme Corp');
});

it('anonymizes email fields', function () {
    $result = (new Anonymizer)->anonymize(['Email' => 'info@acme.com']);

    expect($result['Email'])
        ->not->toBe('info@acme.com')
        ->toEndWith('@example.com')
        ->toContain('anon.');
});

it('anonymizes phone, mobile, and fax fields', function () {
    $result = (new Anonymizer)->anonymize([
        'Phone' => '+32 2 555 1234',
        'Mobile' => '+32 470 123 456',
        'Fax' => '+32 2 555 5678',
    ]);

    expect($result['Phone'])->not->toBe('+32 2 555 1234')->toStartWith('+00-');
    expect($result['Mobile'])->not->toBe('+32 470 123 456')->toStartWith('+00-');
    expect($result['Fax'])->not->toBe('+32 2 555 5678')->toStartWith('+00-');
});

it('anonymizes address, city, postcode, and country fields', function () {
    $result = (new Anonymizer)->anonymize([
        'AddressLine1' => 'Rue de la Paix 42',
        'AddressLine2' => 'Floor 3',
        'AddressLine3' => 'Suite B',
        'City' => 'Brussels',
        'Postcode' => '1000',
        'Country' => 'BE',
    ]);

    expect($result['AddressLine1'])->not->toBe('Rue de la Paix 42')->toContain('Anonstraat');
    expect($result['AddressLine2'])->not->toBe('Floor 3');
    expect($result['City'])->not->toBe('Brussels')->toContain('Anonstad');
    expect($result['Postcode'])->not->toBe('1000');
    expect($result['Country'])->toBe('XX');
});

it('anonymizes financial PII: IBAN, BIC, VAT, CoC', function () {
    $result = (new Anonymizer)->anonymize([
        'IBAN' => 'BE68539007547034',
        'BICCode' => 'GEBABEBB',
        'VATNumber' => 'BE0123456789',
        'ChamberOfCommerce' => '0123456789',
    ]);

    expect($result['IBAN'])->not->toBe('BE68539007547034')->toStartWith('XX00ANON');
    expect($result['BICCode'])->toBe('ANONXXXX');
    expect($result['VATNumber'])->not->toBe('BE0123456789')->toStartWith('ANON');
    expect($result['ChamberOfCommerce'])->not->toBe('0123456789')->toStartWith('ANON');
});

it('nullifies birth date', function () {
    $result = (new Anonymizer)->anonymize(['BirthDate' => '1985-03-15T00:00:00']);

    expect($result['BirthDate'])->toBeNull();
});

it('anonymizes free-text remarks and notes', function () {
    $result = (new Anonymizer)->anonymize([
        'Remarks' => 'Call customer at 8am',
        'Notes' => 'Preferred shipping partner: DHL',
    ]);

    expect($result['Remarks'])->toBe('[anonymized]');
    expect($result['Notes'])->toBe('[anonymized]');
});

it('keeps non-PII business fields completely unchanged', function () {
    $data = [
        'ID' => 'guid-123',
        'InvoiceNumber' => 12345,
        'OrderNumber' => 67890,
        'AmountDC' => 1234.56,
        'AmountFC' => 1234.56,
        'VATAmountDC' => 259.26,
        'Quantity' => 3,
        'UnitPrice' => 99.99,
        'YourRef' => 'REF-001',
        'ItemCode' => 'ART-001',
        'Description' => 'Product description',
        'Journal' => '70',
        'InvoiceDate' => '2026-01-15T00:00:00',
        'OrderDate' => '2026-01-10T00:00:00',
        'Created' => '2026-01-01T00:00:00',
        'Status' => 20,
        'Currency' => 'EUR',
        'Code' => 'ACC-001',
        'Division' => 123456,
        'SalesCurrency' => 'EUR',
        'GLAccount' => 'guid-456',
    ];

    expect((new Anonymizer)->anonymize($data))->toBe($data);
});

it('is deterministic — same value always produces the same output', function () {
    $data = [
        'Name' => 'Acme Corp',
        'Email' => 'info@acme.com',
        'Phone' => '+32 2 555 1234',
        'AddressLine1' => 'Rue de la Paix 42',
    ];

    $anon = new Anonymizer;

    expect($anon->anonymize($data))->toBe($anon->anonymize($data));
});

it('passes through null and empty-string values unchanged', function () {
    $result = (new Anonymizer)->anonymize([
        'Name' => null,
        'Email' => '',
        'Phone' => null,
    ]);

    expect($result['Name'])->toBeNull();
    expect($result['Email'])->toBe('');
    expect($result['Phone'])->toBeNull();
});

it('recursively anonymizes PII in nested relation arrays', function () {
    $result = (new Anonymizer)->anonymize([
        'SalesInvoiceLines' => [
            ['ItemCode' => 'ART-001', 'Description' => 'Widget', 'AmountDC' => 100.0, 'Quantity' => 2],
        ],
        'Account' => ['Name' => 'Acme Corp', 'Email' => 'info@acme.com', 'Code' => 'ACC-001'],
    ]);

    // Non-PII in nested arrays kept
    expect($result['SalesInvoiceLines'][0]['ItemCode'])->toBe('ART-001');
    expect($result['SalesInvoiceLines'][0]['Description'])->toBe('Widget');
    expect($result['SalesInvoiceLines'][0]['AmountDC'])->toBe(100.0);
    expect($result['SalesInvoiceLines'][0]['Quantity'])->toBe(2);

    // PII in nested arrays anonymized
    expect($result['Account']['Name'])->not->toBe('Acme Corp')->toStartWith('Anoniem-');
    expect($result['Account']['Email'])->not->toBe('info@acme.com')->toEndWith('@example.com');
    expect($result['Account']['Code'])->toBe('ACC-001');
});

it('anonymizes SalesOrder customer name but keeps all order business data', function () {
    $result = (new Anonymizer)->anonymize([
        'OrderNumber' => 12345,
        'OrderedByName' => 'Acme Corp',
        'AmountDC' => 500.00,
        'AmountFC' => 500.00,
        'YourRef' => 'PO-2026-001',
        'OrderDate' => '2026-01-15T00:00:00',
        'Status' => 20,
    ]);

    expect($result['OrderNumber'])->toBe(12345);
    expect($result['OrderedByName'])->not->toBe('Acme Corp')->toStartWith('Anoniem-');
    expect($result['AmountDC'])->toBe(500.00);
    expect($result['AmountFC'])->toBe(500.00);
    expect($result['YourRef'])->toBe('PO-2026-001');
    expect($result['OrderDate'])->toBe('2026-01-15T00:00:00');
    expect($result['Status'])->toBe(20);
});
