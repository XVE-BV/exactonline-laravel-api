<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Mcp\Support;

use Illuminate\Support\Collection;

/**
 * Deterministically anonymizes personal/customer-identifying fields in Exact Online API output.
 *
 * Applied to MCP tool output only — never touches core API getters, push, or sync services.
 * Produces coherent fake values seeded by sha1 of the original so the same input always yields
 * the same anonymized output (auditable, not reversible).
 *
 * Kept real: order/invoice numbers, YourRef, amounts, quantities, dates, GUIDs/IDs,
 * item/article codes and descriptions, GL accounts, VAT codes, journals.
 */
class Anonymizer
{
    /** @var array<string> */
    private const EMAIL_FIELDS = [
        'email', 'emailaddress',
    ];

    /** @var array<string> */
    private const PHONE_FIELDS = [
        'phone', 'phonenumber', 'phonenumbermobile', 'phoneextension',
        'mobile', 'mobilephone',
        'fax', 'faxnumber',
    ];

    /** @var array<string> */
    private const NAME_FIELDS = [
        'name',
        'firstname', 'middlename', 'lastname',
        'fullname', 'initials',
        'contactname', 'accountname',
        'orderedbyname', 'invoicetoname', 'delivertoname',
        'employeename',
        'bankaccountholder', 'accountholder', 'holdername',
    ];

    /** @var array<string> */
    private const ADDRESS_LINE_FIELDS = [
        'addressline1', 'addressline2', 'addressline3', 'street',
    ];

    /** @var array<string> */
    private const CITY_FIELDS = ['city'];

    /** @var array<string> */
    private const POSTCODE_FIELDS = ['postcode', 'postalcode', 'zipcode'];

    /** @var array<string> */
    private const STATE_FIELDS = ['state', 'countyname'];

    /** @var array<string> */
    private const COUNTRY_FIELDS = ['country'];

    /** @var array<string> */
    private const IBAN_FIELDS = ['iban', 'bankaccountnumber'];

    /** @var array<string> */
    private const BIC_FIELDS = ['biccode', 'bic'];

    /** @var array<string> */
    private const REGISTRATION_FIELDS = [
        'vatnumber', 'chamberofcommerce', 'coccode',
    ];

    /** @var array<string> */
    private const FREETEXT_FIELDS = ['remarks', 'notes'];

    /** @var array<string> Personal dates — distinct from business dates (invoice/order dates). */
    private const BIRTH_DATE_FIELDS = ['birthdate'];

    /**
     * Anonymize all PII-bearing fields in the array, recursing into nested arrays.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function anonymize(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->anonymize($value);
            } elseif ($value instanceof Collection) {
                $result[$key] = $value->map(fn ($item) => is_array($item)
                    ? $this->anonymize($item)
                    : $item
                );
            } else {
                $result[$key] = $this->anonymizeField((string) $key, $value);
            }
        }

        return $result;
    }

    private function anonymizeField(string $key, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            return $value;
        }

        $lower = strtolower($key);

        return match (true) {
            in_array($lower, self::BIRTH_DATE_FIELDS, true) => null,
            in_array($lower, self::EMAIL_FIELDS, true) => $this->fakeEmail($value),
            in_array($lower, self::PHONE_FIELDS, true) => $this->fakePhone($value),
            in_array($lower, self::NAME_FIELDS, true) => $this->fakeName($value),
            in_array($lower, self::ADDRESS_LINE_FIELDS, true) => $this->fakeAddressLine($value),
            in_array($lower, self::CITY_FIELDS, true) => $this->fakeCity($value),
            in_array($lower, self::POSTCODE_FIELDS, true) => $this->fakePostcode($value),
            in_array($lower, self::STATE_FIELDS, true) => 'Anon-'.$this->h($value),
            in_array($lower, self::COUNTRY_FIELDS, true) => 'XX',
            in_array($lower, self::IBAN_FIELDS, true) => 'XX00ANON'.strtoupper($this->h($value)),
            in_array($lower, self::BIC_FIELDS, true) => 'ANONXXXX',
            in_array($lower, self::REGISTRATION_FIELDS, true) => 'ANON'.strtoupper($this->h($value)),
            in_array($lower, self::FREETEXT_FIELDS, true) => '[anonymized]',
            default => $value,
        };
    }

    private function h(mixed $value): string
    {
        return substr(sha1((string) $value), 0, 8);
    }

    private function fakeName(mixed $value): string
    {
        return 'Anoniem-'.$this->h($value);
    }

    private function fakeEmail(mixed $value): string
    {
        return 'anon.'.$this->h($value).'@example.com';
    }

    private function fakePhone(mixed $value): string
    {
        $h = $this->h($value);

        return '+00-'.substr($h, 0, 3).'-'.substr($h, 3, 4);
    }

    private function fakeAddressLine(mixed $value): string
    {
        $n = (int) (hexdec(substr($this->h($value), 0, 4)) % 999) + 1;

        return "Anonstraat {$n}";
    }

    private function fakeCity(mixed $value): string
    {
        $n = (int) (hexdec(substr($this->h($value), 0, 4)) % 999) + 1;

        return "Anonstad {$n}";
    }

    private function fakePostcode(mixed $value): string
    {
        $n = (int) (hexdec(substr($this->h($value), 0, 4)) % 9000) + 1000;

        return (string) $n;
    }
}
