![magebit (1)](https://github.com/user-attachments/assets/cdc904ce-e839-40a0-a86f-792f7ab7961f)

# Magebit_KlaviyoSubscription

A Magento 2 companion module for the official [Klaviyo extension](https://github.com/klaviyo/magento2-extension)
(`klaviyo/magento2-extension` 4.4.x). It adds SMS marketing consent capture across the storefront,
sends the store view language to Klaviyo profiles and events, consent-gates the Klaviyo onsite
script, and fixes a few gaps in the official extension's checkout and cart handling.

Built for Hyvä themes with Magewire-based checkout, with graceful fallbacks where Hyvä is not used.

## Requirements

| Requirement | Version |
|---|---|
| PHP | >= 8.1 |
| `klaviyo/magento2-extension` | 4.4.* |
| Hyvä theme (suggested) | `hyva-themes/magento2-theme-module` |

## Installation

```bash
composer require magebitcom/module-klaviyo-subscription
bin/magento module:enable Magebit_KlaviyoSubscription
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

`setup:upgrade` adds two boolean columns to the `quote` table (`general_subscription`,
`sms_subscription`) used to persist checkout consent until the order is placed.

## Features

### SMS subscription consent

Adds an SMS marketing opt-in alongside Magento's newsletter opt-in and pushes it to the Klaviyo
SMS list configured under the official extension's *Consent at Checkout* settings.

Opt-in points:

* **Checkout** — a Magewire component (`SubscriptionCheckboxes`) renders newsletter and SMS
  checkboxes in Hyvä checkout. The SMS checkbox appears only when the entered billing/shipping
  telephone is valid for the configured regions; it re-evaluates on every address save. Choices are
  stored on the quote and applied when the order is placed.
* **Account registration** — an SMS checkbox and an international phone input are added to the
  registration form; consent is submitted to Klaviyo after the account is created.
* **Customer dashboard** — the *Newsletter Subscriptions* page gains an SMS toggle that subscribes
  or unsubscribes the profile in Klaviyo, with the current state read back from the Klaviyo profile.
* **Admin customer form** — the native newsletter tab is replaced by a *Newsletter Subscription*
  toggle in the customer fieldset, and SMS subscription state is exposed and saved from the same form.

Subscription state is carried through the API layer as extension attributes: `is_sms_subscribed` on
`CustomerInterface`, and `general_subscription` / `sms_subscription` on `CartInterface`.

### Phone number validation for Klaviyo SMS regions

`Magebit\KlaviyoSubscription\Api\SmsPhoneValidationInterface` validates a raw phone number against
the countries Klaviyo supports for SMS and normalises it to E.164 (`+…`) for the API call. Regions
are admin-configurable per store view, so the checkbox is shown only to customers Klaviyo can
actually message. Supported matchers cover the NANP (US/CA) plus GB, AU, NZ, AT, BE, DK, FI, FR, DE,
HU, IE, IT, LU, NL, NO, PL, PT, ES, SE and CH — including common local `0`-prefixed forms for
GB/AU/NZ.

### Store view language on Klaviyo profiles

Every store view's locale (`general/locale/code`) is resolved into two values and sent to Klaviyo so
flows and campaigns can branch on language:

* `locale` — Klaviyo's native profile attribute (BCP 47, e.g. `lv-LV`)
* `Language` — a custom profile property holding the short code (e.g. `lv`), which flow splits read

Language is sent on:

* **Account creation** — hooked on `AccountManagementInterface::createAccount`, so storefront,
  checkout, social login and REST registrations are all covered.
* **Newsletter subscribe** — rides `newsletter_subscriber_save_commit_after` with the same guards as
  the official extension, using the store from the subscriber row (so admin, import and cron
  subscribes are attributed correctly).
* **Klaviyo events** — added to queued event user properties, which is the only way to reach guest
  profiles identified solely by `$exchange_id`.
* **Onsite tracking** — included in the `klaviyo.identify()` payload for the browsed store view.

Sync failures are logged and swallowed — a Klaviyo outage never fails a registration or subscribe.
Profile writes are de-duplicated per email/locale pair within a request.

#### Backfill command

```bash
bin/magento magebit:klaviyo:backfill-profile-language [--dry-run] [--batch-size=1000] [--store=1]
```

Sends the store view language for every existing customer and newsletter subscriber via Klaviyo's
bulk import jobs (max 10,000 profiles per job), grouped by store. Without it, profiles created
before this module shipped keep failing language splits.

### GDPR consent gating for klaviyo.js

The Klaviyo onsite script sets the `__kla_id` cookie, so it is loaded only after the visitor accepts
the cookie group that contains it. The queue stub is still installed immediately, so nothing is lost;
the script is fetched on `DOMContentLoaded` or later on `user-allowed-save-cookie`. When cookie
restriction mode is off, it loads normally.

### Cart merge on Klaviyo links

Klaviyo abandoned-cart links point at a specific quote. The module overrides the official
`Klaviyo\Reclaim\Controller\Checkout\Cart` controller and adds a `magebitReclaim` route with a
confirmation modal on the cart page, so a logged-in customer who already has a cart is asked whether
to merge the Klaviyo cart into it instead of silently losing either one.

### Extensions of the official extension

* `Helper\Data` and `KlaviyoV3Sdk\KlaviyoV3Api` are overridden via DI preferences to add SMS
  subscribe/unsubscribe and profile-lookup-by-email calls.
* `Model\Api\ProfileClient` implements the two profile endpoints the bundled SDK does not expose
  (`/api/profile-import` and `/api/profile-bulk-import-jobs`), reusing the installed extension's API
  revision constant.
* `Observer\SaveOrderMarketingConsent` replaces the official observer so checkout consent uses the
  quote's stored checkboxes and the validated international phone number.

## Configuration

**Stores → Configuration → Magebit → Klaviyo → SMS phone validation** (website/store view scope):

| Field | Path | Description |
|---|---|---|
| Sms subscription enabled | `magebit_klaviyo/sms_validation/sms_subscription_enabled` | Master switch for the SMS opt-in UI. |
| Allowed countries | `magebit_klaviyo/sms_validation/countries` | ISO 3166-1 alpha-2 regions accepted when validating phone numbers. Defaults to `US,CA`. Keep aligned with [Klaviyo SMS availability](https://help.klaviyo.com/hc/en-us/articles/4402914866843). |

Access is guarded by the `Magebit_KlaviyoSubscription::config` ACL resource.

The Klaviyo API keys, the *Consent at Checkout* email/SMS toggles and the target list IDs are read
from the official Klaviyo extension's own configuration.

*Developed by Magebit. Have questions or need help? Contact us at info@magebit.com or on our [website](https://magebit.com/contact).*
