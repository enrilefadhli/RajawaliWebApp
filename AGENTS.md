# AGENTS.md

## Codex Agent Behavior Specification + System Context

# 1. Agent Identity

You are Codex, assisting with a Retail Inventory Management System. (spec...)

# 2. System Context

This Codex agent works within a **Retail Inventory Management System** consisting of purchasing, sales, stock tracking, and master data management. Codex must always assume this fixed architecture and apply all rules exactly as described.

---

## 2.1 Core Domain Modules

The system contains 8 major modules:

1. **Users & Authentication**
2. **Master Data**
    - Products
    - Categories
    - Suppliers
3. **Stock System**
    - Stocks (real-time stock table)
    - Stock Adjustments (audit log)
    - Stock Opname (physical count)
    - Stock Opname Sessions
4. **Purchase Request (PR)**
5. **Purchase Order (PO)**
6. **Purchase Transactions**
7. **Sales Transactions**
8. **Restock Logic (system-calculated)**

---

### 2.2 High-Level Business Flows

#### **Purchase Flow**

1. Pegawai submits **Purchase Request (PR)**
2. Admin approves or rejects
3. If approved → **exactly 1 Purchase Order (PO)** is auto-generated
4. Purchase transaction is created
5. System increases stock based on purchased quantity
6. Purchase does **not** create stock_adjustment

#### **Sales Flow**

1. Pegawai creates sale
2. System checks stock availability
3. System reduces stock
4. Sale does **not** create stock_adjustment

#### **Stock Opname Flow**

1. Start opname session
2. For each product:
    - Read `stocks.quantity`
    - Input `stock_physical`
    - Calculate difference
3. If different:
    - System creates `stock_adjustments` entry
    - System updates `stocks.quantity = stock_physical`

#### **Manual Stock Update**

-   Always generates a `stock_adjustments` entry
-   Then updates the stock
-   Required for full audit trace

---

### 2.3 Database Rules

-   Products **never store stock** → stock lives in the `stocks` table
-   Minimum stock stored in `products.minimum_stock`
-   Restock logic is calculated by system:
    → no `needs_restock` field
-   All enum-like fields are stored as **string**
-   All timestamps use Laravel defaults
-   PR → PO relationship is always **1-to-1**

---

### 2.4 Relationship Summary

#### Users

-   1 → many Purchase Requests (requested)
-   1 → many Purchase Requests (approved)
-   1 → many Purchases
-   1 → many Sales
-   1 → many Stock Adjustments
-   1 → many Stock Opname Sessions

#### Master Data

-   Category 1 → many Products
-   Product 1 → 1 Stock
-   Product 1 → many Stock Adjustments
-   Product 1 → many PR Details
-   Product 1 → many PO Details
-   Product 1 → many Purchase Details
-   Product 1 → many Sale Details

#### Purchasing

-   PR 1 → many PR Details
-   PR 1 → 1 PO
-   PO 1 → many PO Details
-   Purchase 1 → many Purchase Details

#### Sales

-   Sale 1 → many Sale Details

#### Stock System

-   Opname Session 1 → many Opname records
-   Product 1 → many Opname records

---

### 2.5 System Constraints

#### Stock Integrity

-   Stock cannot go negative
-   Stock changes from:
-   Sales (decrease)
-   Purchases (increase)
-   Opname (override)
-   Manual adjustments (override only with audit entry)

#### Purchasing Integrity

-   PR must be approved before PO creation
-   PO must have at least one detail line
-   Purchase may optionally link to PO

#### Opname Integrity

-   Opname snapshot is historical and not overwritten
-   Differences _must_ generate stock_adjustments

#### Audit Trail

-   Manual updates must always log stock_adjustments
-   Purchases and sales never create adjustments

---

### 2.6 System Design Conventions

Codex must enforce:

-   Foreign key naming consistency:  
    `user_id`, `product_id`, `category_id`, `supplier_id`,  
    `purchase_request_id`, `purchase_order_id`, etc.
-   Use **string** instead of enums
-   No business logic inside controllers → use Services
-   All migrations must be reversible
-   Follow Laravel + Filament best practices
-   No duplicate fields or denormalized stock fields

---

### 2.7 Codex Responsibilities

Codex must generate:

-   Laravel migrations
-   Eloquent models with relationships
-   Filament resources
-   Controllers + routes
-   Service-layer logic
-   Stock update logic
-   PR → PO logic
-   Opname logic
-   Purchase and Sales logic
-   Validation rules
-   Optional bulk import logic

Codex must validate all generated code against the system constraints above.

---

# 3. Full System Knowledge

## Retail Inventory Management System — Medium Version

(This is the medium-length full system knowledge specification.)

## 1. System Overview

Retail IMS handles products, categories, suppliers, stock, purchasing, sales, stock opname, adjustments, and user roles.

## 2. User Roles

-   ADMIN: full access, approves PR, can adjust stock.
-   STAFF: create PR, purchases, sales, perform opname.

## 3. Database Schema (16 tables)

### users

-   id, username, password, name, phone, address?, role, timestamps

### categories

-   id, category_name, category_code, timestamps

### suppliers

-   id, supplier_name, supplier_phone, supplier_address?, timestamps

### products

-   id, category_id, product_code, product_name, purchase_price, selling_price, minimum_stock, description?, timestamps

### stocks

-   id, product_id, quantity, updated_by?, updated_at

### stock_adjustments

-   id, product_id, old_stock, new_stock, difference, reason, created_by, created_at

### stock_opname_sessions

-   id, created_by, opname_date, notes?

### stock_opname

-   id, session_id, product_id, stock_system, stock_physical, difference, created_at

### purchase_requests

-   id, requested_by_id, approved_by_id?, supplier_id, total_amount, request_note?, status, handled_note?, requested_at, handled_at?, timestamps

### purchase_request_details

-   id, purchase_request_id, product_id, quantity, timestamps

### purchase_orders

-   id, purchase_request_id, status, timestamps

### purchase_order_details

-   id, purchase_order_id, product_id, quantity, timestamps

### purchases

-   id, user_id, supplier_id, purchase_order_id?, total_amount, timestamps

### purchase_details

-   id, purchase_id, product_id, quantity, price, timestamps

### sales

-   id, user_id, total_amount, sale_date, notes?, timestamps

### sale_details

-   id, sale_id, product_id, quantity, price, timestamps

## 4. Relationships

-   Category 1..\* Products
-   Product 1..1 Stock
-   Product 1..\* Adjustments
-   Product 1..\* PR/PO/Purchase/Sale details
-   PR 1..\* PR Details
-   PR 1..1 PO
-   PO 1..\* PO Details
-   Purchase 1..\* Purchase Details
-   Sale 1..\* Sale Details
-   OpnameSession 1..\* Opname rows
-   User 1..\* PR, approvals, purchases, sales, adjustments, opname sessions

## 5. Business Logic

### Purchase Request

-   Created by staff
-   Approved/rejected by admin
-   Approval auto-creates PO

### Purchase Order

-   Auto-created from PR
-   Status flow: UNPROCESSED -> WAITING -> RECEIVED -> COMPLETED

### Purchase

-   Increases stock
-   No stock_adjustment generated

### Sales

-   Decreases stock
-   Prevent negative stock
-   No stock_adjustment generated

### Stock Opname

-   Compare physical vs system
-   If difference != 0: create stock_adjustment + update stock

### Manual Stock Update

-   Always generate stock_adjustment

### Restock Logic

-   Computed dynamically:
    stocks.quantity < products.minimum_stock

## 6. Service Layer

-   InventoryService: stock operations
-   PurchaseService: PR, approval, PO, purchase handling
-   SaleService: sales handling
-   OpnameService: opname operations

## 7. Constraints

-   No enums, use string
-   Stock cannot be stored in products table
-   All migrations reversible
-   All stock changes must be logged except purchases/sales
-   PR must be approved before PO
-   PO must have details

---
