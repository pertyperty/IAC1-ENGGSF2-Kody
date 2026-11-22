# Third-Party Service Account Setup Guide (Owner Checklist)

Use this step-by-step guide to create accounts and gather **sandbox / staging** credentials in Kody's recommended activation order.

> **Security rules:**
> - **Never** paste API keys, client secrets, webhook tokens, or passwords into chat, Git commits, screenshots, or support logs.
> - Keep every provider disabled (`false`) in `.env` until isolated staging tests pass.
> - After configuring `.env` locally or on staging, run `php artisan config:clear && php artisan kody:setup-status` (read-only; makes zero external API calls).

---

## Recommended Activation Order

| Order | Service | Unlocks in Kody | Prerequisite |
| --- | --- | --- | --- |
| **1** | **SendGrid** | Email verification (6-digit OTP + link), password recovery, purchase receipts | Domain or verified single sender |
| **2** | **Judge0 CE (via RapidAPI)** | Real Python/Java/C++ execution for Coding Challenges, Weekly Events & Contributor eligibility | Dedicated `code-execution` queue worker |
| **3** | **Xendit** | GCash KodeBit purchases, creator PHP payouts, automated refunds | Business/merchant onboarding & verified fee contract |
| **4** | **Google Cloud OAuth** *(optional)* | Explicit password-confirmed Google sign-in linking for existing verified accounts | Public HTTPS domain |
| **5** | **AWS (EC2 + RDS + S3)** | Staging & production hosting, managed PostgreSQL PITR, private S3 credentials/media | Budget & named primary/backup ops owners |

---

## 1. SendGrid (Email Delivery — Start Here)

1. **Create an account:** Sign up at [https://signup.sendgrid.com/](https://signup.sendgrid.com/) (start on the Free/Essentials tier for staging).
2. **Authenticate your sender:**
   - Go to **Settings → Sender Authentication**.
   - **Recommended for production:** Click **Authenticate Your Domain** and add the CNAME/TXT (SPF/DKIM) DNS records at your domain registrar.
   - **For quick staging testing:** Click **Verify a Single Sender** using an email address you control (e.g. `no-reply@yourdomain.com` or your project mailbox).
3. **Disable click tracking** *(important!)*:
   - Go to **Settings → Tracking → Click Tracking** and turn it **Off** so SendGrid does not rewrite Kody's fragment-based one-time verification/recovery links.
4. **Create a restricted API key:**
   - Go to **Settings → API Keys → Create API Key**.
   - Choose **Restricted Access** and grant **Mail Send → Full Access** only.
   - Copy the key once and save it in your password manager.
5. **Values to place in `.env` (staging only when ready):**
   ```dotenv
   MAIL_MAILER=sendgrid
   ACCOUNT_VERIFICATION_MAILER=sendgrid
   ACCOUNT_RECOVERY_MAILER=sendgrid
   ACCOUNT_NOTIFICATION_MAILER=sendgrid
   MAIL_FROM_ADDRESS=your-verified-sender@yourdomain.com
   MAIL_FROM_NAME="Kody"
   SENDGRID_API_KEY=SG.your_private_key_here
   ```
6. **Verify:** Run `php artisan kody:setup-status` and send a real verification + recovery email to an inbox you control on staging.

---

## 2. Judge0 CE via RapidAPI (Live Code Execution — First Integration After Pilot)

1. **Create a RapidAPI account:** Sign up at [https://rapidapi.com/](https://rapidapi.com/).
2. **Subscribe to Judge0 CE:**
   - Visit [https://rapidapi.com/judge0-official/api/judge0-ce](https://rapidapi.com/judge0-official/api/judge0-ce).
   - Start on the **Basic (Free)** tier for isolated sandbox discovery and testing (set a hard billing/quota alert before upgrading).
3. **Locate your RapidAPI credentials:**
   - On the Judge0 CE Endpoints page, copy `X-RapidAPI-Key` and confirm `X-RapidAPI-Host` is `judge0-ce.p.rapidapi.com`.
4. **Discover exact compiler IDs** *(do not guess)*:
   - Use RapidAPI's **GET `/languages`** endpoint to find the exact integer IDs for **Python 3**, **Java (OpenJDK)**, and **C++ (GCC)** on your plan.
5. **Values to place in `.env` (keep `JUDGE0_ENABLED=false` until staging drills pass):**
   ```dotenv
   JUDGE0_ENABLED=false
   JUDGE0_BASE_URL=https://judge0-ce.p.rapidapi.com
   JUDGE0_API_HOST=judge0-ce.p.rapidapi.com
   JUDGE0_API_KEY=your_rapidapi_key_here
   JUDGE0_PYTHON_ID=<discovered_id>
   JUDGE0_JAVA_ID=<discovered_id>
   JUDGE0_CPP_ID=<discovered_id>
   JUDGE0_PENDING_PER_USER=3
   JUDGE0_CONFIRMATIONS_PER_24H=20
   ```
6. **Verify:** Ensure the dedicated `code-execution` queue worker is running, then run `php artisan kody:judge0-check` on staging before setting `JUDGE0_ENABLED=true`.

---

## 3. Xendit (GCash Payments & Payouts — Defer Until After Free Pilot)

1. **Create a Xendit account:** Sign up at [https://dashboard.xendit.co/register](https://dashboard.xendit.co/register) (Philippines region) and switch to **Test Mode** first.
2. **Enable products:**
   - Confirm **Payments API (GCash eWallet)** and **Payouts v3** are active in Test Mode (and submit KYC/business documents before Live Mode).
3. **Create API & webhook credentials (Test Mode first):**
   - Go to **Settings → Developers → API Keys** → generate a **Secret Key** (`xnd_development_...`) with read/write permissions for Payments, Refunds, and Payouts.
   - Go to **Settings → Developers → Webhooks** → copy your **Webhook Verification Token** and register `https://your-staging-domain/webhooks/xendit` for payment capture/failure/expiry, refund, and payout events.
   - Note your **Business ID** from account settings if using sub-account headers.
4. **Record exact contract fees** *(no guessed defaults)*:
   - From your Xendit merchant agreement/dashboard, record the exact fee values in basis points / centavos.
5. **Values to place in `.env` (keep `XENDIT_ENABLED=false` until sandbox drills pass):**
   ```dotenv
   XENDIT_ENABLED=false
   XENDIT_SECRET_KEY=xnd_development_...
   XENDIT_CALLBACK_TOKEN=your_webhook_token
   XENDIT_BUSINESS_ID=your_business_id
   XENDIT_PAYMENT_FEE_BASIS_POINTS=<exact_contract_bps>
   XENDIT_PAYMENT_FEE_FIXED_MINOR=<exact_centavos>
   XENDIT_PAYOUT_FEE_MINOR=<exact_centavos>
   XENDIT_REFUND_FEE_MINOR=<exact_centavos>
   XENDIT_CONTRACT_VERIFIED=false
   ```
6. **Verify:** Complete sandbox top-up, duplicate webhook, refund, and payout drills, then run `php artisan kody:launch-check` and `php artisan kody:ledger-check`.

---

## 4. Google Cloud Console (Optional Google Sign-In Linking)

1. **Create a project:** Go to [https://console.cloud.google.com/](https://console.cloud.google.com/) and create a project (e.g., `kody-staging`).
2. **Configure OAuth Consent Screen:**
   - Go to **APIs & Services → OAuth consent screen**.
   - Choose **External**, fill app name (`Kody`), support email (`kody.support@gmail.com`), and your privacy/terms URLs.
   - Add scopes: `openid`, `email`, `profile`.
   - In Testing mode, add your own test Google accounts as **Test users**.
3. **Create Web OAuth Client:**
   - Go to **APIs & Services → Credentials → Create Credentials → OAuth client ID → Web application**.
   - Add the exact Authorized Redirect URI: `https://your-domain/auth/google/callback`.
   - Copy the **Client ID** and **Client Secret**.
4. **Values to place in `.env` (keep `GOOGLE_AUTH_ENABLED=false` until tested):**
   ```dotenv
   GOOGLE_AUTH_ENABLED=false
   GOOGLE_CLIENT_ID=your_client_id.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=your_client_secret
   GOOGLE_REDIRECT_URI=https://your-domain/auth/google/callback
   ```

---

## 5. AWS (Staging & Production Hosting — When Leaving Local/ZeroTier)

1. **Create an AWS account** at [https://aws.amazon.com/](https://aws.amazon.com/) (enable MFA on the root account immediately and create a least-privilege IAM admin user).
2. **Set a hard monthly budget alert:** Go to **AWS Billing → Budgets** and set a monthly cost budget + email alert (Kody's pilot assumption is `PHP 12,000 / month`).
3. **Core resources to provision when ready** (see [deployment-operations.md](deployment-operations.md)):
   - **Region:** `ap-southeast-1` (Singapore — lowest latency to the Philippines).
   - **RDS PostgreSQL 16/18:** Private subnet, automated backups (35-day retention), PITR enabled, encrypted at rest, TLS CA enforced.
   - **S3 Bucket:** Block all public access, versioning enabled, server-side encryption (SSE-S3 or KMS), dedicated IAM user/role with access only to `credentials/`, `profile-pictures/`, `module-media/`, and `report-exports/`.
   - **EC2 / App host + TLS Ingress:** HTTPS termination, strict `TRUSTED_PROXIES`, Supervisor managing both `payments,notifications,default` and `code-execution` workers + Laravel scheduler cron.

---

## Quick Verification Checklist (After Filling `.env`)

1. `php artisan config:clear`
2. `php artisan kody:setup-status` — confirms local config readiness without calling external APIs.
3. `php artisan kody:launch-check` — checks release/operations flags before public pilot.
4. Keep `docs/release-evidence-index.md` updated with environment, Git SHA, date, and reviewer for every staged verification.
