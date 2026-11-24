# PROGRESS.md

Log progress, keputusan, dan pembetulan untuk projek ni. **Entri terbaru di ATAS.**

---

## 2026-11-24 — Round 3: pivot ke fork open-source + hosting settle

- **Strava subscription:** user dah ada subscription setahun — tak ada cost tambahan untuk API.
- **Pivot keputusan besar:** instead of build dari kosong, user nak **fork/adopt projek open-source sedia ada** dan customize untuk data dia, lepas tu tambah feature yang patut.
- **Hosting settle (override cadangan awal "private/password"):** web ni **public via shareable link** (senang nak send kat kawan) — bukan private/password-protected. Kena **responsive** — compatible view kat desktop & phone.
- Checked 2 kandidat base projek (license + stack confirmed):
  - **Dreeve** (dulu "Statistics for Strava") — PHP/Symfony, Docker, **AGPL-3.0**, 2.2k⭐, sangat lengkap (dashboard, monthly calendar, Eddington number, heatmap, gear, rewind, PWA, MCP server untuk Claude). AGPL = kalau share access ke orang lain lepas modify, kena sedia source code kat mereka (network copyleft) — tak masalah besar untuk personal project public repo.
  - **Runnr** — Next.js/TypeScript, Prisma, **MIT license**, PWA-capable, tapi lebih ringkas (tak ada Eddington/heatmap/calendar out-of-box), live demo di Vercel, hanya 1⭐ (kecil/kurang teruji).
- Belum settle: base projek mana nak fork (Dreeve vs Runnr), dan git workflow (proper GitHub fork vs copy code je).

## 2026-11-24 — Settle base projek + install skill Hallmark

- **Base projek untuk fork: Dreeve** (bukan Runnr) — dah ada semua feature yang dipilih (dashboard, calendar, Eddington number) + PWA + MCP server.
- **Git workflow: proper GitHub fork** — tak buat lagi (perlu akaun GitHub user bila nak push).
- Install skill **Hallmark** (nutlope/hallmark, 60.2k⭐, MIT) — anti-"AI slop" design skill, global di `~\.claude\skills\hallmark`. Security scan: Socket 0 alerts, Snyk Low Risk, tapi Gen risk assessment "Med Risk" (sebab ada browser automation/puppeteer capability) — install ni perlu approval explicit user sebab auto mode classifier sekat installer automatik pada mulanya.
- Nota: browser-based check Hallmark (`sloplint --render`) perlukan `npm i -g puppeteer-core` + Chrome — belum di-install, so static check je available buat masa ni.

## 2026-11-24 — Strava API Brand Guidelines dicatat

- User share Strava API Brand Guidelines rasmi (developers.strava.com/guidelines). Rules penuh dah masuk [CLAUDE.md](CLAUDE.md) section "Peraturan/arahan tetap projek".
- **Impact serta-merta:** nama Application yang diisi kat strava.com/settings/api tadi — `"My Strava Dashboard"` — ada perkataan "Strava" terus dalam nama, berisiko langgar rule "must not use the Strava name in your application name". **Cadangan: tukar nama** (contoh guna nama projek "Dreeve" sendiri, atau nama neutral lain), bukan "My Strava Dashboard". Boleh edit field tu di Strava settings bila-bila, tak perlu buat app baru.
- User dah create Strava API app: Client ID `300302`, Category "Performance", icon custom (assets/app-icon.png) dah diupload. **Nota: Application Name masih kena disemak/tukar** dari "My Strava Dashboard" ke nama neutral — belum confirm dah tukar ke belum.
- Strava app skrg "Standard Tier": 1 athlete, 200 req/15min, 2000/hari. Boleh "Upgrade" untuk naik ke 10 athlete/400 req kalau perlu nanti (tak perlu buat masa ni sebab single-player).

## 2026-11-24 — Fork Dreeve siap & pushed

- Confirm akaun GitHub user: **brodyn-dev**.
- `gh repo fork dreeveapp/dreeve --clone=false` → fork jadi di **github.com/brodyn-dev/dreeve**.
- Remote disetup dalam folder projek ni: `origin` = fork brodyn-dev, `upstream` = dreeveapp/dreeve asal (untuk pull update upstream lepas ni).
- Checkout `origin/master` → semua codebase Dreeve (PHP/Symfony) kini dalam folder projek ni, sekali dengan CLAUDE.md/PROGRESS.md/assets/ yang kita dah buat (tiada collision nama fail).
- Commit + push: CLAUDE.md, PROGRESS.md, assets/app-icon.png, skills-lock.json berjaya push ke `origin/master` (commit `cf69e7b01`).
- **`.env.local` dah dicipta** (kosong, placeholder) — fail ni **ignored by git** (dalam `.gitignore` sedia ada: `/.env.local`), tempat selamat untuk simpan STRAVA_CLIENT_ID/SECRET/REFRESH_TOKEN sebenar. User kena isi sendiri terus dalam fail (bukan hantar value kat chat).
## 2026-11-24 — Dreeve berjaya jalan (containers healthy)

- Docker Desktop install berjaya (WSL2 backend, All-users install). Sempat jumpa isu "WSL needs updating" → fix dengan `wsl --update`, restart Docker Desktop app.
- `docker compose up -d` di `deploy/` berjaya — container `dreeve` & `dreeve-daemon` status **healthy**.
- **Nota penting (nearly tersilap):** generate `ADMIN_PASSWORD_HASH` guna `echo "<password>" | docker compose exec -T app bin/console security:hash-password --no-interaction -` (bukan interactive prompt — ada isu `stty`/hidden-input dalam container Alpine). User accidentally generate hash guna password contoh yang saya bagi dulu (`PasswordAwak123`) — **dibetulkan**, generate semula dengan password sebenar.
- **Bug ditangkap & dibetulkan:** hash password yang diisi ke `.env` **tak di-wrap dalam single quote** pada percubaan pertama → Docker Compose interpolate `$` dalam hash jadi variable (`"RIMAkS" variable is not set` warning) → hash jadi corrupt. Dibetulkan dengan wrap `ADMIN_PASSWORD_HASH='...'` — recreate container, warning hilang.
- `curl localhost:8080` → HTTP 302 (redirect ke login page) — app jalan betul.
- **Update:** Login admin + Strava OAuth flow berjaya — "You're connected". Refresh token sebenar dah diisi ke `deploy/.env`, container di-restart, healthy.
- **Update:** Import run #1 siap — 250/1034 aktiviti imported, 0 error/skip, exit code 0 (~46 min, kena rate-limit breather sekali). Import jalan dalam batch (ikut rate limit Strava), bukan semua sekali — kena run `app:import:strava` berulang kali, atau setup auto-schedule di admin panel (**Settings > Scheduled actions**, stravaApi mode). Import run #2 sedang jalan di background.
- **Next:** teruskan run import sampai 1034/1034, setup scheduled action untuk auto-import, check map visibility setting di strava.com/settings/privacy.
- **Update branding (selesai):** "App subtitle" (Settings → Appearance) diisi **"Hafizz's Training Log"** — keluar public di navbar ("Dreeve | Hafizz's Training Log"), tak perlu login untuk nampak. Profile picture URL (Settings → General) diisi dengan URL foto Strava (`https://dgalywyvr863hv.cloudfront.net/pictures/athletes/24457623/10602771/4/large.jpg`) — confirmed berjaya save.
- **Update import:** batch #2 jalan — 416/1034 aktiviti (ikut dashboard "Activities 416"), masih proceed ikut rate limit Strava.
- **Keputusan hosting (belum confirm user):** nak guna Cloudflare Tunnel (percuma, dua domain user — nadianalytics.com & brodynhq.com — dah di Cloudflare NS) untuk dapat public link. Cadangan: domain **brodynhq.com** (personal, bukan domain kerja), subdomain **training.brodynhq.com** (elak guna "strava" dalam nama ikut brand guideline). User tanya pasal subdomain tak clash dengan website sedia ada — confirmed selamat (DNS record berasingan). **Tunggu user confirm domain/subdomain pilihan sebelum setup Cloudflare Tunnel.**

## 2026-11-24 — Status semasa: tunggu restart lepas install Docker Desktop

- User tengah install **Docker Desktop** (All-users installation, WSL 2 backend, tanpa Windows Containers) — installer berjaya lepas enable "Virtual Machine Platform". User akan **restart laptop** sekarang.
- **NEXT STEP lepas restart:**
  1. Buka Docker Desktop sekali (first-run setup, biar dia settle)
  2. Verify Docker jalan: `docker --version` / `docker compose version`
  3. Isi `deploy/.env` — `STRAVA_CLIENT_ID` + `STRAVA_CLIENT_SECRET` (dari strava.com/settings/api, app "Hafizz Training Log", Client ID = `300302`). **Jangan isi `STRAVA_REFRESH_TOKEN`** — biar `replace-me`.
  4. `cd deploy && docker compose up -d`
  5. Generate admin password hash: `docker compose exec app bin/console security:hash-password` → masukkan hasil ke `ADMIN_PASSWORD_HASH` dalam `deploy/.env` (ingat: kalau ada `$` dalam hash, guna single-quote atau double setiap `$` — rujuk docs/getting-started/installation.mdx)
  6. `docker compose up -d` sekali lagi (recreate container untuk apply .env baru)
  7. Buka `http://localhost:8080` → login admin → ikut OAuth flow Strava (first-launch) untuk dapat refresh token sebenar
  8. Check Strava privacy setting (strava.com/settings/privacy) — "Hide your activity maps from others completely" kena OFF
  9. Run import: `docker compose exec app bin/console app:import:strava`
- Semua fail setup (docker-compose.yml, .gitignore) dah commit & push ke fork. `deploy/.env` sengaja TAK commit (gitignored, ada secret).

## 2026-11-24 — Betulkan approach deployment (koreksi penting)

- **Koreksi:** `.env.local` di root repo (yang kita buat awal) **salah konteks** — tu untuk contributor Dreeve run test suite/dev dari source, bukan untuk deploy/run app sebenar. Dah **dibuang**.
- **Cara betul (ikut docs/src/content/docs/getting-started/installation.mdx):** Dreeve dijalankan dari **published Docker image** (`robiningelbrecht/dreeve:latest`), bukan build dari source kita punya. Fork `hafizz-training-log` tetap berguna untuk **customize code** lepas ni, tapi untuk run & guna app, tak perlu build dari source.
- Setup baru kat folder **`deploy/`** (dalam projek ni, tapi berasingan logic dari source Dreeve):
  - `deploy/docker-compose.yml` — dari template rasmi (2 container: `app` + `daemon`, port 8080)
  - `deploy/.env` — **gitignored**, dah isi `IMPORT_MODE=stravaApi`, `APP_URL=http://localhost:8080`, `TZ=Asia/Kuala_Lumpur`, `APP_SECRET` (random generated). **Tinggal isi:** `STRAVA_CLIENT_ID`, `STRAVA_CLIENT_SECRET` (dari strava.com/settings/api), dan `ADMIN_PASSWORD_HASH` (generate lepas container jalan kali pertama).
  - ⚠️ **PENTING:** `STRAVA_REFRESH_TOKEN` — JANGAN guna value "Refresh Token" yang Strava settings page tunjuk (confirmed dari docs: "tak akan jalan"). Biar `replace-me` — token sebenar diperoleh lepas buat OAuth flow kat `localhost:8080` first-launch.
  - Authorization Callback Domain kat Strava settings (`localhost`, tanpa port) — **dah betul** ikut requirement docs ("Do not include a port number").
  - Nota tambahan dari docs: check Strava privacy setting (strava.com/settings/privacy) — "Hide your activity maps from others completely" kena **OFF** supaya map aktiviti boleh nampak dalam Dreeve.
- `.gitignore` ditambah: `/deploy/.env`, `/deploy/build`, `/deploy/storage`, `/deploy/watch`, `/deploy/backups`.
- **Blocker semasa: Docker Desktop TAK installed** kat laptop ni (`docker` command not found, Docker Desktop.exe pun takde). Kena install dulu sebelum boleh `docker compose up -d`. Prerequisite rasmi Dreeve: Docker + Docker Compose je (boleh guna fail aktiviti .fit/.gpx/.tcx sebagai alternatif kalau tak nak guna Strava API pun, tapi kita dah pilih stravaApi mode).
- Strava API Application di-rename ke **"Hafizz Training Log"** (compliant — tiada perkataan "Strava" dalam nama). Nota: ni nama OAuth app je, berlainan dari nama software "Dreeve" (engine/codebase) — tak perlu match.
- Repo GitHub di-rename dari `brodyn-dev/dreeve` → **`brodyn-dev/hafizz-training-log`**. Confirmed fork relationship dengan `dreeveapp/dreeve` (upstream) masih intact lepas rename. Local remote `origin` URL dah disync.

## 2026-11-24 — Semua skill dijadikan global

- User nak guna `grill-me`, `grilling`, `grill-with-docs`, `no-ai-slop` untuk **semua projek depan**, bukan Strava je — reinstall semua dengan `-g` ke `~\.claude\skills\`.
- Buang copy project-local (`.claude/skills/` dalam folder Strava ni) sebab dah redundant dengan global.
- **Sekarang semua 5 skill (grill-me, grilling, grill-with-docs, no-ai-slop, hallmark) global** — available di semua projek/session Claude Code user.

## 2026-11-24 — Round 1 grilling settle (semua "yes")

- **Skop pengguna:** single-player — untuk user sendiri je, tak buka untuk orang lain log in.
- **Apa "progress" bermaksud:** dashboard umum (jarak/pace/elevation ikut bulan/tahun) + goal tracker khusus.
- **Tech stack:** Next.js (full-stack, satu codebase).
- **Data storage:** simpan dalam DB sendiri (SQLite/Postgres) — bukan tarik live setiap kali.
- **Hosting:** deploy public tapi private/password-protected (boleh check dari phone).
- Skill `grill-me`, `grilling`, `grill-with-docs`, `no-ai-slop` di-install ke `.claude/skills/` untuk bantu scoping & writing projek ni.
- Research projek Strava tracker sedia ada (Statistics for Strava/Dreeve, Runnr, zest, Goal Visualizer, Endurain) siap. Table stakes: dashboard, route map (Leaflet), gear tracking, PR/best-effort, calendar/monthly view, distance-over-time chart. Differentiator idea: Eddington number, geo heatmap semua route, Rewind year-in-review, race time prediction.
- **FAKTA PENTING (disahkan via web search 2026-11-24):** Strava API dah jadi paid sejak pertengahan 2026 — Standard tier (≤10 athlete, cukup untuk single-player) kena **subscription Strava ~$11.99/bulan atau $79.99/tahun**. Existing developer pun kena subscribe lepas 30 Jun 2026. Club endpoints dibuang, Explore Segments dihadkan ke Extended Access je. Ni cost real yang user kena confirm terima sebelum teruskan dengan Strava API langsung.

## 2026-11-24 — Projek baru mula

- Folder projek baru dicipta, kosong (tak ada code lagi).
- Setup awal: CLAUDE.md + PROGRESS.md dicipta.
- Git repo di-init local (belum ada remote — tunggu user ready nak push).
- Tujuan projek belum ditentukan lagi (user belum specify sama ada ni analisis data Strava peribadi, integrasi API untuk Nadi Analytics, atau lain-lain). Update section ni bila dah jelas.

### Next steps
- Tentukan skop/tujuan sebenar projek.
- Mula struktur code bila skop dah jelas.
