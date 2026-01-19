# WC REST Variable Product Restore Fix

Proof-of-concept plugin to fix an inconsistency in WooCommerce Core where **restoring a variable product via the REST API does not restore its variations**, unlike the Admin UI.
Issue: https://github.com/woocommerce/woocommerce/issues/62833


> ⚠️ This plugin is **not intended for production use**.  
> It exists only to validate and demonstrate the correct behavior before upstreaming the fix into WooCommerce Core.

---

## 🐞 Problem Summary

WooCommerce handles variable products differently depending on how they are restored:

### Admin UI behavior (correct)
- Trashing a variable product → variations are trashed
- Restoring the variable product → variations are restored automatically

### REST API behavior (bug)
- Trashing via `DELETE /products/{id}` → variations are trashed ✅
- Restoring via `PUT /products/{id}` with `status=publish|draft` →  
  ❌ parent is restored  
  ❌ variations remain in `trash`

This causes **data inconsistency**:
- REST API reports parent as `publish`
- Variations remain inaccessible and non-purchasable
- `_wp_trash_meta_*` remains set on variations

---

## 🔍 Root Cause

The REST API performs a **shallow status update** on the parent product and does **not trigger the internal untrash logic** (`wp_untrash_post`) that the Admin UI relies on.

As a result:
- Variations are not restored
- Core hooks responsible for cascading restore are skipped

---

## ✅ What This Plugin Does

This plugin hooks into:
woocommerce_rest_insert_product_object



When a variable product is updated via REST and its status changes from `trash` → `publish|draft|private`, it:

1. Detects the REST restore action
2. Iterates over child variations
3. Untrashes any variation still in `trash`
4. Syncs the variation status with the parent

This restores **parity between REST API and Admin UI behavior**.

---

## 🧪 How to Reproduce the Bug

1. Create a variable product with one or more variations
2. Ensure all are `publish`
3. `DELETE /wp-json/wc/v3/products/{id}` (without `force=true`)
   - Parent + variations move to `trash`
4. `PUT /wp-json/wc/v3/products/{id}` with:
   ```json
   { "status": "publish" }
5.Observe:
  Parent is publish
  Variations remain trash ❌

How to Verify the Fix

With this plugin active:
Repeat the steps above
  -After restoring the parent:
  -Variations are restored automatically
  -REST responses for variations return status: publish
  -Product becomes purchasable again
