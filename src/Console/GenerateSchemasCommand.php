<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateSchemasCommand extends Command
{
    protected $signature = 'exact:generate-schemas
                            {--entity= : Generate schema for a specific entity}
                            {--all : Generate schemas for all priority entities}
                            {--force : Overwrite existing schema files}';

    protected $description = 'Generate validation schemas from Exact Online API documentation';

    protected array $priorityEntities = [
        'CRMAccounts' => 'Account',
        'CRMContacts' => 'Contact',
        'LogisticsItems' => 'Item',
        'SalesOrderSalesOrders' => 'SalesOrder',
        'SalesOrderSalesOrderLines' => 'SalesOrderLine',
        'SalesInvoiceSalesInvoices' => 'SalesInvoice',
        'SalesInvoiceSalesInvoiceLines' => 'SalesInvoiceLine',
        'PurchaseOrderPurchaseOrders' => 'PurchaseOrder',
        'PurchaseOrderPurchaseOrderLines' => 'PurchaseOrderLine',
        'CRMQuotations' => 'Quotation',
        'CRMQuotationLines' => 'QuotationLine',
        'ProjectProjects' => 'Project',
        'FinancialGLAccounts' => 'GLAccount',
        'DocumentsDocuments' => 'Document',
        'CRMAddresses' => 'Address',
        'CRMBankAccounts' => 'BankAccount',
        'InventoryWarehouses' => 'Warehouse',
        'LogisticsItemGroups' => 'ItemGroup',
        'VATVATCodes' => 'VATCode',
        'FinancialJournals' => 'Journal',
        'PayrollEmployees' => 'Employee',
        'LogisticsUnits' => 'Unit',
        'CashflowPayments' => 'Payment',
        'SalesOrderGoodsDeliveries' => 'GoodsDelivery',
        'PurchaseOrderGoodsReceipts' => 'GoodsReceipt',
        'WebhooksWebhookSubscriptions' => 'WebhookSubscription',
    ];

    public function handle(): int
    {
        $schemasPath = dirname(__DIR__, 2).'/resources/schemas';

        if (! File::isDirectory($schemasPath)) {
            File::makeDirectory($schemasPath, 0755, true);
        }

        if ($entity = $this->option('entity')) {
            return $this->generateSingleSchema($entity, $schemasPath);
        }

        if ($this->option('all')) {
            return $this->generateAllSchemas($schemasPath);
        }

        $this->error('Please specify --entity=EntityName or --all');

        return 1;
    }

    protected function generateSingleSchema(string $entity, string $schemasPath): int
    {
        $this->info("Generating schema for: {$entity}");

        $docEntity = $this->findDocEntity($entity);
        if (! $docEntity) {
            $this->error("Unknown entity: {$entity}. Use one of: ".implode(', ', array_values($this->priorityEntities)));

            return 1;
        }

        $schemaFile = $schemasPath.'/'.$entity.'.json';
        if (File::exists($schemaFile) && ! $this->option('force')) {
            $this->warn("Schema already exists: {$schemaFile}. Use --force to overwrite.");

            return 0;
        }

        $schema = $this->fetchAndParseSchema($docEntity, $entity);
        if (! $schema) {
            $this->error("Failed to fetch schema for: {$entity}");

            return 1;
        }

        File::put($schemaFile, json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info("Generated: {$schemaFile}");

        return 0;
    }

    protected function generateAllSchemas(string $schemasPath): int
    {
        $this->info("Generating schemas for all priority entities...\n");
        $failed = [];

        foreach ($this->priorityEntities as $docEntity => $entity) {
            $schemaFile = $schemasPath.'/'.$entity.'.json';

            if (File::exists($schemaFile) && ! $this->option('force')) {
                $this->line("  <comment>Skipped</comment> {$entity} (already exists)");

                continue;
            }

            $this->line("  Fetching {$entity}...");

            $schema = $this->fetchAndParseSchema($docEntity, $entity);
            if (! $schema) {
                $this->error("  Failed: {$entity}");
                $failed[] = $entity;

                continue;
            }

            File::put($schemaFile, json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->info("  <info>Generated</info> {$entity} (".count($schema['fields']).' fields)');
        }

        $this->newLine();
        $this->info('Done! Generated '.(count($this->priorityEntities) - count($failed)).' schemas.');

        if (! empty($failed)) {
            $this->warn('Failed entities: '.implode(', ', $failed));

            return 1;
        }

        return 0;
    }

    protected function findDocEntity(string $entity): ?string
    {
        // First check if entity is a doc entity directly
        if (isset($this->priorityEntities[$entity])) {
            return $entity;
        }

        // Search by friendly name
        $key = array_search($entity, $this->priorityEntities, true);

        return $key !== false ? $key : null;
    }

    protected function fetchAndParseSchema(string $docEntity, string $entity): ?array
    {
        $url = "https://start.exactonline.nl/docs/HlpRestAPIResourcesDetails.aspx?name={$docEntity}";

        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (compatible; ExactSchemaGenerator/1.0)',
            ],
        ]);

        $html = @file_get_contents($url, false, $context);
        if ($html === false) {
            return null;
        }

        return $this->parseHtmlToSchema($html, $entity, $docEntity);
    }

    protected function parseHtmlToSchema(string $html, string $entity, string $docEntity): array
    {
        $schema = [
            'entity' => $entity,
            'endpoint' => $this->extractEndpoint($html, $docEntity),
            'fields' => [],
        ];

        // Parse field table - look for property definitions
        // The table typically has columns: Name, Type, Description
        preg_match_all(
            '/<tr[^>]*>.*?<td[^>]*>([^<]+)<\/td>.*?<td[^>]*>([^<]+)<\/td>.*?<td[^>]*>(.*?)<\/td>.*?<\/tr>/is',
            $html,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $name = trim(strip_tags($match[1]));
            $edmType = trim(strip_tags($match[2]));
            $description = trim(strip_tags($match[3]));

            // Skip header rows or invalid rows
            if (empty($name) || $name === 'Name' || strpos($edmType, 'Edm.') === false) {
                continue;
            }

            $field = $this->parseField($name, $edmType, $description);
            if ($field) {
                $schema['fields'][$name] = $field;
            }
        }

        // If regex didn't work well, try simpler patterns
        if (empty($schema['fields'])) {
            $schema['fields'] = $this->parseFieldsFallback($html);
        }

        return $schema;
    }

    protected function extractEndpoint(string $html, string $docEntity): string
    {
        // Try to extract endpoint from URL pattern in docs
        if (preg_match('/\/api\/v1\/\{division\}\/([^"\'<>\s]+)/i', $html, $match)) {
            return $match[1];
        }

        // Fallback: derive from doc entity name
        // CRMAccounts -> crm/Accounts
        if (preg_match('/^([A-Z][a-z]+)([A-Z].+)$/', $docEntity, $match)) {
            return strtolower($match[1]).'/'.$match[2];
        }

        return strtolower($docEntity);
    }

    protected function parseField(string $name, string $edmType, string $description): ?array
    {
        $type = $this->mapEdmType($edmType);

        $field = ['type' => $type];

        // Detect required fields from description
        if (stripos($description, 'required') !== false || stripos($description, 'mandatory') !== false) {
            $field['required'] = true;
        }

        // Detect read-only
        if (stripos($description, 'read-only') !== false ||
            stripos($description, 'readonly') !== false ||
            in_array($name, ['ID', 'Created', 'Creator', 'Modified', 'Modifier', 'Division'])) {
            $field['readOnly'] = true;
        }

        // Add format for email fields
        if ($type === 'string' && (stripos($name, 'email') !== false || stripos($name, 'e-mail') !== false)) {
            $field['format'] = 'email';
        }

        // Add format for URL/website fields
        if ($type === 'string' && (stripos($name, 'website') !== false || stripos($name, 'url') !== false)) {
            $field['format'] = 'url';
        }

        return $field;
    }

    protected function parseFieldsFallback(string $html): array
    {
        $fields = [];

        // Look for Edm type patterns anywhere
        preg_match_all('/\b([A-Z][a-zA-Z0-9]+)\s*(?::|=)\s*Edm\.([A-Za-z0-9]+)/i', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $name = $match[1];
            $edmType = 'Edm.'.$match[2];
            $fields[$name] = ['type' => $this->mapEdmType($edmType)];
        }

        return $fields;
    }

    protected function mapEdmType(string $edmType): string
    {
        return match (true) {
            str_contains($edmType, 'Guid') => 'guid',
            str_contains($edmType, 'String') => 'string',
            str_contains($edmType, 'Int16') => 'int16',
            str_contains($edmType, 'Int32') => 'int32',
            str_contains($edmType, 'Int64') => 'int64',
            str_contains($edmType, 'Double') => 'double',
            str_contains($edmType, 'Decimal') => 'decimal',
            str_contains($edmType, 'Boolean') => 'boolean',
            str_contains($edmType, 'Byte') => 'byte',
            str_contains($edmType, 'DateTime') => 'datetime',
            str_contains($edmType, 'Binary') => 'binary',
            str_contains($edmType, 'Collection') => 'collection',
            default => 'string',
        };
    }
}
