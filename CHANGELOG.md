# Changelog

All notable changes to `exactonline-laravel-api` will be documented in this file.

## v1.0.0 - Initial Release - 2026-01-11

### Features

- OAuth 2.0 authentication with automatic token refresh
- Entity sync: Accounts, Contacts, Items, Sales/Purchase Orders, Invoices, Quotations, Projects, and more
- Division management with automatic sync after OAuth
- Polymorphic mappings between Laravel models and Exact Online entities via `ExactMappable` trait
- Rate limit handling with automatic retry
- Payload validation before API calls
- Custom exception hierarchy (`ExactOnlineException`, `ApiException`, `AuthenticationException`, `SyncException`, `EntityNotFoundException`)
- Webhook support for real-time updates

### Supported Entities

Account, Contact, Item, Sales Order, Sales Invoice, Purchase Order, Purchase Invoice, Quotation, Project, GL Account, Division, Document, Address, Bank Account, Warehouse, Journal, VAT Code, Employee, Item Group, Unit, Payment
