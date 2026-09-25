# Phum Kasikor Admin Web

A standalone, responsive HTML/CSS/JavaScript admin client. It communicates only with the Laravel API and does not connect to PostgreSQL.

## Run locally

1. Configure `ADMIN_WEB_ORIGINS=http://localhost:5500` in the Laravel environment. Add the exact deployed Admin Web origin in staging/production. Do not use `*` in production.
2. Start Laravel from the backend root:

   ```powershell
   php artisan serve --host=127.0.0.1 --port=8000
   ```

3. In a second terminal, from the backend root, serve this directory:

   ```powershell
   php -S localhost:5500 -t admin-web
   ```

4. Open `http://localhost:5500/login.html`.
5. Set the public API origin in `assets/js/config.js` for non-local environments. Never place credentials or provider secrets there.

An administrator must already exist in Laravel. The login page does not create accounts or assign roles.

## Connected endpoints

| Admin feature | Laravel endpoint | Current integration |
|---|---|---|
| Sign in | `POST /api/auth/login` | Connected; sends `identifier` and `password` |
| Verify session and role | `GET /api/me` | Connected; requires role `admin` |
| Sign out | `POST /api/logout` | Connected |
| Overview | `GET /api/admin/dashboard` | Connected; displays only returned values |
| Farmers | `GET /api/admin/farmers?page=&per_page=&search=` | Connected and paginated |
| Verification queue | `GET /api/admin/verifications?page=&per_page=&status=` | Connected and paginated |
| Private ID image | `GET /api/admin/verifications/{id}/documents/{front\|back}` | Connected; bearer token sent on fetch |
| Review verification | `PUT /api/admin/verifications/{id}` | Connected; rejection requires reason |
| Product catalog | `GET /api/admin/products?page=&per_page=&status=` | Connected and paginated |
| Review product | `PUT /api/admin/products/{id}/review` | Connected; approval or rejection only |

## Backend endpoints still needed

These modules display “API not available” instead of placeholder data:

- `GET /api/admin/orders`
- `GET /api/admin/payments`
- `GET /api/admin/escrow`
- `GET /api/admin/shipments`
- `GET /api/admin/reefer-vehicles`
- `GET /api/admin/telemetry/alerts`
- `GET /api/admin/cold-hubs`
- `GET /api/admin/grn`
- `GET /api/admin/compliance`
- `GET /api/admin/audit-logs`
- `GET /api/admin/users`
- `GET /api/admin/settings`

The existing API also lacks currency-aware GMV, escrow/settlement, compliance-risk and cold-chain aggregate data. Product flagging and “request action” are not supported by the current review API. Farmer detail, document generation, and secure document download for non-verification records also need backend routes.

## Security notes

- The access token is held in `sessionStorage` and sent as a Sanctum bearer token.
- Every page checks `/api/me` and requires an `admin` role. Laravel middleware remains the authorization boundary.
- Public role selection is restricted to customer/farmer. Administrator roles must be provisioned through a trusted backend process.
- The CORS allowlist is configured with `ADMIN_WEB_ORIGINS`; bearer-token requests do not require credential cookies.
- Payment secrets, database access, financial decisions, and authoritative documents remain on the backend.
