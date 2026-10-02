# Plan: Customer Order Notes/Modifiers

## Current State

| Component | Status |
|-----------|--------|
| SaleItem model | Has no modifiers/notes fields |
| POS cart | Simple cart (product + quantity only) |
| Customer QR Order | Simple cart (product + quantity only) |
| Product model | Has ingredients (BOM) but no modifiers |

## Implementation Plan

### Option A: Simple Approach (Notes Only)
Quick to implement, customer can add free-text notes to each item.

### Option B: Full Modifiers System (Recommended)
Product modifiers like "Extra Cheese (+5k)", "Spicy Level: 1/2/3", etc.

---

## Recommended: Option B - Full Modifiers System

### Step 1: Database Migration
Create `product_modifiers` table:
- `id`, `product_id`, `name`, `type` (addon/option), `price_adjustment`, `is_active`, timestamps

Create `sale_item_modifiers` pivot table:
- `id`, `sale_item_id`, `product_modifier_id`, `price`, timestamps

Add `notes` column to `sale_items`:
- Free-text notes per item

### Step 2: Update Models
- `Product` - Add `modifiers()` relationship
- `SaleItem` - Add `notes`, `modifiers()` relationship, update casts

### Step 3: Product Resource Update
Add modifier management to Product resource (inline in product form or separate section)

### Step 4: POS Page Update
- Modify cart structure to include `modifiers` array and `notes`
- Add "Add Modifier" modal when clicking on cart item
- Add "Notes" text input for item
- Recalculate totals when modifiers change

### Step 5: Customer QR Order Update
- Add modifier selection UI in product card
- Add notes text input
- Update cart JavaScript to include modifiers
- Update controller to save modifiers

### Step 6: Receipt/Report Update
- Display modifiers and notes on receipt
- Include in sales reports

---

## Files to Create

### Migration
```
database/migrations/YYYY_MM_DD_create_product_modifiers_table.php
database/migrations/YYYY_MM_DD_create_sale_item_modifiers_table.php
database/migrations/YYYY_MM_DD_add_notes_to_sale_items_table.php
```

---

## Files to Modify

### Models
- `app/Models/Product.php` - Add modifiers relationship
- `app/Models/SaleItem.php` - Add notes field, modifiers relationship

### POS
- `app/Filament/Pages/POS.php` - Update cart, add modifier UI

### Customer Order
- `resources/views/customer/order.blade.php` - Add modifier UI
- `app/Http/Controllers/CustomerOrderController.php` - Save modifiers

### Product Resource
- `app/Filament/Resources/Products/Schemas/ProductForm.php` - Add modifier management

---

## Modifier Types

| Type | Description | Example |
|------|-------------|---------|
| `addon` | Add-on item (can select multiple) | Extra Cheese +5k |
| `option` | Single choice (radio) | Spicy Level: 1/2/3 |
| `text` | Free text input | Special instructions |

---

## Cart Item Structure (New)

```php
$this->cart[$productId] = [
    'product_id' => $productId,
    'product_name' => $product->name,
    'sku' => $product->sku,
    'unit_price' => $sellingPrice,
    'quantity' => 1,
    'subtotal' => $sellingPrice,
    'total' => $sellingPrice,
    'is_duration' => $isDuration,
    'rate_type' => $product->rate_type,
    'rate' => (float) $product->rate,
    'modifiers' => [                    // NEW
        ['id' => 1, 'name' => 'Extra Cheese', 'price' => 5000],
    ],
    'notes' => '',                      // NEW
];
```

---

## Rollback Plan
1. Rollback migration
2. Revert model changes
3. Remove UI components

---

## Questions to Clarify

1. Should modifiers be per-product or global?
   - Per-product: Each product has its own modifiers
   - Global: Same modifiers available for multiple products

2. Should modifiers affect inventory (like ingredients)?
   - Yes: Link modifier ingredients
   - No: Just price adjustment

3. Which approach to use?
   - A: Simple notes only
   - B: Full modifiers system
