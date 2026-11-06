# Deploy SheShield for free: Render + Aiven + Vercel

This is how the pieces fit together:

```
Visitor ──> Vercel (free)            serves pro/ and public/: landing, sign-in, CSS, JS, fonts
              │  forwards /api, /views, /admin, /uploads
              ▼
            Render (free)            runs the PHP app from infrastructure/docker/Dockerfile
              │  encrypted MySQL connection (DB_SSL=true)
              ▼
            Aiven for MySQL (free)   the database; the app creates its tables on first use

            Brevo (free, optional)   sends confirmation emails over HTTPS
```

Visitors only ever see the Vercel address, so sign-in cookies work without any cross-site setup. Render also works on its own: skip part 4 and use the Render address instead.

## What the free plans don't cover

| Limit | Effect | Fix |
|---|---|---|
| Render sleeps after 15 minutes idle | The next request takes up to a minute | The keep-awake workflow (below), or a Render Starter instance (about $7/month) |
| Render's disk is wiped on every deploy or restart | Uploaded evidence and profile photos disappear, and everyone is signed out | Paid instance plus a persistent disk mounted at `/var/www/html/uploads` |
| Render's free plan blocks SMTP (ports 25, 465, 587) | Gmail/SMTP email can't send | Use Brevo (below), which sends over HTTPS |
| Aiven free MySQL: 1 GB storage | Plenty for a demo | Paid Aiven plan |
| Vercel Hobby is for personal, non-commercial use | Fine for a student project | Vercel Pro |

---

## 1. Create the database (Aiven)

1. Sign up at https://console.aiven.io (no card needed for the free plan).
2. **Create service** → **MySQL** → choose the **Free** plan → pick the region closest to you → **Create**.
3. Wait until the service shows **Running**, then open its **Overview** page and keep it open. You need these values from **Connection information**:
   - **Host**, for example `mysql-sheshield-yourname.l.aivencloud.com`
   - **Port**, a five-digit number, not 3306
   - **User**: `avnadmin`
   - **Password**: click to reveal
   - **Database name**: `defaultdb`

Aiven only accepts encrypted connections. `render.yaml` already sets `DB_SSL=true`, which the app needs to connect.

## 2. (Optional) Set up email (Brevo)

Skip this if you don't need confirmation emails. Everything else works without it.

1. Sign up at https://www.brevo.com (the free plan sends 300 emails a day).
2. **Senders, domains & dedicated IPs** → **Senders** → add the address emails should come from, and confirm the email Brevo sends you.
3. **SMTP & API** → **API keys** → **Generate a new API key**, and copy it.

## 3. Deploy the app (Render)

1. Sign up at https://render.com with your GitHub account.
2. **New** → **Blueprint** → choose the `SheShield` repository. Render reads `render.yaml` and shows one web service, `sheshield`.
3. Fill in the values it asks for:

   | Variable | Value |
   |---|---|
   | `DB_HOST` | Aiven host |
   | `DB_PORT` | Aiven port |
   | `DB_USER` | `avnadmin` |
   | `DB_PASS` | Aiven password |
   | `APP_URL` | Leave empty for now (set in part 5) |
   | `BREVO_API_KEY` | Brevo key, or leave empty |
   | `MAIL_FROM_EMAIL` | The sender you confirmed in Brevo, or leave empty |

4. **Apply**. The first build takes about 5 minutes.
5. When it shows **Live**, open the service and copy its address, for example `https://sheshield.onrender.com`. If that name was taken, Render adds a suffix such as `https://sheshield-ab12.onrender.com`.
6. Open `<render address>/pro/landing.html`. The page loads, and the first visit creates the database tables.

## 4. Put Vercel in front (optional, recommended)

1. `vercel.json` forwards to `https://sheshield-y9jp.onrender.com`. If your Render address is different, replace it there (it appears once) and in `.github/workflows/keep-awake.yml`, then commit and push.
2. Sign up at https://vercel.com with GitHub → **Add New** → **Project** → import `SheShield`.
3. Leave the settings as they are (`vercel.json` sets the build) and click **Deploy**.
4. Open the address Vercel gives you, for example `https://sheshield.vercel.app`. It redirects to the landing page.

Vercel only receives the static pages and assets (`scripts/vercel-build.js` copies `pro/` and `public/`). PHP source and config never leave Render.

## 5. Finish up

1. **Links in emails:** on Render, open **Environment** and set `APP_URL` to your public address (the Vercel one, or the Render one if you skipped Vercel). Save; Render redeploys.
2. **Google sign-in:** in the Firebase console → **Authentication** → **Settings** → **Authorized domains**, add your Vercel domain and your Render domain.
3. **Make yourself an admin:** sign up on the live site, then run this from a terminal (XAMPP's MySQL client works) and sign in again:

   ```bash
   D:\xampp\mysql\bin\mysql.exe --ssl -h <aiven host> -P <aiven port> -u avnadmin -p defaultdb -e "INSERT INTO admins (user_id) SELECT id FROM users WHERE email='you@example.com'"
   ```

## Keeping the app awake

`.github/workflows/keep-awake.yml` calls `/api/public_stats.php` every 10 minutes, so Render never reaches its 15-minute sleep timer and the Aiven database sees regular use. It runs on GitHub's free Actions minutes for public repositories.

- One always-on free service uses about 744 of Render's 750 free hours a month, so keep only one free service running in the workspace.
- GitHub sometimes delays scheduled runs at busy times, so an occasional cold start can still happen. For a stricter check, also add a free monitor at https://uptimerobot.com pointing at the same URL every 5 minutes.
- GitHub pauses schedules in repositories with no commits for 60 days. Re-enable it from the **Actions** tab.
- To run it by hand: **Actions** → **Keep Render awake** → **Run workflow**.

## Optional: verify the database certificate

`DB_SSL=true` encrypts the connection. To also check that you are talking to your Aiven server, download the **CA certificate** from Aiven's Overview page, then on Render go to **Environment** → **Secret Files**, add it as `ca.pem`, and set `DB_SSL_CA=/etc/secrets/ca.pem`.

## Troubleshooting

| Symptom | Cause |
|---|---|
| First load takes about a minute | Render was asleep; normal on the free plan |
| 500 error on every PHP page | Check Render → **Logs**. "insecure transport" means `DB_SSL` isn't `true`; "Access denied" means a wrong user or password |
| Landing page loads on Vercel but sign-in fails | `vercel.json` still points at the wrong Render address |
| Photos disappeared | Render redeployed or restarted; expected on the free plan |
| Email not sent | Check `BREVO_API_KEY`, and that `MAIL_FROM_EMAIL` is a sender confirmed in Brevo |

## Environment variables the app reads

| Variable | Default | Purpose |
|---|---|---|
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | XAMPP values | MySQL connection |
| `DB_SSL` | off | `true` encrypts the MySQL connection |
| `DB_SSL_CA` | none | CA file; when set, the server certificate is verified |
| `APP_URL` | worked out from the request | Public address used in email links |
| `BREVO_API_KEY` | none | Send email through Brevo's HTTPS API |
| `SMTP_HOST`, `SMTP_PORT`, `SMTP_USERNAME`, `SMTP_PASSWORD`, `SMTP_ENCRYPTION` | none | Send email over SMTP instead (paid Render instance, Docker, XAMPP) |
| `MAIL_FROM_EMAIL`, `MAIL_FROM_NAME` | none, `SheShield` | Sender shown on emails |
