# adex_maditel — Purchase API Reference

Request/response structures for the four purchase endpoints, traced from the
actual controllers (`app/Http/Controllers/Purchase/*.php`). Routes live in
`routes/api.php` with **no route middleware** — each controller authenticates
manually via one of three caller channels:

| Channel | How it authenticates | Extra body field |
|---|---|---|
| Website | `Origin` header ∈ `ADEX_APP_KEY` (csv) | `token` (= user `adex_key`), `pin` if `settings.allow_pin=1` |
| App | `Authorization: <ADEX_DEVICE_KEY>` (exact) | `user_id` (= user `app_key`) |
| API | `Authorization: Token <apikey>` | `request-id` (unique per service table; becomes `transid`) |

Conventions:

- Guard/validation/auth/balance rejections → **HTTP 403** `{"status":"fail","message":"..."}`.
- Vend outcomes → **HTTP 200** with `status`: `success` | `process` (money stays
  debited, result later) | `fail` (refunded; `newbal` = post-refund balance).
- `transid`: website/app = `uniqid("AIRTIME_"| "DATA_"|"BILL_"|"CABLE_")`; API = caller's `request-id`.
- DB `plan_status`: 0 = pending, 1 = success, 2 = failed/refunded.
- `network` / `data_plan` / `disco` / `cable` / `cable_plan` ids are rows in the
  child's own tables (`network`, `data_plan`, `bill_plan`, `cable_id`,
  `cable_plan`) keyed by `plan_id` — not hardcoded constants.

---

## 1. Airtime — `POST /api/topup`

`AirtimePurchase@BuyAirtime` (`app/Http/Controllers/Purchase/AirtimePurchase.php:15`)

Request:

```json
{
  "network": 1,
  "phone": "08031234567",
  "amount": 100,
  "plan_type": "vtu",
  "bypass": true,
  "request-id": "myref-001"
}
```

- `phone`: numeric, exactly 11 digits. `plan_type`: `vtu` | `sns`. `amount`: integer > 0.
- `bypass: true` skips the network-prefix check on the phone number.

Success (200):

```json
{
  "network": "MTN",
  "request-id": "AIRTIME_66b0f2a1c3d4e",
  "amount": 100,
  "transid": "AIRTIME_66b0f2a1c3d4e",
  "discount": 2,
  "status": "success",
  "message": "successfully purchase MTN VTU to 08031234567 , ₦100",
  "phone_number": "08031234567",
  "oldbal": 5000,
  "newbal": 4902,
  "system": "...",
  "plan_type": "VTU",
  "wallet_vending": "wallet"
}
```

`process` / `fail` (200): same shape; `status` differs; on `fail`, `newbal` is the refunded balance.

Errors (403, `{"status":"fail","message":...}`): `Insufficient Account Kindly
Fund Your Wallet => ₦<bal>` · `Invalid Network ID` · `Invalid Network Plan
Type` · `This is not a MTN Number => <phone>` · `Transaction Plan Id Exits` ·
`Invalid Transaction Pin` · `Invalid Access Token` · `Authorization Header
Token Required` · `Number Block` · `You have Reach Daily Transaction Limit...`
· `Maximum/Minimum Airtime Purchase...` · `<NETWORK> <TYPE> is not available right now`.

---

## 2. Data — `POST /api/data`

`DataPurchase@BuyData` (`app/Http/Controllers/Purchase/DataPurchase.php:14`)

Request:

```json
{
  "network": 1,
  "phone": "08031234567",
  "data_plan": 27,
  "bypass": true,
  "request-id": "myref-002"
}
```

- Plan resolved by `data_plan.plan_id` + network + `plan_status=1`. Price charged
  is the plan column for the user's tier (`smart|agent|awuf|api|special`).
- Plan type (`SME` / `GIFTING` / `COOPERATE GIFTING`) comes from the plan row and
  decides which sub-wallet is debited (`wallet_vending`).

Success (200):

```json
{
  "network": "MTN",
  "request-id": "DATA_66b0f3b2d4e5f",
  "amount": 250,
  "dataplan": "1GB",
  "status": "success",
  "transid": "DATA_66b0f3b2d4e5f",
  "message": "network-specific gifting text",
  "phone_number": "08031234567",
  "oldbal": 5000,
  "newbal": 4750,
  "system": "...",
  "plan_type": "SME",
  "wallet_vending": "wallet",
  "response": "raw provider response"
}
```

`process` / `fail` (200): same shape. API-channel users also receive a webhook
POST to `user.webhook`: `{"status": ..., "request-id": ..., "response": ...}`.

Errors (403): `Insufficient Account Kindly Fund Your <sub> Wallet => ₦<bal>` ·
`Invalid Data Plan ID and Network` · `Network ID invalid` · `Request ID Exits
Before` · `Phone Number Blocked` · `Invalid AccessToken Or Access Denial` ·
`AccessToken Required` · `<NETWORK> <TYPE> is not available right now` ·
`Unable To Debit User Account` · `Server Down Try Again Later`.

---

## 3. Electricity — `POST /api/bill`

`BillPurchase@Buy` (`app/Http/Controllers/Purchase/BillPurchase.php:14`)

Verify meter first — `GET /api/bill/bill-validation` (public):
request `{"disco": 3, "meter_number": "45123456789", "meter_type": "prepaid"}` →
200 `{"status":"success","name":"JOHN DOE"}`; 403 messages: `invalid Meter
Number` · `Invalid Disco Id` · `Invalid meter type` · `Meter type Required` ·
`Meter Number Required` · `Disco ID Required`.

Purchase request:

```json
{
  "disco": 3,
  "meter_number": "45123456789",
  "meter_type": "prepaid",
  "amount": 2000,
  "bypass": true,
  "request-id": "myref-003"
}
```

- `charges` = `bill_charge.bill` flat when `direct=1`, else percentage of amount.
  Total debit = amount + charges. Amount bounded by `bill_min`..`bill_max`.

Success (200):

```json
{
  "disco_name": "IKEDC",
  "request-id": "BILL_66b0f4c3e5f6a",
  "amount": 2000,
  "charges": 100,
  "transid": "BILL_66b0f4c3e5f6a",
  "status": "success",
  "message": "Transaction successful IKEDC PREPAID ₦2000 to 45123456789",
  "meter_number": "45123456789",
  "meter_type": "PREPAID",
  "oldbal": 5000,
  "newbal": 2900,
  "system": "...",
  "token": "1234-5678-9012-3456",
  "wallet_vending": "wallet"
}
```

`token` is the prepaid electricity token. `process` / `fail` (200): same shape
(quirk: the vendor "processing" string matched in code is the typo `proccess`).

Errors (403): `Insufficient Account Kindly Fund Your Wallet => ₦<bal>` ·
`Invalid Disco ID` · `Invalid Meter Number` · `Referrence ID Used` ·
`Electricity Bill Not Available Right Now` · `Maximum/Minimum Electricity
Purchase for this account is => ₦...` · `You have Reach Daily Transaction
Limit...` · `Unable to insert`.

---

## 4. Cable TV — `POST /api/cable`

`CablePurchase@BuyCable` (`app/Http/Controllers/Purchase/CablePurchase.php:15`)

Verify smartcard first — `GET /api/cable/cable-validation` (public):
request `{"cable": 1, "iuc": "1234567890"}` → 200
`{"status":"success","name":"JOHN DOE"}`; 403 messages: `Invalid IUC NUMBER` ·
`inavlid cable id` · `cable id required` · `iuc number required`.

Purchase request:

```json
{
  "cable": 1,
  "iuc": "1234567890",
  "cable_plan": 12,
  "bypass": true,
  "request-id": "myref-004"
}
```

- `cable` = provider row (DSTV/GOTV/STARTIMES); `cable_plan` must belong to that
  provider and be active. `charges` from `cable_charge` (flat or %), total
  debit = plan price + charges.

Success (200):

```json
{
  "cable_name": "GOTV",
  "request-id": "CABLE_66b0f5d4f6a7b",
  "amount": 3600,
  "charges": 100,
  "status": "success",
  "transid": "CABLE_66b0f5d4f6a7b",
  "message": "successfully purchase GOTV GOtv Max ₦3600 to 1234567890",
  "iuc": "1234567890",
  "oldbal": 5000,
  "newbal": 1300,
  "system": "...",
  "wallet_vending": "wallet",
  "plan_name": "GOtv Max"
}
```

⚠️ `process` / `fail` variants (200) use the **typo key `cabl_name`** instead of
`cable_name` — integrations must accept both.

Errors (403): `Insufficient Account Kindly Fund Your Wallet => ₦<bal>` ·
`Invalid Cable Plan ID` · `invalid cable plan id` · `Invalid IUC Number` ·
`Referrence ID Used` · `<CABLE> is not available right now` · `Invalid account number`.
