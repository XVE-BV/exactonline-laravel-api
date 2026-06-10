# Changelog

All notable changes to `exactonline-laravel-api` will be documented in this file.

## v3.2.0 - 2026-06-10

Feature: add `division_id` foreign key on `exact_connections` referencing `exact_divisions.id`, alongside the existing `division` code (which the Exact API still uses). Exposes an `activeDivision()` relationship. `division_id` is auto-populated wherever the active division is set: division sync, division switch, and OAuth connection setup. Additive and nullable; no behavior change to API calls.

## v3.1.0 - 2026-06-10

Feature: optional read-only MCP (Model Context Protocol) server, disabled by default. Exposes the integration's local state (connections, mappings, rate limits, divisions, webhooks) and live read-only Exact Online API calls to MCP clients over two transports: stdio (`exact:mcp` artisan command) and streamable-HTTP (`exact/mcp` route with static bearer-token auth). Built on the optional `laravel/mcp` dependency; OAuth tokens and secrets are never returned (scrubbed from all output).

## v3.0.1 - 2026-05-11

Fix: removed incorrect `readOnly` flag on `SalesInvoice.Status` in the schema. Exact Online's REST API accepts `Status` on create (e.g. `50` for Draft, `20` for Open), but the schema marked it read-only and the payload validator rejected create payloads that set it. Senders that need to control the initial status of a pushed invoice can now set it.

## v3.0.0 - 2026-05-08

Breaking: PHP namespace renamed from `Skylence\ExactonlineLaravelApi` to `XVE\ExactonlineLaravelApi` and composer package name from `skylence/exactonline-laravel-api` to `xve/exactonline-laravel-api`. No functional changes.

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
