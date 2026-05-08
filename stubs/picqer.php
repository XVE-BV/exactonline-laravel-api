<?php

// PHPStan stubs for picqer/exact-php-client
// Makes all model properties nullable to reflect the Exact Online API behavior.
// Dynamic __call methods are declared via @method tags so PHPStan recognises them.

namespace Picqer\Financials\Exact;

/**
 * @method static[] get(array<string, mixed> $params = [])
 * @method static skip(int $skip)
 * @method static top(int $top)
 * @method static filter(string $filter, string $expand = '', string $select = '')
 * @method static select(string|array<string> $select)
 * @method static orderBy(string $field, string $direction = 'asc')
 * @method static expand(string $expand)
 * @method bool save()
 * @method bool delete()
 */
class Model
{
    /** @return array<string, mixed> */
    public function attributes(): array {}

    public function __set(string $key, mixed $value): void {}

    /** @return static|null */
    public function find(mixed $id): mixed {}
}

/**
 * @property string|null $ID Primary key
 * @property string|null $Accountant Reference to the accountant of the customer. Conditions: The referred accountant must have value > 0 in the field IsAccountant
 * @property string|null $AccountManager ID of the account manager
 * @property string|null $AccountManagerFullName Name of the account manager
 * @property int|null $AccountManagerHID Number of the account manager
 * @property string|null $ActivitySector Reference to Activity sector of the account
 * @property string|null $ActivitySubSector Reference to Activity sub-sector of the account
 * @property string|null $AddressLine1 Visit address first line
 * @property string|null $AddressLine2 Visit address second line
 * @property string|null $AddressLine3 Visit address third line
 * @property int|null $AutomaticProcessProposedEntry Automatically create entries for complete entry proposals
 * @property BankAccount[] $BankAccounts Collection of Bank accounts
 * @property bool|null $Blocked Indicates if the account is blocked
 * @property string|null $BSN Citizen Service Number for the Netherlands
 * @property string|null $BusinessType Reference to the business type of the account
 * @property bool|null $CanDropShip Indicates the default for the possibility to drop ship when an item is linked to a supplier
 * @property string|null $ChamberOfCommerce Chamber of commerce number
 * @property string|null $City Visit address City
 * @property string|null $Classification1 Account classification 1
 * @property string|null $Classification2 Account classification 2
 * @property string|null $Classification3 Account classification 3
 * @property string|null $Classification4 Account classification 4
 * @property string|null $Classification5 Account classification 5
 * @property string|null $Classification6 Account classification 6
 * @property string|null $Classification7 Account classification 7
 * @property string|null $Classification8 Account classification 8
 * @property string|null $Code Unique key, fixed length numeric string with leading spaces, length 18. IMPORTANT: When you use OData $filter on this field you have to make sure the filter parameter contains the leading spaces
 * @property string|null $CodeAtSupplier Code under which your own company is known at the account
 * @property string|null $CompanySize Reference to Company size of the account
 * @property int|null $ConsolidationScenario Consolidation scenario (Time & Billing). Values: 0 = No consolidation, 1 = Item, 2 = Item + Project, 3 = Item + Employee, 4 = Item + Employee + Project, 5 = Project + WBS + Item, 6 = Project + WBS + Item + Employee. Item means in this case including Unit and Price, these also have to be the same to consolidate
 * @property string|null $ControlledDate Date of the latest control of account data with external web service
 * @property string|null $Country Country code
 * @property string|null $CountryName Country name
 * @property string|null $Created Creation date
 * @property string|null $Creator User ID of creator
 * @property string|null $CreatorFullName Name of creator
 * @property float|null $CreditLinePurchase Maximum amount of credit for Purchase. If no value has been defined, there is no credit limit
 * @property float|null $CreditLineSales Maximum amount of credit for sales. If no value has been defined, there is no credit limit
 * @property string|null $CustomField Custom field endpoint. Provided only for the Exact Online Premium users.
 * @property string|null $DatevCreditorCode DATEV creditor code for Germany legislation
 * @property string|null $DatevDebtorCode DATEV debtor code for Germany legislation
 * @property int|null $DeliveryAdvice Indicates how deliveries are handled. Values: 0 = Partial, orders can be delivered partial, 1 = Complete the order needs to be complete to deliver, 2 = Partial without backorder when deliver partially the remainder of the order is completed without delivery
 * @property float|null $DiscountPurchase Default discount percentage for purchase. This is stored as a fraction. ie 5.5% is stored as .055
 * @property float|null $DiscountSales Default discount percentage for sales. This is stored as a fraction. ie 5.5% is stored as .055
 * @property int|null $Division Division code
 * @property string|null $Email E-Mail address of the account
 * @property bool|null $EnableSalesPaymentLink Indicates whether payment link is activated for sales
 * @property string|null $EndDate Determines in combination with the start date if the account is active. If the current date is > end date the account is inactive
 * @property string|null $EORINumber EORI number
 * @property string|null $EstablishedDate RegistrationDate
 * @property string|null $Fax Fax number
 * @property string|null $GLAccountPurchase Default (corporate) GL offset account for purchase (cost)
 * @property string|null $GLAccountSales Default (corporate) GL offset account for sales (revenue)
 * @property string|null $GLAP Default GL account for Accounts Payable
 * @property string|null $GLAR Default GL account for Accounts Receivable
 * @property string|null $GlnNumber Global Location Number can be used by companies to identify their locations, giving them complete flexibility to identify any type or level of location required
 * @property bool|null $HasWithholdingTaxSales Indicates whether a customer has withholding tax on sales
 * @property bool|null $IgnoreDatevWarningMessage Suppressed warning message when there is duplication on the DATEV code
 * @property string|null $IncotermAddressPurchase Address of Incoterm for Purchase
 * @property string|null $IncotermAddressSales Address of Incoterm for Sales
 * @property string|null $IncotermCodePurchase Code of Incoterm for Purchase
 * @property string|null $IncotermCodeSales Code of Incoterm for Sales
 * @property int|null $IncotermVersionPurchase Version of Incoterm for Purchase Supported version for Incoterms : 2010, 2020
 * @property int|null $IncotermVersionSales Version of Incoterm for Sales Supported version for Incoterms : 2010, 2020
 * @property string|null $IntraStatArea Intrastat Area
 * @property string|null $IntraStatDeliveryTerm Intrastat delivery method
 * @property string|null $IntraStatSystem System for Intrastat
 * @property string|null $IntraStatTransactionA Transaction type A for Intrastat
 * @property string|null $IntraStatTransactionB Transaction type B for Intrastat
 * @property string|null $IntraStatTransportMethod Transport method for Intrastat
 * @property string|null $InvoiceAccount ID of account to be invoiced instead of this account
 * @property string|null $InvoiceAccountCode Code of InvoiceAccount
 * @property string|null $InvoiceAccountName Name of InvoiceAccount
 * @property int|null $InvoiceAttachmentType Indicates which attachment types should be sent when a sales invoice is printed. Only values in related table with Invoice=1 are allowed
 * @property int|null $InvoicingMethod Method of sending for sales invoices. Values: 1: Paper, 2: EMail, 4: Mailbox (electronic exchange), 8: Send and track, 32: Send via PeppolTake notes: To use the '4 - Mailbox (electronic exchange)' option, the 'Mailbox' feature set is required in the licence. To use the '32 - Send via Peppol' option, e-invoicing via Peppol must be activated
 * @property int|null $IsAccountant Indicates whether the account is an accountant. Values: 0 = No accountant, 1 = True, but accountant doesn't want his name to be published in the list of accountants, 2 = True, and accountant is published in the list of accountants
 * @property int|null $IsAgency Indicates whether the accounti is an agency
 * @property int|null $IsAnonymised Indicates whtether the account is anonymised.
 * @property int|null $IsCompetitor Indicates whether the account is a competitor
 * @property bool|null $IsExtraDuty Indicates whether a customer is eligible for extra duty
 * @property int|null $IsMailing Indicates if the account is excluded from mailing marketing information
 * @property bool|null $IsPilot Indicates whether the account is a pilot account
 * @property bool|null $IsReseller Indicates whether the account is a reseller
 * @property bool|null $IsSales Indicates whether the account is allowed for sales
 * @property bool|null $IsSupplier Indicates whether the account is a supplier
 * @property string|null $Language Language code
 * @property string|null $LanguageDescription Language description
 * @property float|null $Latitude Latitude (used by Google maps)
 * @property string|null $LeadPurpose Reference to Lead purpose of an account
 * @property string|null $LeadSource Reference to Lead source of an account
 * @property string|null $Logo Bytes of the logo image
 * @property string|null $LogoFileName The file name (without path, but with extension) of the image
 * @property string|null $LogoThumbnailUrl Thumbnail url of the logo
 * @property string|null $LogoUrl Url to retrieve the logo
 * @property float|null $Longitude Longitude (used by Google maps)
 * @property string|null $MainContact Reference to main contact person
 * @property string|null $Modified Last modified date
 * @property string|null $Modifier User ID of modifier
 * @property string|null $ModifierFullName Name of modifier
 * @property string|null $Name Account name
 * @property string|null $OINNumber Dutch government identification number
 * @property string|null $Parent ID of the parent account
 * @property string|null $PayAsYouEarn Indicates the loan repayment plan for UK legislation
 * @property string|null $PaymentConditionPurchase Code of default payment condition for purchase
 * @property string|null $PaymentConditionPurchaseDescription Description of PaymentConditionPurchase
 * @property string|null $PaymentConditionSales Code of default payment condition for sales
 * @property string|null $PaymentConditionSalesDescription Description of PaymentConditionSales
 * @property string|null $PeppolIdentifier Peppol identifier user entered manually, corresponds to picked peppol adress
 * @property int|null $PeppolIdentifierType Peppol identifier type that user picked manually - GLN, COC, etc
 * @property string|null $Phone Phone number
 * @property string|null $PhoneExtension Phone number extention
 * @property string|null $Postcode Visit address postcode
 * @property string|null $PriceList Default sales price list for account
 * @property string|null $PurchaseCurrency Currency of purchaseTake notes: If the currency code input is not in the active currencies, the value will be set to empty.
 * @property string|null $PurchaseCurrencyDescription Description of PurchaseCurrency
 * @property int|null $PurchaseLeadDays Indicates number of days required to receive a purchase. Acts as a default
 * @property string|null $PurchaseVATCode Default VAT code used for purchase entries
 * @property string|null $PurchaseVATCodeDescription Description of PurchaseVATCode
 * @property bool|null $RecepientOfCommissions Define the relation that should be taken in the official document of the rewarding fiscal fiches Belcotax
 * @property string|null $Remarks Remarks
 * @property string|null $Reseller ID of the reseller account. Conditions: the target account must have the property IsReseller turned on
 * @property string|null $ResellerCode Code of Reseller
 * @property string|null $ResellerName Name of Reseller
 * @property string|null $RSIN Fiscal number for NL legislation
 * @property string|null $SalesCurrency Currency of Sales used for Time & BillingTake notes: If the currency code input is not in the active currencies, the value will be set to empty.
 * @property string|null $SalesCurrencyDescription Description of SalesCurrency
 * @property string|null $SalesVATCode Default VAT code for a sales entry
 * @property string|null $SalesVATCodeDescription Description of SalesVATCode
 * @property string|null $SearchCode Search code
 * @property int|null $SecurityLevel Security level (0 - 100)
 * @property int|null $SeparateInvPerSubscription Indicates how invoices are generated from subscriptions. 0 = subscriptions belonging to the same customer are combined in a single invoice. 1 = each subscription results in one invoice. In both cases, each individual subscription line results in one invoice line
 * @property int|null $ShippingLeadDays Indicates the number of days it takes to send goods to the customer. Acts as a default
 * @property string|null $ShippingMethod Default shipping method
 * @property bool|null $ShowRemarkForSales Indicates whether to display Ordered by account's remarks when creating a new sales order
 * @property string|null $StartDate Indicates in combination with the end date if the account is active
 * @property string|null $State State/Province/County code When changing the Country and the State is filled, the State must be assigned with a valid value from the selected country or set to empty
 * @property string|null $StateName Name of State
 * @property string|null $Status If the status field is filled this means the account is a customer. The value indicates the customer status. Possible values: A=None, S=Suspect, P=Prospect, C=Customer
 * @property string|null $TradeName Trade name can be registered and shown with the client (for all legislations)
 * @property string|null $Type Account type: Values: A = Relation, D = Division
 * @property string|null $UniqueTaxpayerReference Unique taxpayer reference for UK legislation
 * @property string|null $VATLiability Indicates the VAT status of an account to be able to identify the relation that should be selected in the VAT debtor listing in Belgium
 * @property string|null $VATNumber The number under which the account is known at the Value Added Tax collection agency
 * @property string|null $Website Website of the account
 * @property bool|null $IsCustomer Indicates whether the account is a customer
 */
class Account extends Model {}

/**
 * @property string|null $ID Primary key
 * @property string|null $Account Account linked to the address
 * @property bool|null $AccountIsSupplier Indicates if the account is a supplier
 * @property string|null $AccountName Name of the account
 * @property string|null $AddressLine1 First address line
 * @property string|null $AddressLine2 Second address line
 * @property string|null $AddressLine3 Third address line
 * @property string|null $City City
 * @property string|null $Contact Contact linked to Address
 * @property string|null $ContactName Contact name
 * @property string|null $Country Country code
 * @property string|null $CountryName Country name
 * @property string|null $Created Creation date
 * @property string|null $Creator User ID of creator
 * @property string|null $CreatorFullName Name of creator
 * @property string|null $CustomField Custom field endpoint. Provided only for the Exact Online Premium users.
 * @property int|null $Division Division code
 * @property string|null $Fax Fax number
 * @property bool|null $FreeBoolField_01 Free boolean field 1
 * @property bool|null $FreeBoolField_02 Free boolean field 2
 * @property bool|null $FreeBoolField_03 Free boolean field 3
 * @property bool|null $FreeBoolField_04 Free boolean field 4
 * @property bool|null $FreeBoolField_05 Free boolean field 5
 * @property string|null $FreeDateField_01 Free date field 1
 * @property string|null $FreeDateField_02 Free date field 2
 * @property string|null $FreeDateField_03 Free date field 3
 * @property string|null $FreeDateField_04 Free date field 4
 * @property string|null $FreeDateField_05 Free date field 5
 * @property float|null $FreeNumberField_01 Free number field 1
 * @property float|null $FreeNumberField_02 Free number field 2
 * @property float|null $FreeNumberField_03 Free number field 3
 * @property float|null $FreeNumberField_04 Free number field 4
 * @property float|null $FreeNumberField_05 Free number field 5
 * @property string|null $FreeTextField_01 Free text field 1
 * @property string|null $FreeTextField_02 Free text field 2
 * @property string|null $FreeTextField_03 Free text field 3
 * @property string|null $FreeTextField_04 Free text field 4
 * @property string|null $FreeTextField_05 Free text field 5
 * @property string|null $Mailbox MailboxTake notes: The 'Mailbox' functionality required the Mailbox feature set in the licence.
 * @property bool|null $Main Indicates if the address is the main address for this type
 * @property string|null $Modified Last modified date
 * @property string|null $Modifier User ID of modifier
 * @property string|null $ModifierFullName Name of modifier
 * @property string|null $NicNumber Last 5 digits of SIRET number which is an intern sequential number of 4 digits representing the identification of the localization of the office
 * @property string|null $Notes Notes for an address
 * @property string|null $Phone Phone number
 * @property string|null $PhoneExtension Phone extension
 * @property string|null $Postcode Postcode
 * @property string|null $State State
 * @property string|null $StateDescription Name of the State
 * @property int|null $Type The type of address. Visit=1, Postal=2, Invoice=3, Delivery=4
 * @property string|null $Warehouse The warehouse linked to the address, if a warehouse is linked the account will be empty. Can only be filled for type=Delivery
 * @property string|null $WarehouseCode Code of the warehoude
 * @property string|null $WarehouseDescription Description of the warehouse
 */
class Address extends Model {}

/**
 * @property string|null $ID Primary key
 * @property string|null $Account Account (customer, supplier) to which the bank account belongs
 * @property string|null $AccountName The name of the account
 * @property string|null $BankAccount The bank account number
 * @property string|null $BankAccountHolderName Name of the holder of the bank account, as known by the bank
 * @property string|null $BICCode BIC code of the bank where the bank account is held
 * @property bool|null $Blocked Indicates if the bank account is blocked
 * @property string|null $Created Creation date
 * @property string|null $Creator User ID of creator
 * @property string|null $CreatorFullName Name of creator
 * @property string|null $Description Description of the bank account
 * @property int|null $Division Division code
 * @property string|null $Format Format that belongs to the bank account number
 * @property bool|null $Main Indicates if the bank account is the main bank account
 * @property string|null $Modified Last modified date
 * @property string|null $Modifier User ID of modifier
 * @property string|null $ModifierFullName Name of modifier
 * @property string|null $PaymentServiceAccount ID of the Payment service account. Used when Type is 'P' (Payment service)
 * @property string|null $Type The type indicates what entity the bank account is used for. A = Account (default), E = Employee, K = Cash, P = Payment service, R = Bank, S = Student, U = Unknown. Currently it's only possible to create 'Account' type bank accounts.
 * @property string|null $TypeDescription Description of the Type
 * @property string|null $IBAN IBAN of the bank account
 * @property string|null $BankName Name of the bank
 * @property string|null $BankDescription Description of the bank
 */
class BankAccount extends Model {}

/**
 * @property string|null $ID Primary key
 * @property string|null $Account The account to which the contact belongs
 * @property bool|null $AccountIsCustomer Indicates if account is a customer
 * @property bool|null $AccountIsSupplier Indicates if account is a supplier
 * @property string|null $AccountMainContact Reference to the main contact of the account
 * @property string|null $AccountName Name of the account
 * @property string|null $AddressLine2 Second address line
 * @property string|null $AddressStreet Street name of the address
 * @property string|null $AddressStreetNumber Street number of the address
 * @property string|null $AddressStreetNumberSuffix Street number suffix of the address
 * @property string|null $BirthDate Birth date
 * @property string|null $BirthPlace Birth place
 * @property string|null $BusinessEmail Email address of the contact
 * @property string|null $BusinessFax Fax of the contact
 * @property string|null $BusinessMobile Mobile of the contact
 * @property string|null $BusinessPhone Phone of the contact
 * @property string|null $BusinessPhoneExtension Phone extension of the contact
 * @property string|null $City City
 * @property string|null $Code Code of the account
 * @property string|null $Country Country code
 * @property string|null $Created Creation date
 * @property string|null $Creator User ID of the creator
 * @property string|null $CreatorFullName Name of the creator
 * @property string|null $CustomField Custom field endpoint. Provided only for the Exact Online Premium users.
 * @property int|null $Division Division code
 * @property string|null $Email Email address of the contact
 * @property string|null $EndDate End date
 * @property string|null $FirstName First name. Provide at least first name or last name to create a new contact
 * @property string|null $FullName Full name (First name Middle name Last name)
 * @property string|null $Gender Gender
 * @property int|null $HID Contact ID
 * @property string|null $IdentificationDate Identification date
 * @property string|null $IdentificationDocument Reference to the identification document of the contact
 * @property string|null $IdentificationUser Reference to the user responsible for identification
 * @property string|null $Initials Initials
 * @property int|null $IsAnonymised Indicates whether the contact is anonymised.
 * @property bool|null $IsMailingExcluded Indicates whether contacts are excluded from the marketing list
 * @property bool|null $IsMainContact Indicates if this is the main contact of the linked account
 * @property string|null $JobTitleDescription Jobtitle of the contact
 * @property string|null $Language Language code
 * @property string|null $LastName Last name. Provide at least first name or last name to create a new contact
 * @property string|null $LeadPurpose Reference to purpose of an contact
 * @property string|null $LeadSource Reference to source of an contact
 * @property string|null $MarketingNotes The user should be able to do a full text search on these notes to gather contacts for a marketing campaign
 * @property string|null $MiddleName Middle name
 * @property string|null $Mobile Business phone of the contact
 * @property string|null $Modified Last modified date
 * @property string|null $Modifier User ID of modifier
 * @property string|null $ModifierFullName Name of the last modifier
 * @property string|null $Nationality Nationality
 * @property string|null $Notes Extra remarks
 * @property string|null $PartnerName Last name of partner
 * @property string|null $PartnerNamePrefix Middlename of partner
 * @property string|null $Person Reference to the personal information of this contact such as name, gender, address etc.
 * @property string|null $Phone Phone of the contact
 * @property string|null $PhoneExtension Phone extension of the contact
 * @property string|null $Picture This field is write-only. The picture can be downloaded through PictureUrl and PictureThumbnailUrl.
 * @property string|null $PictureName Filename of the picture
 * @property string|null $PictureThumbnailUrl Url to retrieve the picture thumbnail
 * @property string|null $PictureUrl Url to retrieve the picture
 * @property string|null $Postcode Postcode
 * @property string|null $SocialSecurityNumber Social security number
 * @property string|null $StartDate Start date
 * @property string|null $State State
 * @property string|null $Title Title
 * @property string|null $TitleAbbreviation TitleAbbreviation
 * @property string|null $TitleDescription TitleDescription
 * @property string|null $AddressLine1 First address line
 * @property string|null $Fax Fax number
 * @property string|null $Salutation Salutation
 */
class Contact extends Model {}

/**
 * @property string|null $ID Primary key
 * @property string|null $Account ID of the related account of this document
 * @property string|null $AccountCode Code of Account
 * @property string|null $AccountName Name of Account
 * @property bool|null $ActionRegenerateEntryProposal ActionRegenerateEntryProposal
 * @property float|null $AmountFC Amount in the currency of the transaction
 * @property string|null $Body Body of this document
 * @property string|null $Category ID of the category of this document
 * @property string|null $CategoryDescription Description of Category
 * @property string|null $Contact ID of the related contact of this document
 * @property string|null $ContactFullName Contact full name
 * @property string|null $ContractID The contract linked to the document
 * @property string|null $ContractNumber Contract Number
 * @property string|null $Created Creation date
 * @property string|null $Creator User ID of creator
 * @property string|null $CreatorFullName Name of creator
 * @property string|null $Currency Currency code
 * @property int|null $Division Division code
 * @property string|null $DocumentDate Entry date of the incoming document
 * @property string|null $DocumentFolder The Id of document folder
 * @property string|null $DocumentFolderCode The Code of document folder
 * @property string|null $DocumentFolderDescription The Decsription of document folder
 * @property string|null $DocumentViewUrl Url to view the document
 * @property string|null $EntryStatusDescription EntryStatusDescription
 * @property string|null $ExpiryDate Expiry date of this document
 * @property string|null $FinancialTransactionEntryID Reference to the transaction lines of the financial entry. For a document of type sales invoice it will return the InvoiceID of the sales invoice (SalesInvoices API).
 * @property bool|null $HasEmptyBody Indicates that the document body is empty
 * @property int|null $HID Human-readable ID, formatted as xx.xxx.xxx. Unique. May not be equal to zero
 * @property bool|null $InheritShare InheritShare value
 * @property string|null $Item The item linked to the document
 * @property string|null $ItemCode Code of Item
 * @property string|null $ItemDescription Description of Item
 * @property string|null $Language The language code of the document
 * @property string|null $Modified Last modified date
 * @property string|null $Modifier User ID of modifier
 * @property string|null $ModifierFullName Name of modifier
 * @property string|null $Opportunity The opportunity linked to the document
 * @property string|null $Project The project linked to the document
 * @property string|null $ProjectCode Code of project
 * @property string|null $ProjectDescription Description of project
 * @property int|null $ProposedEntryStatus ProposedEntryStatus, 0 = Void, 5 = Rejected, 20 = Open, 50 = Processed
 * @property int|null $SalesInvoiceNumber 'Our reference' of the transaction that belongs to this document
 * @property int|null $SalesOrderNumber Number of the sales order
 * @property int|null $SendMethod Send method
 * @property int|null $ShopOrderNumber Number of the shop order
 * @property string|null $Subject Subject of this document
 * @property string|null $TeamsMeetingId Teams meeting id
 * @property int|null $Type ID of the type of this document
 * @property string|null $TypeDescription Description of Type
 */
class Document extends Model {}

/**
 * @property string|null $ID Primary Key
 * @property int|null $AllowCostsInSales Allow cost base amount and vat amount to be generated in sales entries
 * @property int|null $AssimilatedVATBox AssimilatedVATBox (France)
 * @property string|null $BalanceSide The following values are supported: D (Debit) C (Credit)
 * @property string|null $BalanceType The following values are supported: B (Balance Sheet) W (Profit & Loss)
 * @property int|null $BelcotaxType Indentify the kind of rewarding for the G/L account. This is used in the official document for the fiscal fiches Belcotax
 * @property string|null $Code Unique Code of the G/L account
 * @property bool|null $Compress Indicate if this G/L account should be shown as compressed without the details in the CRW report of G/L history
 * @property string|null $Costcenter Cost Center linked to the G/L account
 * @property string|null $CostcenterDescription Description of Costcenter
 * @property string|null $Costunit Cost Unit linked to the G/L account
 * @property string|null $CostunitDescription Description of Costunit
 * @property string|null $Created Creation date
 * @property string|null $Creator User ID of creator
 * @property string|null $CreatorFullName Name of creator
 * @property string|null $CustomField Custom field endpoint
 * @property DeductibilityPercentage[] $DeductibilityPercentages Deductibility percentages. You can have several Deductibility percentages, with start and end dates
 * @property string|null $Description Name of the G/L account. If Multilanguage featureset is enabled in the administration and the G/L account already has a set of termed description, this field is not allowed to change.
 * @property int|null $Division Division code
 * @property bool|null $ExcludeVATListing General ledger transactions on this G/L account should not appear on the VAT listing
 * @property float|null $ExpenseNonDeductiblePercentage Expenses on this G/L account can not be used to reduce the incomes
 * @property bool|null $IsBlocked When blocked you can't use this general ledger account anymore for new entries
 * @property bool|null $Matching Allow entries on this general ledger account to be matched via the G/L account card
 * @property string|null $Modified Last modified date
 * @property string|null $Modifier User ID of modifier
 * @property string|null $ModifierFullName Name of modifier
 * @property string|null $PrivateGLAccount If a private use percentage is defined, you need to specify the G/L account used for the re-invoice of the private use to the owner of the company
 * @property float|null $PrivatePercentage Specify the percentage of the cost that should be re-invoiced to the owner of the company as private use of the costs
 * @property string|null $ReportingCode Used in the export of yearly report
 * @property bool|null $RevalueCurrency Indicates if the amounts booked on this general ledger account will be recalculated when currency revaluation is done
 * @property string|null $SearchCode Search Code
 * @property int|null $Type The type of the G/L account. Supported values are:10 = Cash12 = Bank14 = Credit card16 = Payment services20 = Accounts receivable21 = Prepayment accounts receivable22 = Accounts payable24 = VAT25 = Employees payable26 = Prepaid expenses27 = Accrued expenses29 = Income taxes payable30 = Fixed assets32 = Other assets35 = Accumulated depreciation40 = Inventory50 = Capital stock52 = Retained earnings55 = Long term debt60 = Current portion of debt90 = General100 = Tax payable110 = Revenue111 = Cost of goods120 = Other costs121 = Sales, general administrative expenses122 = Depreciation costs123 = Research and development125 = Employee costs126 = Employment costs130 = Exceptional costs140 = Exceptional income150 = Income taxes160 = Interest income300 = Year end reflection301 = Indirect year end costing302 = Direct year end costing
 * @property string|null $TypeDescription Description of Type
 * @property int|null $UseCostcenter Indicates if cost centers can be used when using this general ledger account. The following values are supported: 0 (Optional) 1 (Mandatory) 2 (No)
 * @property int|null $UseCostunit Indicates if cost units can be used when using this general ledger account. The following values are supported: 0 (Optional) 1 (Mandatory) 2 (No)
 * @property string|null $VATCode VAT Code linked to the G/L account
 * @property string|null $VATDescription Description of VAT
 * @property string|null $VATGLAccountType Specify the kind of purchase this G/L account is used for. This is important for the Belgian VAT return to indicate in which VAT box the base amount of purchase should go
 * @property string|null $VATNonDeductibleGLAccount If you use a percentage of non deductible VAT, you can specify another G/L account that will be used for the non deductible part of the VAT amount. This is used directly in the entry application of purchase invoices.
 * @property float|null $VATNonDeductiblePercentage If not the full amount of the VAT is deductible, you can indicate a percentage for the non decuctible part. This is used during the entry of purchase invoices
 * @property string|null $VATSystem The following values are supported: I (Invoice) C (Cash) (France)
 * @property string|null $YearEndCostGLAccount Indicates the costing account for year end calculations
 * @property string|null $YearEndReflectionGLAccount Indicates the reflection account that is used by year end application
 */
class GLAccount extends Model {}

/**
 * @property string|null $EntryID Primary key
 * @property string|null $Created Creation date
 * @property string|null $Creator User ID of the creator
 * @property string|null $CreatorFullName Name of the creator
 * @property string|null $DeliveryAccount Reference to account for delivery
 * @property string|null $DeliveryAccountCode Delivery account code
 * @property string|null $DeliveryAccountName Account name
 * @property string|null $DeliveryAddress Reference to shipping address
 * @property string|null $DeliveryContact Reference to contact for delivery
 * @property string|null $DeliveryContactPersonFullName Name of the contact person of the customer who will receive delivered goods
 * @property string|null $DeliveryDate Date of goods delivery
 * @property int|null $DeliveryNumber Delivery number
 * @property string|null $Description Header description
 * @property int|null $Division Division code
 * @property string|null $Document Document that is manually linked to the sales order delivery
 * @property string|null $DocumentSubject Document Subject
 * @property int|null $EntryNumber Entry number
 * @property mixed $GoodsDeliveryLines Collection of lines
 * @property string|null $Modified Last modified date
 * @property string|null $Modifier User ID of modifier
 * @property string|null $ModifierFullName Name of modifier
 * @property string|null $Remarks Remarks
 * @property string|null $ShippingMethod Reference to shipping method. Define shipping method during POST, else it will be empty by default.
 * @property string|null $ShippingMethodCode Code of shipping method
 * @property string|null $ShippingMethodDescription Description of shipping method
 * @property string|null $TrackingNumber Reference to header tracking number
 * @property string|null $SalesOrderID ID of the related sales order
 * @property string|null $Warehouse Warehouse
 * @property string|null $WarehouseCode Code of Warehouse
 * @property string|null $WarehouseDescription Description of Warehouse
 */
class GoodsDelivery extends Model {}

/**
 * @property string|null $ID The unique identifier of a stock transaction for a goods delivery line. A goods delivery line can be split into multiple storage locations. In this case, multiple storage locations will have the same stock transaction ID.
 * @property StockBatchNumber[] $BatchNumbers Collection of batch numbers
 * @property string|null $Created Creation date
 * @property string|null $Creator User ID of creator
 * @property string|null $CreatorFullName Name of creator
 * @property string|null $CustomerItemCode Code the customer uses for this item
 * @property string|null $DeliveryDate Date of goods delivery
 * @property string|null $Description Description of sales order delivery
 * @property int|null $Division Division code
 * @property string|null $EntryID The EntryID identifies the goods delivery. All the lines of a goods delivery have the same EntryID
 * @property string|null $Item Reference to item
 * @property string|null $ItemCode Item code
 * @property string|null $ItemDescription Description of item
 * @property int|null $LineNumber Line number
 * @property string|null $Modified Last modified date
 * @property string|null $Modifier User ID of modifier
 * @property string|null $ModifierFullName Name of modifier
 * @property string|null $Notes Notes
 * @property float|null $QuantityDelivered Quantity delivered
 * @property float|null $QuantityOrdered Quantity ordered
 * @property string|null $SalesOrderLineID Reference to sales order
 * @property int|null $SalesOrderLineNumber Sales order line number
 * @property int|null $SalesOrderNumber Sales order number
 * @property StockSerialNumber[] $SerialNumbers Collection of serial numbers
 * @property string|null $StorageLocation Reference to storage location
 * @property string|null $StorageLocationCode Storage location code
 * @property string|null $StorageLocationDescription Storage location description
 * @property string|null $TrackingNumber Reference to tracking number
 * @property string|null $Unitcode Code of item unit
 */
class GoodsDeliveryLine extends Model {}

/**
 * @property string|null $ID Primary key
 * @property string|null $Created Creation date
 * @property string|null $Creator User ID of the creator
 * @property string|null $CreatorFullName Name of the creator
 * @property string|null $Description Description of the goods receipt
 * @property int|null $Division Division code
 * @property string|null $Document Document that is linked to the goods receipt
 * @property string|null $DocumentSubject Document subject
 * @property int|null $EntryNumber Entry number of the resulting stock entry
 * @property int|null $GoodsReceiptLineCount Total row count of lines
 * @property mixed $GoodsReceiptLines Collection of receipt lines
 * @property string|null $Modified Last modified date
 * @property string|null $Modifier User ID of the last modifier
 * @property string|null $ModifierFullName Name of the last modifier
 * @property string|null $ReceiptDate Date of the goods receipt
 * @property int|null $ReceiptNumber Receipt number
 * @property string|null $Remarks Receipt note
 * @property string|null $Supplier Account ID of the supplier
 * @property string|null $SupplierCode Supplier code
 * @property string|null $SupplierContact ID of the contact person at the supplier
 * @property string|null $SupplierContactFullName Name of the contact person at the supplier
 * @property string|null $SupplierName Supplier name
 * @property string|null $Warehouse Warehouse ID
 * @property string|null $WarehouseCode Warehouse code
 * @property string|null $WarehouseDescription Description of the warehouse
 * @property string|null $PurchaseOrderID ID of the related purchase order
 * @property string|null $GoodsReceiptID Primary key of the goods receipt
 * @property string|null $YourRef The purchase invoice number provided by the supplier
 */
class GoodsReceipt extends Model {}

/**
 * @property string|null $ID The unique identifier of a stock transaction for a goods receipt line. A goods receipt line can be split into multiple storage locations. In this case, multiple storage locations will have the same stock transaction ID.
 * @property StockBatchNumber[] $BatchNumbers Collection of batch numbers
 * @property string|null $Created Creation date
 * @property string|null $Creator User ID of the creator
 * @property string|null $CreatorFullName Name of the creator
 * @property string|null $Description Goods receipt line description
 * @property int|null $Division Division code
 * @property string|null $Expense Expense related to the Work Breakdown Structure of the selected project. Only available with a professional service license
 * @property string|null $ExpenseDescription Description of expense. Only available with a professional service license
 * @property string|null $GoodsReceiptID All the lines of a goods receipt have the same GoodsReceiptID
 * @property string|null $Item ID of the received item
 * @property string|null $ItemCode Code of the received item
 * @property string|null $ItemDescription Item description
 * @property string|null $ItemUnitCode Unit code of the purchase
 * @property int|null $LineNumber Line number
 * @property string|null $Location ID of the storage location in the warehouse where the item is received
 * @property string|null $LocationCode Code of the storage location in the warehouse where the item is received
 * @property string|null $LocationDescription Description of the storage location in the warehouse where the item is received
 * @property string|null $Modified Last modified date
 * @property string|null $Modifier User ID of the last modifier
 * @property string|null $ModifierFullName Name of the last modifier
 * @property string|null $Notes Notes
 * @property string|null $Project Reference to project
 * @property string|null $ProjectCode Project code
 * @property string|null $ProjectDescription Project description
 * @property string|null $PurchaseOrderID Reference to purchase order
 * @property string|null $PurchaseOrderLineID ID of the purchase order line that is received
 * @property int|null $PurchaseOrderNumber Order number of the purchase order that is received
 * @property float|null $QuantityOrdered Quantity ordered
 * @property float|null $QuantityReceived Quantity received
 * @property bool|null $Rebill Indicates whether the purchase order line needs to be rebilled. Only available with a professional service license
 * @property StockSerialNumber[] $SerialNumbers Collection of serial numbers
 * @property string|null $SupplierItemCode Supplier item code
 */
class GoodsReceiptLine extends Model {}

/**
 * @property string|null $ID A guid that is the unique identifier of the item
 * @property float|null $AverageCost The current average cost price
 * @property string|null $Barcode Barcode of the item (numeric string)
 * @property string|null $Class_01 Item class code referring to ItemClasses with ClassID 1
 * @property string|null $Class_02 Item class code referring to ItemClasses with ClassID 2
 * @property string|null $Class_03 Item class code referring to ItemClasses with ClassID 3
 * @property string|null $Class_04 Item class code referring to ItemClasses with ClassID 4
 * @property string|null $Class_05 Item class code referring to ItemClasses with ClassID 5
 * @property string|null $Class_06 Item class code referring to ItemClasses with ClassID 6
 * @property string|null $Class_07 Item class code referring to ItemClasses with ClassID 7
 * @property string|null $Class_08 Item class code referring to ItemClasses with ClassID 8
 * @property string|null $Class_09 Item class code referring to ItemClasses with ClassID 9
 * @property string|null $Class_10 Item class code referring to ItemClasses with ClassID 10
 * @property string|null $Code Item code
 * @property int|null $CopyRemarks Copy sales remarks to sales lines
 * @property string|null $CostPriceCurrency The currency of the current and proposed cost price
 * @property float|null $CostPriceNew Proposed cost price
 * @property float|null $CostPriceStandard The current standard cost price
 * @property string|null $Created Creation date
 * @property string|null $Creator User ID of creator
 * @property string|null $CreatorFullName Name of creator
 * @property string|null $CustomField Custom field endpoint. Provided only for the Exact Online Premium users.
 * @property string|null $Description Description of the item
 * @property int|null $Division Division code
 * @property string|null $EndDate Together with StartDate this determines if the item is active
 * @property string|null $ExtraDescription Extra description text, slightly longer than the regular description (255 instead of 60)
 * @property bool|null $FreeBoolField_01 Free boolean field 1
 * @property bool|null $FreeBoolField_02 Free boolean field 2
 * @property bool|null $FreeBoolField_03 Free boolean field 3
 * @property bool|null $FreeBoolField_04 Free boolean field 4
 * @property bool|null $FreeBoolField_05 Free boolean field 5
 * @property string|null $FreeDateField_01 Free date field 1
 * @property string|null $FreeDateField_02 Free date field 2
 * @property string|null $FreeDateField_03 Free date field 3
 * @property string|null $FreeDateField_04 Free date field 4
 * @property string|null $FreeDateField_05 Free date field 5
 * @property float|null $FreeNumberField_01 Free numeric field 1
 * @property float|null $FreeNumberField_02 Free numeric field 2
 * @property float|null $FreeNumberField_03 Free numeric field 3
 * @property float|null $FreeNumberField_04 Free numeric field 4
 * @property float|null $FreeNumberField_05 Free numeric field 5
 * @property float|null $FreeNumberField_06 Free numeric field 6
 * @property float|null $FreeNumberField_07 Free numeric field 7
 * @property float|null $FreeNumberField_08 Free numeric field 8
 * @property string|null $FreeTextField_01 Free text field 1
 * @property string|null $FreeTextField_02 Free text field 2
 * @property string|null $FreeTextField_03 Free text field 3
 * @property string|null $FreeTextField_04 Free text field 4
 * @property string|null $FreeTextField_05 Free text field 5
 * @property string|null $FreeTextField_06 Free text field 6
 * @property string|null $FreeTextField_07 Free text field 7
 * @property string|null $FreeTextField_08 Free text field 8
 * @property string|null $FreeTextField_09 Free text field 9
 * @property string|null $FreeTextField_10 Free text field 10
 * @property string|null $GLCosts GL account the cost entries will be booked on. This overrules the GL account from the item group. If the license contains 'Intuit integration' this property overrides the value in Settings, not the item group.
 * @property string|null $GLCostsCode Code of GL account for costs
 * @property string|null $GLCostsDescription Description of GLCosts
 * @property string|null $GLRevenue GL account the revenue will be booked on. This overrules the GL account from the item group. If the license contains 'Intuit integration' this property overrides the value in Settings, not the item group.
 * @property string|null $GLRevenueCode Code of GLRevenue
 * @property string|null $GLRevenueDescription Description of GLRevenue
 * @property string|null $GLStock GL account the stock entries will be booked on. This overrules the GL account from the item group. If the license contains 'Intuit integration' this property overrides the value in Settings, not the item group.
 * @property string|null $GLStockCode Code of GL account for stock
 * @property string|null $GLStockDescription Description of GLStock
 * @property float|null $GrossWeight Gross weight for international goods shipments
 * @property bool|null $IsBatchItem Indicates if batches are used for this item
 * @property bool|null $IsFractionAllowedItem Indicates if fractions (for example 0.35) are allowed for quantities of this item
 * @property int|null $IsMakeItem Indicates that an Item is produced to Inventory, not purchased
 * @property int|null $IsNewContract Only used for packages (IsPackageItem=1). To indicate if this package is a new contract type package
 * @property int|null $IsOnDemandItem Is On demand Item
 * @property bool|null $IsPackageItem Indicates if the item is a package item. Can only be created in the hosting administration
 * @property bool|null $IsPurchaseItem Indicates if the item can be purchased
 * @property bool|null $IsSalesItem Indicates if the item can be sold
 * @property bool|null $IsSerialItem Indicates that serial numbers are used for this item
 * @property bool|null $IsStockItem If you have the Trade or Manufacturing license and you check this property the item will be shown in the stock positions overview, stock counts and transaction lists. If you have the Invoice module and you check this property you will get a general journal entry based on the Stock and Costs G/L accounts of the item group. If you don’t want the general journal entry to be created you should change the Stock/Costs G/L account on the Item group page to the type Costs instead of Inventory. If you have the CRM Standalone license, the item will not be available.
 * @property bool|null $IsSubcontractedItem Indicates if the item is provided by an outside supplier
 * @property int|null $IsTaxableItem Indicates if tax needs to be calculated for this item
 * @property int|null $IsTime Indicates if the item is a time unit item (for example a labor hour item)
 * @property int|null $IsWebshopItem Indicates if the item can be exported to a web shop. If you have the CRM Standalone license, the item will not be available.
 * @property string|null $ItemGroup GUID of Item group of the item
 * @property string|null $ItemGroupCode Code of ItemGroup
 * @property string|null $ItemGroupDescription Description of ItemGroup
 * @property string|null $Modified Last modified date
 * @property string|null $Modifier User ID of modifier
 * @property string|null $ModifierFullName Name of modifier
 * @property float|null $NetWeight Net weight for international goods shipments
 * @property string|null $NetWeightUnit Net Weight unit for international goods shipment, only available in manufacturing packages
 * @property string|null $Notes Notes
 * @property string|null $Picture This field is write-only. The picture can be downloaded through PictureUrl and PictureThumbnailUrl.
 * @property string|null $PictureName File name of picture
 * @property string|null $PictureThumbnailUrl Url where thumbnail picture can be retrieved
 * @property string|null $PictureUrl Url where picture can be retrieved
 * @property string|null $SalesVatCode Code of SalesVat
 * @property string|null $SalesVatCodeDescription Description of SalesVatCode
 * @property string|null $SearchCode Search code of the item
 * @property int|null $SecurityLevel Security level (0 - 100)
 * @property float|null $StandardSalesPrice Standard sales price
 * @property string|null $StartDate Together with EndDate this determines if the item is active
 * @property string|null $StatisticalCode Statistical code
 * @property float|null $StatisticalNetWeight Statistical net weight
 * @property float|null $StatisticalUnits Statistical units
 * @property float|null $StatisticalValue Statistical value
 * @property float|null $Stock Quantity that is in stock
 * @property string|null $PurchaseCurrency Currency code for purchase price
 * @property float|null $PurchasePrice Purchase price
 * @property string|null $PurchaseVATCode VAT code used for purchase
 * @property string|null $SalesCurrency Currency code for sales price
 * @property float|null $SalesPrice Sales price
 * @property string|null $SalesVATCode VAT code used for sales
 * @property string|null $Unit The standard unit of this item
 * @property string|null $UnitDescription Description of Unit
 * @property string|null $UnitType Type of unit: A=Area, L=Length, O=Other, T=Time, V=Volume, W=Weight
 */
class Item extends Model {}

/**
 * @property string|null $ID Primary Key
 * @property bool|null $AllowVariableCurrency Indicates if the journal allows variable currency
 * @property bool|null $AllowVariableExchangeRate Indicates if the journal allows the exchange rate of the currency of the amounts in the journal entry to be changed
 * @property bool|null $AllowVAT Indicates if the journal allows the use of VAT in the financial entry. Applicable only for General Journals
 * @property bool|null $AutoSave Indicates if the journal automatically saves the entries when the amount is in balance with the entry lines. Applicable for all types except cash. In the UI is called 'Exit Automatically'
 * @property string|null $Bank Reference to bank account. Only Bank journal type will have a value
 * @property string|null $BankAccountBICCode BIC code of the bank where the bank account is held. Only Bank journal type will have a value
 * @property string|null $BankAccountCountry Country of bank account. Only Bank journal type will have a value
 * @property string|null $BankAccountDescription Description of BankAccount. Only Bank journal type will have a value
 * @property string|null $BankAccountIBAN IBAN of the bank account. Only Bank journal type will have a value
 * @property string|null $BankAccountID Reference to the Bank Account linked to the Journal. Only Bank journal type will have a value
 * @property string|null $BankAccountIncludingMask Bank account number. Is mandatory for Journals that have Type = Bank
 * @property string|null $BankName Name of bank account. Only Bank journal type will have a value
 * @property string|null $Code Primary key
 * @property string|null $Created Creation date
 * @property string|null $Creator User ID of creator
 * @property string|null $CreatorFullName Name of creator
 * @property string|null $Currency Default Currency of the Journal. If AllowVariableCurrency is false this is the only currency that can be used
 * @property string|null $CurrencyDescription Description of Currency
 * @property string|null $CustomField Custom field endpoint
 * @property string|null $Description Name of the Journal
 * @property int|null $Division Division code
 * @property string|null $GLAccount Suspense general ledger account
 * @property string|null $GLAccountCode Code of GLAccount
 * @property string|null $GLAccountDescription Description of GLAccount
 * @property int|null $GLAccountType Type of GLAccount
 * @property bool|null $IsBlocked Indicates whether the journal is blocked or not.
 * @property string|null $Modified Last modified date
 * @property string|null $Modifier User ID of modifier
 * @property string|null $ModifierFullName Name of modifier
 * @property string|null $PaymentInTransitAccount General ledger account for payment in transit
 * @property string|null $PaymentServiceAccountIdentifier Identifier detail of the Payment service account. Ex. EmailID for Paypal type of Payment service account
 * @property int|null $PaymentServiceProvider Type of Payment service provider. The following values are supported: 1 (Adyen), 2 (Paypal), 3 (Stripe). Is mandatory for Journals of Type 16 (Payment service)
 * @property string|null $PaymentServiceProviderName Name of the Payment service provider
 * @property int|null $Type Type of Journal. The following values are supported: 10 (Cash) 12 (Bank) 16 (Payment service) 20 (Sales) 21 (Return invoice) 22 (Purchase) 23 (Received return invoice) 90 (General journal)
 */
class Journal extends Model {}

/**
 * @property string|null $ID Identifier of the payment.
 * @property string|null $Account The supplier to which the payment has to be done.
 * @property string|null $AccountBankAccountID The bank account of the supplier, to which the payment has to be done.
 * @property string|null $AccountBankAccountNumber The bank account number of the supplier, to which the payment has to be done.
 * @property string|null $AccountCode The code of the supplier to which the payment has to be done.
 * @property string|null $AccountContact Contact person copied from the purchase invoice linked to the related purchase entry. Used as prefered contact when sending reminders.
 * @property string|null $AccountContactName Name of the contact person of the supplier.
 * @property string|null $AccountName Name of the supplier.
 * @property float|null $AmountDC The amount in default currency (division currency). Payments are matched on this amount.
 * @property float|null $AmountDiscountDC The amount of the discount in the default currency.
 * @property float|null $AmountDiscountFC The amount of the discount. This is in the amount of the selected currency.
 * @property float|null $AmountFC The amount of the payment. This is in the amount of the selected currency.
 * @property string|null $BankAccountID Own bank account from which the payment must be done.
 * @property string|null $BankAccountNumber Own bank account number from which the payment must be done.
 * @property string|null $CashflowTransactionBatchCode When processing payments, all payments with the same processing data are put in a batch. This field contains the code of that batch.
 * @property string|null $Created Creation date.
 * @property string|null $Creator User ID of the creator.
 * @property string|null $CreatorFullName Name of the creator.
 * @property string|null $Currency The currency of the payment. This currency can only deviate from the division currency if the module Currency is in the license.
 * @property string|null $Description Extra description for the payment that may be included in the bank export file.
 * @property string|null $DiscountDueDate Date before which the payment must be done to be eligible for discount.
 * @property int|null $Division Division code.
 * @property string|null $Document Document that is created when processing payments. The bank export file is attached to the document.
 * @property int|null $DocumentNumber Number of the document.
 * @property string|null $DocumentSubject Subject of the document.
 * @property string|null $DueDate Date before which the payment must be done.
 * @property string|null $EndDate Date since when the payment is no longer an outstanding item. This is the highest invoice date of all matched payments.
 * @property int|null $EndPeriod Period since when the payment is no longer an outstanding item. This is the highest period of all matched payments.
 * @property int|null $EndYear Year (of period) since when the payment is no longer an outstanding item. This is the highest year of all matched payments. Used in combination with EndPeriod.
 * @property string|null $EntryDate Processing date of the payment.
 * @property string|null $EntryID The unique identifier for a set of payments. A payment can be split so that one part is paid on a different date. In that case the two records get a different EntryID.
 * @property int|null $EntryNumber Entry number of the linked transaction.
 * @property string|null $GLAccount G/L account of the payment. Must be of type 22 (Accounts payable).
 * @property string|null $GLAccountCode Code of the G/L account.
 * @property string|null $GLAccountDescription Description of the G/L account.
 * @property string|null $InvoiceDate Invoice date of the linked transaction.
 * @property int|null $InvoiceNumber Invoice number of the linked transaction.
 * @property int|null $IsBatchBooking Boolean indicating whether the payment is part of a batch booking.
 * @property string|null $Journal Journal of the linked transaction.
 * @property string|null $JournalDescription Description of the journal.
 * @property string|null $Modified Last modified date.
 * @property string|null $Modifier User ID of modifier.
 * @property string|null $ModifierFullName Name of modifier.
 * @property int|null $PaymentBatchNumber Number assigned during the of processing payments. When payments are processed a bank export file is created. This file contains one or more batches that contain one or more payments. Each batch gets a sequence number that is stored for each payment in that batch.
 * @property string|null $PaymentCondition Payment condition of the linked transaction.
 * @property string|null $PaymentConditionDescription Description of the payment condition.
 * @property int|null $PaymentDays Number of days between invoice date and due date.
 * @property int|null $PaymentDaysDiscount Number of days between invoice date and due date of the discount.
 * @property float|null $PaymentDiscountPercentage Payment discount percentage.
 * @property string|null $PaymentMethod Method of payment. B = On credit (default) I = Collection K = Cash V = Credit card.
 * @property string|null $PaymentReference Payment reference for the payment that may be included in the bank export file.
 * @property string|null $PaymentSelected Date and time since when the payment is selected to be paid.
 * @property string|null $PaymentSelector User who selected the payment to be paid.
 * @property string|null $PaymentSelectorFullName Name of the payment selector.
 * @property float|null $RateFC Exchange rate from payment currency to division currency. AmountFC * RateFC = AmountDC.
 * @property int|null $Source The source of the payment.
 * @property int|null $Status The status of the payment. 20 = open 30 = selected - payment is selected to be paid 40 = processed - payment has been done 50 = matched - payment is matched with one or more other outstanding items or financial statement lines
 * @property float|null $TransactionAmountDC Total amount of the linked transaction in default currency (division currency).
 * @property float|null $TransactionAmountFC Total amount of the linked transaction in the selected currency.
 * @property string|null $TransactionDueDate Due date of the linked transaction.
 * @property string|null $TransactionEntryID Linked transaction. Use this as reference to PurchaseEntries.
 * @property string|null $TransactionID Linked transaction line. Use this as reference to PurchaseEntryLines
 * @property bool|null $TransactionIsReversal Indicates if the linked transaction is a reversal entry.
 * @property int|null $TransactionReportingPeriod Period of the linked transaction.
 * @property int|null $TransactionReportingYear Year of the linked transaction.
 * @property int|null $TransactionStatus Status of the linked transaction.
 * @property int|null $TransactionType Type of the linked transaction.
 * @property string|null $YourRef Invoice number of the supplier. In case the payment belongs to a bank entry line and is matched with one invoice, YourRef is filled with the YourRef of this invoice.
 */
class Payment extends Model {}

/**
 * @property string|null $ID Primary key
 * @property string|null $Account The account for this project
 * @property string|null $AccountCode Code of Account
 * @property string|null $AccountContact Contact person of Account
 * @property string|null $AccountName Name of Account
 * @property bool|null $AllowAdditionalInvoicing Indicates if additional invoice is allowed for project
 * @property bool|null $AllowMemberEntryOnly Allow only member to create time or cost entry
 * @property bool|null $BlockEntry Block time and cost entries
 * @property bool|null $BlockInvoicing Block invoicing
 * @property bool|null $BlockPlanning Block planning and reservations
 * @property bool|null $BlockPurchasing Block purchasing
 * @property bool|null $BlockRebilling Block rebilling
 * @property float|null $BudgetedAmount Budgeted amount of sales in the default currency of the company
 * @property float|null $BudgetedCosts Budgeted amount of costs in the default currency of the company
 * @property ProjectHourBudget|null $BudgetedHoursPerHourType Collection of budgeted hours
 * @property float|null $BudgetedRevenue Budgeted amount of revenue in the default currency of the company
 * @property int|null $BudgetOverrunHours BudgetOverrunHours: 10-Allowed, 20-Not Allowed
 * @property int|null $BudgetType Budget type
 * @property string|null $BudgetTypeDescription Budget type description
 * @property string|null $Classification Used only for PSA to link a project classification to the project
 * @property string|null $ClassificationDescription Description of Classification
 * @property string|null $Code Code Note : Code is not mandatory in PSA packages.If no code is provided, project auto number will be used, but this can only be applied to PSA packages.
 * @property float|null $CostsAmountFC Used only for PSA to store the budgetted costs of a project (except for project type Campaign and Non-billable). Positive quantities only
 * @property string|null $Created Creation date
 * @property string|null $Creator User ID of creator
 * @property string|null $CreatorFullName Name of creator
 * @property string|null $CustomerPOnumber Used only for PSA to store the customer's PO number
 * @property string|null $CustomField Custom field endpoint. Provided only for the Exact Online Premium users.
 * @property string|null $Description Description of the project
 * @property int|null $Division Division code
 * @property string|null $DivisionName Name of Division
 * @property string|null $EndDate End date of the project. In combination with the start date the status is determined
 * @property string|null $FixedPriceItem Item used for fixed price invoicing. To be defined per project. If empty the functionality relies on the setting
 * @property string|null $FixedPriceItemDescription Description of FixedPriceItem
 * @property bool|null $HasWBSLines Indicates if whether the Project has WBS
 * @property int|null $IncludeInvoiceSpecification Include invoice specification. E.g: 1 = Based on account, 2 = Always, 3 = Never
 * @property bool|null $IncludeSpecificationInInvoicePdf Indicates whether to include invoice specification in invoice PDF
 * @property string|null $InternalNotes Internal notes not to be printed in invoice
 * @property string|null $InvoiceAddress Invoice address
 * @property bool|null $InvoiceAsQuoted Indicates whether the project is invoice as quoted
 * @property string|null $InvoiceDescription Description for generate project invoice
 * @property InvoiceTerm[] $InvoiceTerms Collection of invoice terms
 * @property int|null $IsWBSRequiredForEntry Indicates whether the project WBS is required for time and cost entry E.g: 0 = Based on company setting, 1 = Yes, 2 = No
 * @property string|null $Manager Responsible person for this project
 * @property string|null $ManagerFullname Name of Manager
 * @property float|null $MarkupPercentage Purchase markup percentage
 * @property string|null $Modified Last modified date
 * @property string|null $Modifier User ID of modifier
 * @property string|null $ModifierFullName Name of modifier
 * @property string|null $Notes For additional information about projects
 * @property string|null $PaymentCondition Payment condition code for this project
 * @property string|null $PrepaidItem Used only for PSA. This item is used for prepaid invoicing. If left empty, the functionality relies on a setting
 * @property string|null $PrepaidItemDescription Description of PrepaidItem
 * @property int|null $PrepaidType PrepaidType: 1-Retainer, 2-Hour type bundle
 * @property string|null $PrepaidTypeDescription Description of PrepaidType
 * @property ProjectRestrictionEmployee[] $ProjectRestrictionEmployees Collection of employee restrictions
 * @property ProjectRestrictionItem[] $ProjectRestrictionItems Collection of item restrictions
 * @property ProjectRestrictionRebilling[] $ProjectRestrictionRebillings Collection of rebilling restrictions
 * @property float|null $SalesTimeQuantity Budgeted time. Total number of hours estimated for the fixed price project
 * @property string|null $SourceQuotation Source quotation
 * @property string|null $StartDate Start date of a project. In combination with the end date the status is determined
 * @property float|null $TimeQuantityToAlert Alert when exceeding (Hours)
 * @property int|null $Type Reference to ProjectTypes. E.g: 1 = Campaign , 2 = Fixed Price, 3 = Time and Material, 4 = Non billable, 5 = Prepaid
 * @property string|null $TypeDescription Description of Type
 * @property bool|null $UseBillingMilestones Indicates whether the Project is using billing milestones
 */
class Project extends Model {}

/**
 * @property string|null $ID Primary key
 * @property float|null $Budget Number of hours to be budgeted to a project
 * @property string|null $Created Date and time when the project hour budget was created
 * @property string|null $Creator ID of user that created the project hour budget
 * @property string|null $CreatorFullName Full name of user that created the project hour budget
 * @property int|null $Division Division number
 * @property string|null $Item ID of hour type of budget
 * @property string|null $ItemCode Code of hour type
 * @property string|null $ItemDescription Description of hour type
 * @property string|null $Modified Last modified date of project hour budget
 * @property string|null $Modifier ID of last user that modified the project hour budget
 * @property string|null $ModifierFullName Full name of last user that modified the project hour budget
 * @property string|null $Project Project ID that the budgeted hours is referenced to
 * @property string|null $ProjectCode Project code that the budgeted hours is referenced to
 * @property string|null $ProjectDescription Project description that the budgeted hours is referenced to
 */
class ProjectHourBudget extends Model {}

/**
 * @property string|null $ID A guid that is the unique identifier of the purchase invoice.
 * @property float|null $Amount The amount including VAT in the foreign currency.
 * @property string|null $ContactPerson Guid identifying the contact person of the supplier.
 * @property string|null $Currency The code of the currency of the invoiced amount.
 * @property string|null $Description The description of the invoice.
 * @property string|null $Document Guid identifying a document that is attached to the invoice.
 * @property string|null $DueDate The date before which the invoice has to be paid. This by default will be set according to the payment condition.
 * @property int|null $EntryNumber The unique number of the purchase invoice. The entry number is based on a setting in the purchase journal and incremented for each new purchase invoice.
 * @property float|null $ExchangeRate The exchange rate between the invoice currency and the default currency of the division.
 * @property int|null $FinancialPeriod The financial period in which the invoice is entered.
 * @property int|null $FinancialYear The financial year in which the invoice is entered.
 * @property string|null $InvoiceDate The date on which the supplier entered the invoice.
 * @property string|null $Journal The code of the purchase journal in which the invoice is entered.
 * @property string|null $Modified The date and time the invoice was last modified.
 * @property string|null $PaymentCondition The code of the payment condition that is used to calculate the due date and discount.
 * @property string|null $PaymentReference Unique reference to match payments and invoices.
 * @property mixed $PurchaseInvoiceLines The collection of lines that belong to the purchase invoice.
 * @property string|null $Remarks The user can enter remarks related to the invoice here.
 * @property int|null $Source Indicates the origin of the invoice. 1 Manual entry, 3 Purchase invoice, 4 Purchase order, 5 Web service.
 * @property int|null $Status The status of the invoice. 10 Draft, 20 Open, 50 Processed.
 * @property string|null $Supplier Guid that identifies the supplier.
 * @property int|null $Type Indicates the type of the purchase invoice. 8030 Direct purchase invoice, 8031 Direct purchase invoice (Credit), 8033 Purchase invoice, 8034 Purchase invoice (Credit)
 * @property float|null $VATAmount The total VAT amount of the purchase invoice.
 * @property string|null $Warehouse Guid that identifies the warehouse that will receive the purchased goods. This is mandatory for creating a direct purchase invoice except for Exact Online Projects.
 * @property string|null $YourRef The invoice number provided by the supplier.
 */
class PurchaseInvoice extends Model {}

/**
 * @property string|null $ID A guid that uniquely identifies the purchase invoice line.
 * @property float|null $Amount In a GET request the line amount is always returned excluding VAT in foreign currency.In a POST request the line amount has to be submitted either including or excluding the VAT amount. This depends on the type (including or excluding) of the VAT code.
 * @property string|null $CostCenter The code of the cost center that is linked to this invoice line.
 * @property string|null $CostUnit The code of the cost unit that is linked to this invoice line.
 * @property string|null $Currency The currency of the line amount. The total invoice amount and all individual line amounts are in the same currency.
 * @property string|null $CustomField Custom field endpoint. Provided only for the Exact Online Premium users.
 * @property string|null $Description Description of the invoice line.
 * @property float|null $Discount The discount given on the default price. A value of 0.1 translates to 10% discount.
 * @property string|null $Expense Expense related to the Work Breakdown Structure of the selected project. Only available with a professional service license
 * @property string|null $ExpenseDescription Description of expense. Only available with a professional service license
 * @property string|null $InvoiceID The unique identifier of the purchase invoice this line belongs to.
 * @property string|null $Item Guid that identifies the purchase item. In a POST request either the Item or the PurchaseOrderLine has to be supplied.
 * @property string|null $ItemUnit The default unit of the purchased item.
 * @property int|null $LineNumber The sequence number of the line.
 * @property string|null $Modified The date and time the invoice line was last modified.
 * @property float|null $NetPrice The net price that has to be paid per unit. NetPrice = UnitPrice * (1.0 - Discount).Depending on the type of the VAT code the net price is including or excluding VAT.
 * @property string|null $Notes The user can enter notes related to the invoice line here.
 * @property string|null $Project The project linked to the purchase invoice line. This field is only applicable for Manufacturing and Professional Services.
 * @property string|null $PurchaseOrderLine Guid that identifies the purchase order line that is being invoiced. When doing a POST either the Item or the PurchaseOrderLine has to be supplied.The values of the purchase order line such as Quantity, Item and Amount will be copied to the purchase invoice line.
 * @property float|null $Quantity The number of purchased items in purchase units. The purchase unit is defined on the item card and it can also be found using the logistics/SupplierItem api endpoint.For divisible items the quantity can be a fractional number, otherwise it is an integer.
 * @property float|null $QuantityInDefaultUnits The number of purchased items in default units. An item has both a default unit and a purchase unit, for example piece and box with a box containing 12 pieces. The multiplication factor (12 in this example) between the default unit and purchase unit is maintained on the item card. When you GET a purchase invoice line for 1 box of items the field Quantity = 1 and QuantityInDefaultUnits = 12.
 * @property bool|null $Rebill Indicates whether the purchase invoice line needs to be rebilled. Only available with a professional service license
 * @property string|null $Unit The code of the unit in which the item is purchased. For example piece, box or kg. The value is taken from the purchase unit in the item card.
 * @property float|null $UnitPrice The default purchase price per unit.Depending on the type of the VAT code the unit price is including or excluding VAT.
 * @property float|null $VATAmount The VAT amount of the invoice line.
 * @property string|null $VATCode The VAT code used for the invoice line.
 * @property float|null $VATPercentage The VAT percentage.
 */
class PurchaseInvoiceLine extends Model {}

/**
 * @property string|null $PurchaseOrderID Primary key
 * @property float|null $AmountDC Total amount in the default currency of the company
 * @property float|null $AmountFC Total amount in the currency of the transaction
 * @property int|null $ApprovalStatus Approval status of purchase order. 0=Awaiting approval, 1=Automatically, 2=Approved.
 * @property string|null $ApprovalStatusDescription Description of ApprovalStatus
 * @property string|null $Approved Approval datetime
 * @property string|null $Approver User who approved the purchase order
 * @property string|null $ApproverFullName Name of approver
 * @property string|null $Created Creation date
 * @property string|null $Creator User ID of creator
 * @property string|null $CreatorFullName Name of creator
 * @property string|null $Currency Currency code
 * @property string|null $DeliveryAccount Reference to account for delivery
 * @property string|null $DeliveryAccountCode Delivery account code
 * @property string|null $DeliveryAccountName Account name
 * @property string|null $DeliveryAddress Reference to shipping address
 * @property string|null $DeliveryContact Reference to contact for delivery
 * @property string|null $DeliveryContactPersonFullName Name of the contact person of the customer who will receive delivered goods
 * @property string|null $Description Description of the purchase order
 * @property int|null $Division Division code
 * @property string|null $Document Document that is manually linked to the purchase order
 * @property string|null $DocumentSubject Subject of the document
 * @property bool|null $DropShipment Shows if it is a drop shipment purchase order
 * @property float|null $ExchangeRate The exchange rate between the invoice currency and the default currency of the division.
 * @property string|null $IncotermAddress Address of Incoterm
 * @property string|null $IncotermCode Code of Incoterm
 * @property int|null $IncotermVersion Version of Incoterm Supported version for Incoterms : 2010, 2020
 * @property int|null $InvoiceStatus Invoice status of purchase order: 10-Open, 20-Partial, 30-Complete, 40-Canceled
 * @property string|null $Modified Last modified date
 * @property string|null $Modifier User ID of modifier
 * @property string|null $ModifierFullName Name of modifier
 * @property string|null $OrderDate Order date
 * @property int|null $OrderNumber Human readable id of the purchase order
 * @property int|null $OrderStatus Purchase order status: 10-Open, 20-Partial, 30-Complete, 40-Canceled
 * @property string|null $PaymentCondition The payment condition code used for due date and discount calculation
 * @property string|null $PaymentConditionDescription Description of payment condition
 * @property string|null $PurchaseAgent Purchase agent
 * @property string|null $PurchaseAgentFullName Name of purchase agent
 * @property int|null $PurchaseOrderLineCount Total row count of lines
 * @property mixed $PurchaseOrderLines Collection of lines
 * @property string|null $ReceiptDate This field shows the date the goods are expected to be received.
 * @property int|null $ReceiptStatus Receipt status of purchase order: 10-Open, 20-Partial, 30-Complete, 40-Canceled
 * @property string|null $Remarks Include any relevant remarks regarding the purchase order.
 * @property string|null $SalesOrder Reference to sales order when purchase order generated via back to back sales order. Show NULL if more than one sales order is linked to the purchase order.
 * @property int|null $SalesOrderNumber Number of the sales order. Show NULL if more than one sales order is linked to the purchase order.
 * @property string|null $SelectionCode ID of selection code. Only supported by the Plus, Professional and Premium for Wholesale & Distribution and Manufacturing
 * @property string|null $SelectionCodeCode Code of selection code
 * @property string|null $SelectionCodeDescription Description of selection code
 * @property string|null $ShippingMethod ShippingMethod
 * @property string|null $ShippingMethodCode Code of ShippingMethod
 * @property string|null $ShippingMethodDescription Description of ShippingMethod
 * @property int|null $Source This shows how the purchase order was created: 1-Manual entry, 2-Import, 3-Other, 4-Purchase order, 5-Sales order, 6-Supplier's items, 7-Subcontract, 8-Purchase order advice, 9-Shop order, 10-MRP calculation, 11-Rest API, 12-Merge purchase orders
 * @property string|null $Supplier Reference to supplier account
 * @property string|null $SupplierCode Code of supplier
 * @property string|null $SupplierContact Contact of supplier
 * @property string|null $SupplierContactPersonFullName Contact person full name of supplier
 * @property string|null $SupplierName Name of supplier
 * @property float|null $VATAmount Total VAT amount in the currency of the transaction
 * @property string|null $Warehouse Warehouse
 * @property string|null $WarehouseCode Code of Warehouse
 * @property string|null $WarehouseDescription Description of Warehouse
 * @property string|null $YourRef Shows the reference number associated with the purchase order. Enter a description and reference to make the purchase order easier to identify.
 */
class PurchaseOrder extends Model {}

/**
 * @property string|null $ID Primary key
 * @property float|null $AmountDC Amount in the default currency of the company
 * @property float|null $AmountFC Amount in the currency of the transaction
 * @property string|null $CostCenter Reference to Cost center
 * @property string|null $CostCenterDescription Description of CostCenter
 * @property string|null $CostUnit Reference to Cost unit
 * @property string|null $CostUnitDescription Description of CostUnit
 * @property string|null $Created Creation date
 * @property string|null $Creator User ID of creator
 * @property string|null $CreatorFullName Name of creator
 * @property string|null $CustomField Custom field endpoint. Provided only for the Exact Online Premium users.
 * @property string|null $Description Description of the purchase order line
 * @property float|null $Discount Discount in percentage for item
 * @property int|null $Division Division code
 * @property string|null $Expense Expense related to the Work Breakdown Structure of the selected project. Only available with a professional service license
 * @property string|null $ExpenseDescription Description of expense. Only available with a professional service license
 * @property float|null $InStock The current stock level of items shown in stock unit. The information is displayed only for items with the stock property selected.
 * @property float|null $InvoicedQuantity Quantity of item that has been invoiced
 * @property int|null $IsBatchNumberItem Indicates that an Item is an batch item
 * @property int|null $IsSerialNumberItem Indicates that an Item is an serial item
 * @property string|null $Item Reference to the item for purchase order
 * @property string|null $ItemBarcode Barcode of the item (numeric string)
 * @property string|null $ItemBarcodeAdditional This is the barcode for the unit other than standard unit of the item. Only supported by the Premium for Wholesale & Distribution and Manufacturing
 * @property string|null $ItemCode Item code
 * @property string|null $ItemDescription Description of item
 * @property bool|null $ItemDivisable Indicates if fractional quantities of the item can be used, for example quantity = 0.4
 * @property int|null $LineNumber Line number
 * @property string|null $Modified Last modified date
 * @property string|null $Modifier User ID of modifier
 * @property string|null $ModifierFullName Name of modifier
 * @property float|null $NetPrice The net price is the unit price (VAT code taken into account) with any discount applied
 * @property string|null $Notes Notes
 * @property string|null $Project Reference to project. Only available with a professional service license
 * @property string|null $ProjectCode Project code. Only available with a professional service license
 * @property string|null $ProjectDescription Description of the project. Only available with a professional service license
 * @property float|null $ProjectedStock The current stock level + the planned quantity to be received - the planned quantity to deliver shown in stock unit.
 * @property string|null $PurchaseOrderID Identifies the purchase order. All the lines of a purchase order have the same PurchaseOrderID
 * @property float|null $Quantity Quantity in item units
 * @property float|null $QuantityInPurchaseUnits Quantity in purchase units. Use this field when creating a purchase order
 * @property bool|null $Rebill Indicates whether the purchase order line needs to be rebilled. Only available with a professional service license
 * @property string|null $ReceiptDate Date the goods are expected to be received
 * @property float|null $ReceivedQuantity Quantity of goods received
 * @property string|null $SalesOrder Sales order that is linked to a back to back sales order in purchase order. Show NULL if more than one sales order is linked to the purchase order line.
 * @property string|null $SalesOrderLine Sales order line of the sales order that Is linked to a back to back sales order in purchase order. Show NULL if more than one sales order is linked to the purchase order line.
 * @property int|null $SalesOrderLineNumber Number of the sales order line. Show NULL if more than one sales order is linked to the purchase order line.
 * @property int|null $SalesOrderNumber Number of the sales order. Show NULL if more than one sales order is linked to the purchase order line.
 * @property ShopOrderMaterialPlan[] $ShopOrderMaterialPlans Collection of Shop order Material plans
 * @property ShopOrderRoutingStepPlan[] $ShopOrderRoutingStepPlans Collection of Shop order Routing step plans
 * @property string|null $SupplierItemCode Code the supplier uses for this item
 * @property int|null $SupplierItemCopyRemarks Indicate if the notes content should be copied from SupplierItem's remarks. The default follows the CopyRemarks value from SupplierItem. Values: 0 = Do not copy remark, 1 = Copy remark
 * @property string|null $Unit Code of item unit
 * @property string|null $UnitDescription Description of unit
 * @property float|null $UnitPrice Item price per purchase unit
 * @property float|null $VATAmount Amount of VAT charges calculated from total amount and vat percentage
 * @property string|null $VATCode The VAT code used when the invoice was registered
 * @property string|null $VATDescription Description of vat code
 * @property float|null $VATPercentage The VAT percentage of the VAT code. This is the percentage at the moment the invoice is created. It's also used by the default calculation of VAT amounts and VAT base amounts
 */
class PurchaseOrderLine extends Model {}

/**
 * @property string|null $QuotationID Identifier of the quotation
 * @property float|null $AmountDC Amount in the default currency of the company
 * @property float|null $AmountDiscount Discount Amount in the currency of the transaction
 * @property float|null $AmountDiscountExclVat Discount Amount excluding VAT in the currency of the transaction
 * @property float|null $AmountFC Amount in the currency of the transaction
 * @property string|null $CloseDate Date on which the customer accepted or rejected the quotation version
 * @property string|null $ClosingDate Date on which you expect to close/win the deal
 * @property string|null $Created Date and time on which the quotation was created
 * @property string|null $Creator User ID of the creator
 * @property string|null $CreatorFullName Name of the creator
 * @property string|null $Currency The currency of the quotation
 * @property string|null $DeliveryAccount The account where the items should delivered
 * @property string|null $DeliveryAccountCode The code of the delivery account
 * @property string|null $DeliveryAccountContact The contact person of the delivery account
 * @property string|null $DeliveryAccountContactFullName Full name of the delivery account contact person
 * @property string|null $DeliveryAccountName The name of the delivery account
 * @property string|null $DeliveryAddress The id of the delivery address
 * @property string|null $DeliveryDate The date of the delivery
 * @property string|null $Description The description of the quotation
 * @property int|null $Division Division code
 * @property string|null $Document Document linked to the quotation
 * @property string|null $DocumentSubject The subject of the document
 * @property string|null $DueDate Date after which the quotation is no longer valid
 * @property string|null $IncotermAddress Address of Incoterm
 * @property string|null $IncotermCode Code of Incoterm
 * @property int|null $IncotermVersion Version of Incoterm Supported version for Incoterms : 2010, 2020
 * @property string|null $InvoiceAccount The account to which the invoice is sent
 * @property string|null $InvoiceAccountCode The code of the invoice account
 * @property string|null $InvoiceAccountContact The contact person of the invoice account
 * @property string|null $InvoiceAccountContactFullName Full name of the invoice account contact person
 * @property string|null $InvoiceAccountName The name of the invoice account
 * @property string|null $Modified Date and time on which the quotation was last modified
 * @property string|null $Modifier User ID of the modifier
 * @property string|null $ModifierFullName Name of the modifier
 * @property string|null $Opportunity Opportunity linked to the quotation
 * @property string|null $OpportunityName The name of the opportunity
 * @property string|null $OrderAccount The account that requested the quotation
 * @property string|null $OrderAccountCode The code of the order account
 * @property string|null $OrderAccountContact The contact person of the order account
 * @property string|null $OrderAccountContactFullName Full name of the order account contact person
 * @property string|null $OrderAccountName The name of the order account
 * @property string|null $PaymentCondition Payment condition code
 * @property string|null $PaymentConditionDescription Payment condition description
 * @property string|null $Project The project linked to the quotation
 * @property string|null $ProjectCode The code of the project
 * @property string|null $ProjectDescription The description of the project
 * @property string|null $QuotationDate Date on which the quotation version is entered or printed. Both during entering and printing this date can be adjusted
 * @property mixed $QuotationLines The collection of quotation lines
 * @property int|null $QuotationNumber Unique number to indentify the quotation. By default this number is based on the setting for first available number
 * @property QuotationOrderChargeLine[] $QuotationOrderChargeLines Collection of shipping cost and order charge lines. Only applicable in POST. Ignore the URL returns in GET.
 * @property string|null $Remarks Extra text that can be added to the quotation
 * @property string|null $SalesChannel ID of Sales channel.
 * @property string|null $SalesChannelCode Code of Sales channel.
 * @property string|null $SalesChannelDescription Description of Sales channel.
 * @property string|null $SalesPerson The user that is responsible for the quotation version
 * @property string|null $SalesPersonFullName Full name of the sales person
 * @property string|null $SelectionCode ID of selection code. Only supported by the Plus, Professional and Premium for Wholesale & Distribution and Manufacturing
 * @property string|null $SelectionCodeCode Code of selection code
 * @property string|null $SelectionCodeDescription Description of selection code
 * @property string|null $ShippingMethod Shipping method ID
 * @property string|null $ShippingMethodDescription Shipping method description
 * @property int|null $Status The status of the quotation version. 5 = Rejected, 6 = Reviewed and closed, 10 = Recovery, 20 = Draft, 25 = Open, 35 = Processing... , 40 = Printed, 50 = Accepted, 60 = Awaiting online acceptance, 70 = Accepted but an error occurred during processing
 * @property string|null $StatusDescription The description of the status
 * @property float|null $VATAmountFC Total VAT amount in the currency of the transaction
 * @property int|null $VersionNumber Number indicating the different reviews which are made for the quotation
 * @property string|null $WarehouseCode Code of Warehouse
 * @property string|null $WarehouseDescription Description of Warehouse
 * @property string|null $WarehouseID Warehouse. Only supported by the Plus, Professional and Premium editions for Wholesale & Distribution and Manufacturing
 * @property string|null $YourRef The number by which this quotation is identified by the order account
 */
class Quotation extends Model {}

/**
 * @property string|null $ID Primary key
 * @property float|null $AmountDC Amount in the default currency of the company
 * @property float|null $AmountFC Amount in the currency of the transaction
 * @property string|null $CostCenter Reference to Cost center
 * @property string|null $CostCenterDescription Description of CostCenter
 * @property string|null $CostUnit Reference to Cost unit
 * @property string|null $CostUnitDescription Description of CostUnit
 * @property string|null $CustomerItemCode Code the customer uses for this item
 * @property string|null $CustomField Custom field endpoint
 * @property string|null $Description By default this contains the item description
 * @property float|null $Discount Discount given on the default price. This is stored as a fraction. ie 5.5% is stored as .055
 * @property int|null $Division Division code
 * @property string|null $Item Reference to the item that is sold in this quotation line
 * @property string|null $ItemDescription Description of the item
 * @property int|null $LineNumber Indicates the sequence of the lines within one quotation
 * @property float|null $NetPrice Net price of the quotation line
 * @property string|null $Notes Extra notes
 * @property bool|null $Optional Indicates the optional line
 * @property float|null $Quantity The number of items sold in default units. The quantity shown in the entry screen is Quantity * UnitFactor
 * @property string|null $QuotationID Identifies the quotation. All the lines of a quotation have the same QuotationID
 * @property int|null $QuotationNumber Unique number to indentify the quotation. By default this number is based on the setting for first available number
 * @property string|null $UnitCode Code of the item unit
 * @property string|null $UnitDescription Description of the item unit
 * @property float|null $UnitPrice Price per item unit
 * @property float|null $VATAmountFC VAT amount of the line in the currency of the transaction
 * @property string|null $VATCode The VAT code that is used when the quotation is invoiced
 * @property string|null $VATDescription Description of the VAT code
 * @property float|null $VATPercentage The VAT percentage of the VAT code
 * @property int|null $VersionNumber Number indicating the different reviews which are made for the quotation
 */
class QuotationLine extends Model {}

/**
 * @property string|null $InvoiceID Primary key
 * @property float|null $AmountDC For the header lines (LineNumber = 0) of an entry this is the SUM(AmountDC) of all lines
 * @property float|null $AmountDiscount Discount amount in the default currency of the company
 * @property float|null $AmountDiscountExclVat Discount amount exclude VAT in the default currency of the company
 * @property float|null $AmountFC For the header this is the sum of all lines, including VAT
 * @property float|null $AmountFCExclVat For the header this is the sum of all lines, excluding VAT
 * @property string|null $Created Creation date
 * @property string|null $Creator User ID of creator
 * @property string|null $CreatorFullName Name of creator
 * @property string|null $Currency Currency for the invoice. Default this is the currency of the administration
 * @property string|null $DeliverTo Delivery account for invoice
 * @property string|null $DeliverToAddress Address of delivery as per invoice delivery account
 * @property string|null $DeliverToContactPerson Delivery account person for invoice
 * @property string|null $DeliverToContactPersonFullName Name of delivery account's contact person as per invoice
 * @property string|null $DeliverToName Name of the delivery account's customer as per invoice
 * @property string|null $Description Description. Can be different for header and lines
 * @property float|null $Discount Discount percentage
 * @property int|null $DiscountType Leading field of total discount. 1=Discount percentage, 2=Discount amount excl. VAT, 3=Discount amount incl. VAT, 4=Total amount excl. VAT, 5=Total amount incl. VAT
 * @property int|null $Division Division code
 * @property string|null $Document Document that is manually linked to the invoice
 * @property int|null $DocumentNumber Number of the document
 * @property string|null $DocumentSubject Subject of the document
 * @property string|null $DueDate The due date for payments. This date is calculated based on the EntryDate and the Paymentcondition
 * @property float|null $ExtraDutyAmountFC Extra duty amount in the currency of the transaction. Both extra duty amount and VAT amount need to be specified in order to differ this property from automatically calculated.
 * @property float|null $GAccountAmountFC A positive value of the amount indicates that the amount is to be paid by the customer to your G bank account.In case of a credit invoice the amount should have negative value when retrieved or posted to Exact.
 * @property string|null $IncotermAddress Address of Incoterm
 * @property string|null $IncotermCode Code of Incoterm
 * @property int|null $IncotermVersion Version of Incoterm Supported version for Incoterms : 2010, 2020
 * @property string|null $InvoiceDate Official date for the invoice. When the invoice is entered it's equal to the field 'EntryDate'. During the printing process the invoice date can be entered
 * @property int|null $InvoiceNumber Assigned at entry or at printing depending on setting. The number assigned is based on the freenumbers as defined for the Journal. When printing the field InvoiceNumber is copied to the fields EntryNumber and InvoiceNumber of the sales entry
 * @property string|null $InvoiceTo Reference to the Customer who will receive the invoice
 * @property string|null $InvoiceToContactPerson Reference to the Contact person of the customer who will receive the invoice
 * @property string|null $InvoiceToContactPersonFullName Name of the contact person of the customer who will receive the invoice
 * @property string|null $InvoiceToName Name of the customer who will receive the invoice
 * @property bool|null $IsExtraDuty Indicates whether the invoice has extra duty
 * @property string|null $Journal The journal code. Every invoice should be linked to a sales journal
 * @property string|null $JournalDescription Description of Journal
 * @property string|null $Modified Last modified date
 * @property string|null $Modifier User ID of modifier
 * @property string|null $ModifierFullName Name of modifier
 * @property string|null $OrderDate Order date
 * @property string|null $OrderedBy Customer who ordered the invoice
 * @property string|null $OrderedByContactPerson Contact person of customer who ordered the invoice
 * @property string|null $OrderedByContactPersonFullName Name of contact person of customer who ordered the invoice
 * @property string|null $OrderedByName Name of customer who ordered the invoice
 * @property int|null $OrderNumber Number to identify the order. By default the number is based on a setting for the first free number, but you can post your own number.
 * @property string|null $PaymentCondition The payment condition used for due date and discount calculation
 * @property string|null $PaymentConditionDescription Description of PaymentCondition
 * @property string|null $PaymentReference Payment reference for sales invoice
 * @property string|null $Remarks Extra remarks
 * @property string|null $SalesChannel ID of Sales channel.
 * @property string|null $SalesChannelCode Code of Sales channel.
 * @property string|null $SalesChannelDescription Description of Sales channel.
 * @property mixed $SalesInvoiceLines Collection of lines
 * @property SalesInvoiceOrderChargeLine[] $SalesInvoiceOrderChargeLines Collection of shipping cost and order charge lines. Only applicable in POST. Ignore the URL returns in GET.
 * @property string|null $Salesperson Sales representative
 * @property string|null $SalespersonFullName Name of sales representative
 * @property string|null $SelectionCode ID of selection code. Only supported by the Plus, Professional and Premium for Wholesale & Distribution and Manufacturing
 * @property string|null $SelectionCodeCode Code of selection code
 * @property string|null $SelectionCodeDescription Description of selection code
 * @property string|null $ShippingMethod Shipping method ID
 * @property string|null $ShippingMethodCode Shipping method code
 * @property string|null $ShippingMethodDescription Shipping method description
 * @property int|null $StarterSalesInvoiceStatus Starter Sales invoice status (for starter functionality)
 * @property string|null $StarterSalesInvoiceStatusDescription Description of StarterSalesInvoiceStatus
 * @property int|null $Status The status of the entry. 10 = draft. During the creation of an invoice draft records occur in the draft modus if during an invoice a new page with lines is triggered. If the user leaves the invoice in an abnormal way the draft invoices can be recovered. Draft invoices are not included in financial reports, balances etc. 20 = open. Open invoices can be changed. New invoices get the status open by default. 50 = processed. Processed invoices can't be changed anymore. Processing is done via printing. Processed invoices can't be reopened
 * @property string|null $StatusDescription Description of Status
 * @property int|null $Type Indicates the type of invoice Values: 8020 - Sales invoices, 8021 - Sales credit note, 8023 - Direct sales invoice, 8024 - Direct credit note. Type 8023 and 8024 are only supported by the Plus, Professional and Premium editions for Wholesale & Distribution and Manufacturing
 * @property string|null $TypeDescription Description of the type
 * @property float|null $VATAmountDC Total VAT amount in the default currency of the company
 * @property float|null $VATAmountFC Total VAT amount in the currency of the transaction
 * @property string|null $Warehouse Mandatory for direct sales invoice/credit note, cannot be set for normal sales invoice/credit note.
 * @property string|null $DeliveryAddress Delivery address ID
 * @property string|null $DeliveryDate Delivery date
 * @property float|null $WithholdingTaxAmountFC Withholding tax amount applied to sales invoice. Not supported in The Netherlands.
 * @property float|null $WithholdingTaxBaseAmount Withholding tax base amount to calculate withholding amount. Not supported in The Netherlands.
 * @property float|null $WithholdingTaxPercentage Withholding tax percentage applied to sales invoice. Not supported in The Netherlands.
 * @property string|null $YourRef The invoice number of the customer
 */
class SalesInvoice extends Model {}

/**
 * @property string|null $ID Primary key
 * @property float|null $AmountDC Amount in the default currency of the company. For almost all lines this can be calculated like: AmountDC = AmountFC * RateFC
 * @property float|null $AmountFC For normal lines it's the amount excluding VAT
 * @property string|null $CostCenter Reference to Cost center
 * @property string|null $CostCenterDescription Description of CostCenter
 * @property string|null $CostUnit Reference to Cost unit
 * @property string|null $CostUnitDescription Description of CostUnit
 * @property string|null $CustomerItemCode Code the customer uses for this item
 * @property string|null $CustomField Custom field endpoint. Provided only for the Exact Online Premium users.
 * @property string|null $DeliveryDate Delivery date of an item in a sales invoice. This is used for VAT on prepayments, only if sales order is not used in the license.
 * @property string|null $Description Description. Can be different for header and lines
 * @property float|null $Discount Discount given on the default price. Discount = (DefaultPrice of Item - PriceItem in line) / DefaultPrice of Item
 * @property int|null $Division Division code
 * @property string|null $Employee Link to Employee originating from time and cost transactions
 * @property string|null $EmployeeFullName Name of employee
 * @property string|null $EndTime EndTime is used to store the last date of a period. EndTime is used in combination with StartTime
 * @property float|null $ExtraDutyAmountFC Extra duty amount in the currency of the transaction. Both extra duty amount and VAT amount need to be specified in order to differ this property from automatically calculated.
 * @property float|null $ExtraDutyPercentage Extra duty percentage
 * @property string|null $GLAccount The GL Account of the sales invoice line. This field is mandatory. This field is generated based on the revenue account of the item (or the related item group). G/L Account is also used to determine whether the costcenter / costunit is mandatory
 * @property string|null $GLAccountDescription Description of GLAccount
 * @property string|null $InvoiceID The InvoiceID identifies the sales invoice. All the lines of a sales invoice have the same InvoiceID
 * @property string|null $Item Reference to the item that is sold in this sales invoice line
 * @property string|null $ItemCode Item code
 * @property string|null $ItemDescription Description of Item
 * @property int|null $LineNumber Indicates the sequence of the lines within one invoice
 * @property float|null $NetPrice Net price of the sales invoice line
 * @property string|null $Notes Extra notes
 * @property string|null $Pricelist Price list
 * @property string|null $PricelistDescription Description of Pricelist
 * @property string|null $Project The project to which the sales transaction line is linked. The project can be different per line. Sometimes also the project in the header is filled although this is not really used
 * @property string|null $ProjectDescription Description of Project
 * @property string|null $ProjectWBS WBS linked to the sales invoice
 * @property string|null $ProjectWBSDescription Description of WBS
 * @property float|null $Quantity The number of items sold in default units. The quantity shown in the entry screen is Quantity * UnitFactor
 * @property string|null $SalesOrder Identifies the sales order this invoice line is based on
 * @property string|null $SalesOrderLine Identifies the sales order line this sales invoice line is based on
 * @property int|null $SalesOrderLineNumber Then line number of the sales order line on which this invoice line is based on
 * @property int|null $SalesOrderNumber The order number of the sales order on which this invoice line is based on
 * @property string|null $StartTime StartTime is used to store the first date of a period. StartTime is used in combination with EndTime
 * @property string|null $Subscription When generating invoices from subscriptions, this field records the link between invoice lines and subscription lines
 * @property string|null $SubscriptionDescription Description of subscription line
 * @property string|null $UnitCode Code of Unit
 * @property string|null $UnitDescription Description of Unit
 * @property float|null $UnitPrice Price per unit
 * @property float|null $VATAmountDC VAT amount in the default currency of the company
 * @property float|null $VATAmountFC VAT amount in the currency of the transaction
 * @property string|null $VATCode The VAT code that is used when the invoice is registered
 * @property string|null $VATCodeDescription Description of VATCode
 * @property float|null $VATPercentage The vat percentage of the VAT code. This is the percentage at the moment the invoice is created. It's also used for the default calculation of VAT amounts and VAT base amounts
 */
class SalesInvoiceLine extends Model {}

/**
 * @property string|null $OrderID Primary key
 * @property float|null $AmountDC Amount in the default currency of the company
 * @property float|null $AmountDiscount Discount amount in the default currency of the company
 * @property float|null $AmountDiscountExclVat Discount amount excluding VAT in the default currency of the company
 * @property float|null $AmountFC Amount in the currency of the transaction
 * @property float|null $AmountFCExclVat Amount exclude VAT in the currency of the transaction
 * @property int|null $ApprovalStatus Approval status of sales order. 0=Awaiting approval, 1=Automatically, 2=Approved. Approve a new sales order by giving value 2 if user has SalesOrderApproval right.
 * @property string|null $ApprovalStatusDescription Description of ApprovalStatus
 * @property string|null $Approved Approval datetime
 * @property string|null $Approver User who approved the sales order
 * @property string|null $ApproverFullName Name of approver
 * @property string|null $Created Creation date
 * @property string|null $Creator User ID of creator
 * @property string|null $CreatorFullName Name of creator
 * @property string|null $Currency Currency code
 * @property string|null $CustomField Custom field endpoint. Provided only for the Exact Online Premium users.
 * @property string|null $DeliverTo Reference to the delivery customer. For an existing sales order this value can not be changed.
 * @property string|null $DeliverToContactPerson Reference to contact person of delivery customer
 * @property string|null $DeliverToContactPersonFullName Name of contact person of delivery customer
 * @property string|null $DeliverToName Name of delivery customer
 * @property string|null $DeliveryAddress Delivery address
 * @property string|null $DeliveryDate Delivery date
 * @property int|null $DeliveryStatus Shipping status
 * @property string|null $DeliveryStatusDescription Description of DeliveryStatus
 * @property string|null $Description Description
 * @property float|null $Discount Discount percentage
 * @property int|null $Division Division code
 * @property string|null $Document Document that is manually linked to the sales order
 * @property int|null $DocumentNumber Number of the document
 * @property string|null $DocumentSubject Subject of the document
 * @property string|null $IncotermAddress Address of Incoterm
 * @property string|null $IncotermCode Code of Incoterm
 * @property int|null $IncotermVersion Version of Incoterm Supported version for Incoterms : 2010, 2020
 * @property int|null $InvoiceStatus Invoice status
 * @property string|null $InvoiceStatusDescription Description of InvoiceStatus
 * @property string|null $InvoiceTo Reference to the customer who will receive the invoice. For an existing sales order this value can not be changed.
 * @property string|null $InvoiceToContactPerson Reference to the contact person of the customer who will receive the invoice
 * @property string|null $InvoiceToContactPersonFullName Name of the contact person of the customer who will receive the invoice
 * @property string|null $InvoiceToName Name of the customer who will receive the invoice
 * @property string|null $Modified Last modified date
 * @property string|null $Modifier User ID of modifier
 * @property string|null $ModifierFullName Name of modifier
 * @property string|null $OrderDate Order date
 * @property string|null $OrderedBy Customer who ordered the sales order. For an existing sales order this value can not be changed.
 * @property string|null $OrderedByContactPerson Contact person of the customer who ordered the sales order
 * @property string|null $OrderedByContactPersonFullName Name of contact person of the customer who ordered the sales order
 * @property string|null $OrderedByName Name of the customer who ordered the sales order
 * @property int|null $OrderNumber Number of sales order
 * @property string|null $PaymentCondition The payment condition used for due date and discount calculation
 * @property string|null $PaymentConditionDescription Description of PaymentCondition
 * @property string|null $PaymentReference Payment reference for sales order
 * @property string|null $Remarks Extra remarks
 * @property string|null $SalesChannel ID of Sales channel.
 * @property string|null $SalesChannelCode Code of Sales channel
 * @property string|null $SalesChannelDescription Description of Sales channel
 * @property mixed $SalesOrderLines Collection of lines
 * @property SalesOrderOrderChargeLine[] $SalesOrderOrderChargeLines Collection of order charge lines
 * @property string|null $Salesperson Sales representative
 * @property string|null $SalespersonFullName Name of sales representative
 * @property string|null $SelectionCode ID of selection code. Only supported by the Plus, Professional and Premium for Wholesale & Distribution and Manufacturing
 * @property string|null $SelectionCodeCode Code of selection code
 * @property string|null $SelectionCodeDescription Description of selection code
 * @property string|null $ShippingMethod ShippingMethod
 * @property string|null $ShippingMethodDescription Description of ShippingMethod
 * @property int|null $Status The status of the sales order. 12 = Open, 20 = Partial, 21 = Complete, 45 = Cancelled.
 * @property string|null $StatusDescription Description of Status
 * @property string|null $WarehouseCode Code of Warehouse
 * @property string|null $WarehouseDescription Description of Warehouse
 * @property string|null $WarehouseID Warehouse. Only supported by the Plus, Professional and Premium editions for Wholesale & Distribution and Manufacturing
 * @property string|null $YourRef The reference number of the customer
 */
class SalesOrder extends Model {}

/**
 * @property string|null $ID Primary key
 * @property float|null $AmountDC Amount in the default currency of the company
 * @property float|null $AmountFC Amount in the currency of the transaction
 * @property string|null $CostCenter Reference to Cost center
 * @property string|null $CostCenterDescription Description of CostCenter
 * @property float|null $CostPriceFC Item cost price
 * @property string|null $CostUnit Reference to Cost unit
 * @property string|null $CostUnitDescription Description of CostUnit
 * @property string|null $CustomerItemCode Code the customer uses for this item
 * @property string|null $CustomField Custom field endpoint. Provided only for the Exact Online Premium users.
 * @property string|null $DeliveryDate Delivery date of this line
 * @property int|null $DeliveryStatus Shipping status of the sales order line. 12=Open, 20=Partial, 21=Complete, 45=Cancelled
 * @property string|null $Description Description
 * @property float|null $Discount Discount given on the default price. Discount = (DefaultPrice of Item - PriceItem in line) / DefaultPrice of Item
 * @property int|null $Division Division code
 * @property int|null $InvoiceStatus Invoice status of the sales order line. 12=Open, 20=Partial, 21=Complete, 45=Cancelled
 * @property string|null $Item Reference to the item that is sold in this sales order line
 * @property string|null $ItemCode Code of Item
 * @property string|null $ItemDescription Description of Item
 * @property string|null $ItemVersion Item Version
 * @property string|null $ItemVersionDescription Description of Item Version
 * @property int|null $LineNumber Line number
 * @property float|null $Margin Sales margin of the sales order line
 * @property float|null $NetPrice Net price of the sales order line
 * @property string|null $Notes Extra notes
 * @property string|null $OrderID The OrderID identifies the sales order. All the lines of a sales order have the same OrderID
 * @property int|null $OrderNumber Number of sales order
 * @property int|null $OrderStatus The status of the sales order line. 12=Open, 20=Partial, 21=Complete, 45=Cancelled
 * @property string|null $Pricelist Price list
 * @property string|null $PricelistDescription Description of Pricelist
 * @property string|null $Project The project to which the sales order line is linked. The project can be different per line. Sometimes also the project in the header is filled although this is not really used
 * @property string|null $ProjectDescription Description of Project
 * @property string|null $PurchaseOrder Purchase order that is linked to the sales order
 * @property string|null $PurchaseOrderLine Purchase order line of the purchase order that is linked to the sales order
 * @property int|null $PurchaseOrderLineNumber Number of the purchase order line
 * @property int|null $PurchaseOrderNumber Number of the purchase order
 * @property float|null $Quantity The number of items sold in default units. The quantity shown in the entry screen is Quantity * UnitFactor.Positive quantity = Sales order lines, Negative quantity = Trade-in lines.
 * @property float|null $QuantityDelivered The number of items delivered
 * @property float|null $QuantityInvoiced The number of items invoiced
 * @property string|null $ShopOrder Reference to ShopOrder
 * @property string|null $UnitCode Code of item unit
 * @property string|null $UnitDescription Description of Unit
 * @property float|null $UnitPrice Price per unit in the currency of the transaction
 * @property int|null $UseDropShipment Indicates if drop shipment is used (delivery directly to customer, invoice to wholesaler)
 * @property float|null $VATAmount VAT amount in the currency of the transaction
 * @property string|null $VATCode VAT code
 * @property string|null $VATCodeDescription Description of VATCode
 * @property float|null $VATPercentage The vat percentage of the VAT code. This is the percentage at the moment the sales order is created. It's also used for the default calculation of VAT amounts and VAT base amounts
 */
class SalesOrderLine extends Model {}

/**
 * @property string|null $ID Primary key
 * @property string|null $Account Tax account
 * @property string|null $AccountCode Code of Account
 * @property string|null $AccountName Name of Account
 * @property string|null $CalculationBasis Indicates how to calculate the tax. 0 = based on the gross amount, 1 = based on the gross amount + another tax
 * @property string|null $Charged Indicates if transactions using the VAT code are transactions of the domestic VAT charging regulation (such as those for subcontractors) or transactions that are registered within the EU. If Charged=1 and linked to a purchase invoice, both a line for the VAT to pay and a line for the VAT to claim are being created
 * @property string|null $Code VAT code
 * @property string|null $Created Creation date
 * @property string|null $Creator User ID of creator
 * @property string|null $CreatorFullName Name of creator
 * @property string|null $CustomField Custom field endpoint
 * @property string|null $Description Description of the VAT code
 * @property int|null $Division Division code
 * @property string|null $EUSalesListing Used in all legislations except France. Indicates if and how transactions using the VAT code appear on the ICT return (EU sales list). L = Listing goods, N = No listing, S = Listing services, T = Triangulation
 * @property int|null $ExcludeVATListing ExcludeVATListing. Used in Belgium Legislation to indicate whether the entries need to be excluded from the VAT Listing.
 * @property string|null $GLDiscountPurchase Indicates the purchase discount GL account linked to the VAT codes for German legislation
 * @property string|null $GLDiscountPurchaseCode Code of the G/L account used for VAT corrections of settlement discount purchase (Germany only)
 * @property string|null $GLDiscountPurchaseDescription Description of the G/L account used for VAT corrections of settlement discount purchase (Germany only)
 * @property string|null $GLDiscountSales Indicates the sales discount GL account linked to the VAT codes for German legislation
 * @property string|null $GLDiscountSalesCode Code of the G/L account used for VAT corrections of settlement discount sales (Germany only)
 * @property string|null $GLDiscountSalesDescription Description of the G/L account used for VAT corrections of settlement discount sales (Germany only)
 * @property string|null $GLToClaim G/L account that is used to book the VAT to claim. If you enter purchases with a VAT code, the VAT amount to be claimed is entered to this VAT account. Must be of type VAT
 * @property string|null $GLToClaimCode Code of the VAT to claim G/L account for the VAT code
 * @property string|null $GLToClaimDescription Description of the VAT to claim G/L account for the VAT code
 * @property string|null $GLToPay G/L account that is used to book the VAT to pay. If you enter sales with a VAT code, the VAT amount to be paid is entered to this VAT account. Must be of type VAT
 * @property string|null $GLToPayCode Code of the VAT to pay G/L account for the VAT code
 * @property string|null $GLToPayDescription Description of the VAT to pay G/L account for the VAT code
 * @property bool|null $IntraStat Used in all legislations except France. Indicates if intrastat is used
 * @property string|null $IntrastatType Used in France legislation only. Indicates if and how transactions using the VAT code appear on the DEB/DES return. L = Goods, N = Empty, S = Services
 * @property bool|null $IsBlocked Indicates if the VAT code may still be used
 * @property string|null $LegalText Legal description for VAT code to print in the total block of the invoice
 * @property string|null $Modified Last modified date
 * @property string|null $Modifier User ID of modifier
 * @property string|null $ModifierFullName User name of modifier
 * @property string|null $OssCountry OSS country. Available when OneStopShop featureset is enabled in the administration.
 * @property float|null $Percentage Percentage of the VAT code
 * @property int|null $TaxReturnType Indicates what type of Taxcode it is: can be VAT, IncomeTax
 * @property string|null $Type Indicates how the VAT amount should be calculated in relation to the invoice amount. B = VAT 0% (Only base amount), E = Excluding, I = Including, N = No VAT
 * @property string|null $VatDocType Field in VAT code maintenance to calculate different VATs depending on the selected document type. P = purchase invoice, F = freelance invoice, E = expense voucher. The field is valid for witholding tax type
 * @property int|null $VatMargin The VAT margin scheme is used for the trade of secondhand goods which are purchased without VAT (for example when a company buys a secondhand good from a private person). In the VAT margin scheme, the VAT is not calculated based on the sales price. Instead of that, the VAT is calculated based on the margin (gross sales price minus the gross purchase price)
 * @property int|null $VATPartialRatio Partial ratio explains which part of the VAT the company has to pay. Used in some branches where the sellers have a bad reputation, so the buyers have to take over the VAT-liability
 * @property VatPercentage[] $VATPercentages VAT percentages. You can have several VAT percentages, with start and end dates
 * @property string|null $Country Country code for the VAT code
 * @property string|null $VATDocType Document type for the VAT (alias for VatDocType as used in action code)
 * @property string|null $VATMargin VAT margin scheme indicator (alias for VatMargin as used in action code)
 * @property string|null $VATTransactionType Indicates the type of transactions for which the VAT code may be used. B = Both, P = Purchase, S = Sales
 */
class VatCode extends Model {}

/**
 * @property string|null $ID A guid that is the unique identifier of the warehouse
 * @property string|null $Code Code of the warehouse
 * @property string|null $Created Creation date
 * @property string|null $Creator User ID of creator
 * @property string|null $CreatorFullName Name of creator
 * @property string|null $DefaultStorageLocation The default storage location of this warehouse. Warehouses can have a default storage location in packages Manufacturing Professional & Premium or Wholesale Professional & Premium
 * @property string|null $DefaultStorageLocationCode Default storage location's code
 * @property string|null $DefaultStorageLocationDescription Default storage location's description
 * @property string|null $Description The description of the warehouse
 * @property int|null $Division Division code
 * @property string|null $EMail Email address
 * @property bool|null $Main Indicates if this is the main warehouse. There's always exactly one main warehouse per administration
 * @property string|null $ManagerUser User reponsible for the warehouse
 * @property string|null $Modified Last modified date
 * @property string|null $Modifier User ID of modifier
 * @property string|null $ModifierFullName Name of modifier
 * @property int|null $UseStorageLocations Indicates if this warehouse is using storage locations. The storage locations will not be removed when when this is deactivated
 */
class Warehouse extends Model {}

/**
 * @property string|null $ID Primary key
 * @property string|null $CallbackURL Callback URL endpoint
 * @property string|null $ClientID OAuth client Id
 * @property string|null $Created Creation date
 * @property string|null $Creator User ID of creator
 * @property string|null $CreatorFullName Name of creator
 * @property string|null $Description Description of the OAuth Client
 * @property int|null $Division Division code
 * @property bool|null $IsInstant Enable instant delivery (only supported for topic 'GoodsDeliveries'). For any other topic, return an error.
 * @property string|null $Topic Webhook subscription topic, e.g.: Accounts, Items, StockPositions
 * @property string|null $UserID Subscribing User ID
 */
class WebhookSubscription extends Model {}

/**
 * @property int|null $Code Primary key
 * @property string|null $ArchiveDate Date on which the division is archived
 * @property int|null $BlockingStatus Values: 0 = Not blocked, 1 = Backup/restore, 2 = Conversion busy, 3 = Conversion shadow, 4 = Conversion waiting, 5 = Copy data waiting, 6 = Copy data busy
 * @property DivisionClass|null $Class_01 First division classification. User should have access rights to view division classifications.
 * @property DivisionClass|null $Class_02 Second division classification. User should have access rights to view division classifications.
 * @property DivisionClass|null $Class_03 Third division classification. User should have access rights to view division classifications.
 * @property DivisionClass|null $Class_04 Fourth division classification. User should have access rights to view division classifications.
 * @property DivisionClass|null $Class_05 Fifth division classification. User should have access rights to view division classifications.
 * @property string|null $Country Country of the division. Is used for determination of legislation
 * @property string|null $CountryDescription Description of Country
 * @property string|null $Created Creation date
 * @property string|null $Creator User ID of creator
 * @property string|null $CreatorFullName Name of the creator
 * @property string|null $Currency Default currency of the division
 * @property string|null $CurrencyDescription Description of Currency
 * @property string|null $Customer Owner account of the division
 * @property string|null $CustomerCode Owner account code of the division
 * @property string|null $CustomerName Owner account name of the division
 * @property string|null $Description Description
 * @property int|null $HID Number that customers give to the division
 * @property bool|null $Main True for the main (hosting) division
 * @property string|null $Modified Last modified date
 * @property string|null $Modifier User ID of modifier
 * @property string|null $ModifierFullName Name of the last modifier
 * @property string|null $OBNumber The soletrader VAT number used for offical returns to tax authority
 * @property string|null $SiretNumber Siret Number of the division (France)
 * @property string|null $StartDate Date on which the division becomes active
 * @property int|null $Status Regular administrations will have status 0. Currently, the only other possibility is 'archived' (1), which means the administration is not actively used, but still needs to be accessible for the customer/accountant to meet legal obligations
 * @property string|null $TaxOfficeNumber Number of your local tax authority (Germany)
 * @property string|null $TaxReferenceNumber Local tax reference number (Germany)
 * @property string|null $TemplateCode Division template code
 * @property string|null $VATNumber VAT number
 * @property string|null $Website Customer value, hyperlink to external website
 */
class Division extends Model {}

/**
 * @property string|null $UserID Primary key
 * @property int|null $AccountingDivision Accounting division number
 * @property int|null $CurrentDivision Division number that is currently used in the API. You should use a division number in the url
 * @property string|null $CustomerCode Account code of the logged in user.
 * @property string|null $DivisionCustomer Owner account of the division
 * @property string|null $DivisionCustomerCode Owner account code of the division
 * @property string|null $DivisionCustomerName Owner account name of the division
 * @property string|null $DivisionCustomerSiretNumber Owner account SIRET Number of the division for French legislation
 * @property string|null $DivisionCustomerVatNumber Owner account VAT Number of the division
 * @property int|null $DossierDivision Dossier division number (optional)
 * @property string|null $Email Email address of the user
 * @property string|null $EmployeeID Employee ID
 * @property string|null $FirstName First name
 * @property string|null $FullName Full name of the user
 * @property string|null $Gender Gender: M=Male, V=Female, O=Unknown
 * @property string|null $Initials Initials
 * @property bool|null $IsClientUser Client user of an accountant: either a portal user or a non-accountant user with his own license (internal use)
 * @property bool|null $IsEmployeeSelfServiceUser Employee user with limited access and specific start page
 * @property bool|null $IsMyFirmLiteUser MyFirm lite user of accountant with limited access and specific start page (internal use)
 * @property bool|null $IsMyFirmPortalUser MyFirm user of accountant with limited access and specific start page (internal use)
 * @property bool|null $IsOEIMigrationMandatory Determines whether one exact identity migration is mandatory for the user. True - User does have to migrate, False - User does not have to migrate
 * @property bool|null $IsStarterUser Starter user with limited access and specific start page (internal use)
 * @property string|null $Language Language spoken by this user
 * @property string|null $LanguageCode Language (culture) that is used in Exact Online
 * @property string|null $LastName Last name
 * @property int|null $Legislation Legislation
 * @property string|null $MiddleName Middle name
 * @property string|null $Mobile Mobile phone
 * @property string|null $Nationality Nationality
 * @property string|null $PackageCode Package code used in the customers license
 * @property string|null $Phone Phone number
 * @property string|null $PhoneExtension Phone number extension
 * @property string|null $PictureUrl Url that can be used to retrieve the picture of the user
 * @property string|null $ServerTime The current date and time in Exact Online
 * @property float|null $ServerUtcOffset The time difference with UTC in seconds
 * @property string|null $ThumbnailPicture Binary thumbnail picture of this user (This property will never return value and will be removed in the near future.)
 * @property string|null $ThumbnailPictureFormat File type of the picture (This property will never return value and will be removed in the near future.)
 * @property string|null $Title Title
 * @property string|null $UserName Login name of the user. If the user logs in with One Exact Identity, the login name is in the email address field
 */
class Me extends Model {}

/**
 * @method object getClient()
 */
class Connection
{
    /** @return array<int, array<string, mixed>> */
    public function get(string $url): array {}
}

// Collection sub-types referenced in @property annotations
class DeductibilityPercentage extends Model {}
class StockBatchNumber extends Model {}
class StockSerialNumber extends Model {}
class InvoiceTerm extends Model {}
class ProjectRestrictionEmployee extends Model {}
class ProjectRestrictionItem extends Model {}
class ProjectRestrictionRebilling extends Model {}
class ShopOrderMaterialPlan extends Model {}
class ShopOrderRoutingStepPlan extends Model {}
class QuotationOrderChargeLine extends Model {}
class SalesInvoiceOrderChargeLine extends Model {}
class SalesOrderOrderChargeLine extends Model {}
class VatPercentage extends Model {}
class DivisionClass extends Model {}

namespace Picqer\Financials\Exact\Query;

trait Findable
{
    /** @return static|null */
    public function find(mixed $id): mixed {}
}
