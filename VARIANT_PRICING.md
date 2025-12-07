# Variant Pricing System

## Overview
The product detail page now supports dynamic pricing based on variant selections. Each variant option can have its own price that will be displayed when selected.

## How It Works

### Database Schema
When inputting product variants in `product_input.php`:
- Each variant has: `category_name`, `option_name`, `option_price`, `image_path`
- Example:
  - Category: "Ukuran"
  - Option: "14cm", Price: 32000
  - Option: "15cm", Price: 35000

### Price Display Logic

1. **Initial Load**: Displays base product price
2. **When Variant Selected**: 
   - If variant has `option_price` > 0, displays that price
   - Otherwise, displays base product price
3. **Multiple Variants**: Uses the most recently selected variant's price

### Example Scenario

Product: "Tas Purunik" (Base Price: 200,000)
- Category "Ukuran":
  - 14cm: 32,000
  - 15cm: 35,000

Behavior:
- Page loads → Shows "Rp 200.000"
- User selects "15cm" → Price changes to "Rp 35.000"
- User selects "14cm" → Price changes to "Rp 32.000"

### Implementation Details

**File: Produk_detail/Produk_detail.js**
- `renderVariantCategory()`: Stores `option_price` in element's `dataset.optionPrice`
- `getSelectedVariant()`: Collects all selected variants and their prices
- `updateDisplayedPrice()`: Updates price display based on selected variants
- `wireVariantClickHandlers()`: Calls `updateDisplayedPrice()` on variant selection

**Supported Categories**:
- Works with ANY category name (Ukuran, Warna, Model, Motif, etc.)
- Pricing works independent of category type

### Frontend Integration

The pricing is automatically:
- Displayed in the product price element
- Used when adding to cart
- Used in checkout calculations
