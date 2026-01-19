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

