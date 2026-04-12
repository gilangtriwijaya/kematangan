# Permintaan akses server-to-server untuk app_kematangan (OPD endpoints)

**Penting:** Jangan kirim token/secret lewat chat publik. Token/secret harus diprovision ke Vault/Secret Manager atau dikirim melalui channel aman ke admin server.

## Ringkasan
Tim Kematangan memerlukan akses server-to-server read-only agar aplikasi `kematangan` dapat menarik (pull) data OPD untuk mapping SSO→OPD lokal dan verifikasi akses pengguna (contoh: verifikator global).

---

## Endpoint & format (konfirmasi)
- Base URL: https://sso.example.id
- OPD paginasi: `GET https://sso.example.id/api/opds?per_page={n}&page={p}`
- Single OPD: `GET https://sso.example.id/api/opds/{opd_id}`
- (Opsional) User: `GET https://sso.example.id/api/users/{sso_user_id}`
- Params: `page`, `per_page`, `updated_after` (ISO8601)
- Pagination schema: `{"data":[...], "meta": {"page","per_page","total","last_page"}}`

**Sample OPD object (contoh nyata):**
```json
{
  "id": 9,
  "name": "Dinas X",
  "kode_opd": "DX-001",
  "alamat": "Jl. Contoh 1",
  "contact": "021-xxxxxxx",
  "parent_id": null,
  "updated_at": "2026-01-05T12:00:00Z"
}
```

**Sample paginated response:**
```json
{
  "data": [ /* OPD objects */ ],
  "meta": { "page": 1, "per_page": 200, "total": 1234, "last_page": 7 }
}
```

---

## Pagination & rate limits (konfirmasi)
- `max_per_page`: 500
- Rate limit: 100 requests/min per IP (burst 10 req/s)
- Rate-limit headers: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`
- On `429` returned header: `Retry-After` (seconds)

---

## Authentication & provisioning (pilih salah satu)
A) Bearer token (direkomendasikan):
- Scope minimal: `read:opds` (opsional: `read:users`)
- Header: `Authorization: Bearer <TOKEN>`
- Provisioning: simpan token di Vault path `secret/apps/kematangan/SSO_PULL_TOKEN`. Setelah dipasang, konfirmasi dengan Vault request ID atau sebutkan server hostname di channel aman.

B) HMAC (alternatif):
- Spec canonical string (konfirmasi): `<timestamp>.<path>.<body>`
- Algoritma: HMAC-SHA256
- Headers: `X-SSO-Timestamp`, `X-SSO-Signature: sha256=<hex>`
- Allowed skew: ±120s (konfirmasi)
- Provisioning: simpan secret di Vault path `secret/apps/kematangan/SSO_PULL_SECRET`.

C) OAuth2 client_credentials atau mTLS — sebutkan detail jika tersedia.

---

## Token lifecycle & rotation (konfirmasi)
- Test token validity: example 48h (konfirmasi actual)
- Prod tokens: beri kebijakan rotasi (contoh: 30 hari)
- Revoke: via ticket SSO ops atau Vault revoke

---

## IP allowlist & TLS
- Temporary allowlist: `203.0.113.5` (sudah ditambahkan untuk testing). Mohon kirim tambahan IP publik/CIDR untuk whitelist jika diperlukan.
- TLS: Public CA diterima (konfirmasi bila custom CA diperlukan).

---

## Test user & access
- `sso_user_id=42` (verifglobal@anambaskab.go.id) — dikonfirmasi memiliki akses ke `kematangan` dengan role `verifikator-global`.

---

## Error examples (konfirmasi format)
- 401 Unauthorized: `{"error":"invalid_token"}`
- 403 Forbidden: `{"error":"forbidden"}`
- 429 Too Many Requests: `{"error":"rate_limited"}` + header `Retry-After`

---

## Bulk/export
Jika server-to-server tidak memungkinkan, SSO dapat menyediakan signed CSV/NDJSON dump untuk initial import (`opd_sso_id,opd_name`).

---

## One-line tests (admin fetch token from Vault first)

**Bearer (copy-paste):**
```bash
curl -H "Accept: application/json" -H "Authorization: Bearer <FETCHED_TOKEN>" \
  "https://sso.example.id/api/opds?per_page=200&page=1"
```

**HMAC (example; confirm spec before use):**
```bash
ts=$(date +%s)
payload="${ts}./api/opds?per_page=200&page=1"
sig=$(printf '%s' "$payload" | openssl dgst -sha256 -hmac "<FETCHED_SECRET>" -binary | xxd -p -c 256)

curl -H "Accept: application/json" -H "X-SSO-Timestamp: ${ts}" -H "X-SSO-Signature: sha256=${sig}" \
  "https://sso.example.id/api/opds?per_page=200&page=1"
```

---

## Langkah kami setelah secret terpasang (kami akan jalankan di server aplikasi)
```bash
php artisan sso:pull-opds --apply --per_page=500
php artisan sso:fetch-map-opds --apply --threshold=60
php artisan sso:guess-opd-mappings --apply --threshold=60
php scripts/check_user.php 42
```

Kami akan melaporkan: jumlah OPD diimpor, jumlah mapping otomatis diterapkan, OPD yang masih perlu mapping manual, dan apakah `user 42` punya akses tanpa 403.

---

## Permintaan konfirmasi singkat dari Tim SSO
Harap balas singkat untuk setiap item di bawah:
- Konfirmasi header auth (Bearer atau HMAC) dan header names
- Konfirmasi sample response JSON & apakah `id` adalah stable SSO OPD id
- Konfirmasi `max_per_page`, rate limits, dan header rate-limit
- Konfirmasi Vault path telah dibuat dan berikan Vault request ID atau server hostname (jangan share token)
- Kirim daftar IP publik/CIDR yang harus di-whitelist (jika ada tambahan)
- Contact person & support hours

Terima kasih,
Tim Kematangan

---

*Catatan admin:* file ini tidak berisi token. Setelah Vault admin menempatkan token, konfirmasi Vault request ID atau hostname di channel aman dan kami akan jalankan initial sync.
