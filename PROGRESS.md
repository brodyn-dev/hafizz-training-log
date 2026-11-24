# PROGRESS.md

Log progress, keputusan, dan pembetulan untuk projek ni. **Entri terbaru di ATAS.**

---

## 2026-11-25 — Fix bug: widget "Challenge consistency" table tak full width

User perasan table kat widget "Challenge consistency" (dashboard) tak stretch sampai tepi card, lain dari widget Yearly Stats di atas dia yang full width.

**Punca:** `templates/html/dashboard/widget/widget--challenge-consistency.html.twig` — `<table>` tu tak ada class `w-full`, unlike semua table widget lain (contoh `widget--yearly-stats.html.twig` yang ada `class="w-full ..."`). Table tanpa `w-full` auto shrink ikut content, bukan stretch ikut card.

**Fix:** tambah `w-full` kat table tu. Ni **pre-existing bug upstream Dreeve**, bukan dari rebrand kita.

Rebuild: docker image → recreate `app` → `app:cache:render:clear` (ikut checklist yang dah ditambah). Verified di localhost — table dah full width match Yearly Stats. Commit `014f30091`, pushed.

## 2026-11-25 — Lengkapkan design spec yang tercicir (Hanken Grotesk + tabular-nums)

User tanya: "redesign ni dah follow skill no-ai-slop dan hallmark?" — jawapan jujur lepas check code: **tak sepenuhnya**.

- `no-ai-slop` — skill untuk tulisan/teks, bukan design visual. Tak relevan untuk kerja rebrand CSS/font ni.
- `hallmark` — prinsip dia (elak gradient/neon, elak font banned-list, 1 accent + neutral) digunakan secara **manual/konsep** masa fasa "approve design direction", **bukan** invoke skill tu penuh. Approved spec sendiri sebut font patut **Manrope (body) + Hanken Grotesk (heading/nombor besar)** + `tabular-nums` untuk semua figure — tapi commit build sebenar (`08ac56830`) cuma apply Manrope untuk SEMUA (termasuk heading), dan `tabular-nums` yang jumpa dalam code rupanya dah sedia ada dari upstream Dreeid (bukan kita yang tambah).

**Fix (lengkapkan 2 item yang tercicir):**
- `public/css/tailwind.css` — tambah `--font-heading: "Hanken Grotesk", ui-sans-serif, system-ui` + base-layer rule `h1,h2,h3,h4,h5,h6 { font-family: var(--font-heading) }` (automatik cover semua widget title/heading, tak perlu edit setiap template).
- `templates/html/layout/app-shell.html.twig` — tambah import Google Fonts Hanken Grotesk sekali dengan Manrope.
- `templates/html/dashboard/widget/widget--intro-text.html.twig` — tambah class `font-heading tabular-nums` kat `kbdTagOpen` (chip nombor macam "3 684 km", "805 active days" dalam naratif dashboard).
- **Scope sengaja dihadkan** kat heading (global) + chip naratif dashboard — TAK retrofit semua 25 widget dashboard (rolling-stats, eddington, dll) sebab terlalu luas untuk 1 session, risiko tinggi tanpa test menyeluruh. Boleh extend lepas ni kalau user nak.
- Rebuild: `tailwindcss cli --minify` → docker build image → recreate container `app` je.
- Commit `dfc607960`, pushed.

**🐛 Jumpa isu baru masa verify — Dreeve "Render cache":**
- Lepas rebuild+recreate container, perubahan **TAK nampak** di browser walaupun fail/image dah betul. Punca: Dreeve ada cache HTML page sendiri (`app:cache:render:clear` command), keyed by app VERSION — sebab kita tak bump version (masih 5.9.1) bila buat custom build, Dreeve ingat takde apa berubah & terus serve HTML lama.
- **Fix:** run `docker compose exec app bin/console app:cache:render:clear` lepas setiap custom rebuild yang ubah template/CSS/JS.
- **📌 PENTING untuk next session — tambah kat checklist rebuild:** lepas `docker compose up -d --no-deps --force-recreate app`, SENTIASA run `app:cache:render:clear` sekali, barulah verify.

**⚠️ Isu cache LAIN yang confirmed (perlu user purge lagi):**
- Static asset URL (`css/dist/tailwind.min.css?v5.9.1`, `js/dist/app.min.js?v5.9.1`) guna cache-bust ikut **app version**, bukan content-hash. Version tak bergerak bila kita custom-build sendiri → browser & Cloudflare cache **1 tahun** (`immutable`) walaupun content dah tukar.
- **Next action (user):** purge Cloudflare cache SEKALI LAGI (dashboard → Caching → Purge Everything) — kali ni untuk CSS (tailwind.min.css), bukan JS. Lepas tu clear cache browser phone macam biasa (private tab / clear cached files) untuk confirm heading font (Hanken Grotesk) & tabular-nums nampak di `trainlog.brodynhq.com`.
- **Root cause ni akan ulang setiap kali** kita custom-build CSS/JS tanpa bump app version — accept as known tradeoff buat masa ni (single-user project, purge manual okay), tak worth effort tukar ke content-hash versioning dalam upstream Dreeve.

## 2026-11-25 — Fix bug: Theme toggle (Light/System/Dark) tak sync visual

User report: button Theme di profile dropdown "tak berfungsi seperti sepatutnya".

**Diagnosis (confirmed via test berulang kali di browser, localhost & live):**
- Theme sebenarnya **BERFUNGSI** — klik Light/Dark/System betul-betul tukar warna app & simpan ke `localStorage`.
- **Bug sebenar:** highlight (kotak oren) pada icon yang dipilih **tak sync** — lepas klik atau lepas reload, kadang tunjuk takde satu pun terpilih, kadang tunjuk pilihan lain dari yang sepatutnya. Punca: `public/js/layout/dark-mode.js` cuma set radio button `checked` **sekali sahaja** masa page load — tak refresh balik bila dropdown dibuka semula.
- Ni bug asal upstream Dreeve punya code, bukan sesuatu yang kita rosakkan masa rebrand.

**Fix:**
- `public/js/components/overlay/dropdown.js` — dispatch custom event `dropdown:opened` bila mana-mana popover dropdown dibuka.
- `public/js/layout/dark-mode.js` — extract logic sync checked state ke method `syncCheckedState()`, panggil balik setiap kali `dropdown:opened` event fired (bukan sekali je masa load).
- Rebuild: `webpack` (JS je, tak sentuh CSS/PHP) → `docker build` image `dreeve-custom:local` baru → `docker compose up -d --no-deps --force-recreate app` (elak kacau `daemon`/import, ikut pelajaran insiden container-recreate sebelum ni).
- **Verified fix** di `localhost:8080` (bypass Cloudflare cache): theme toggle sync betul lepas klik, lepas reopen dropdown, dan lepas full page reload — semua 3 skenario confirmed consistent.
- Commit `694fbf7b4`, pushed ke `origin/master`.

**Cloudflare CDN cache — SETTLE:**
- Punca awal: public URL serve app.min.js LAMA sebab cache-bust guna app version (`?v5.9.1`) bukan content-hash — version tak berubah, Cloudflare cache 1 tahun (`immutable`).
- User dah purge cache (Cloudflare dashboard → Caching → Purge Everything). **Confirmed via curl:** live `app.min.js` kini byte-for-byte sama dengan fail fixed tempatan (md5 match), `Age` header reset (fresh fetch dari origin).
- **Fully resolved** — tak perlu further action.

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
## 2026-11-24 — Custom build SIAP: design baru dah LIVE di app sebenar

Sambung dari design direction yang approved — kali ni betul-betul apply ke app (bukan mockup je). Dilakukan masa Strava API block (daily rate limit), so zero risiko kacau import.

**Apa yang ditukar (source, bukan runtime config):**
- `public/css/tailwind.css` — `--color-primary: #F26722` → **`#E8552A`**, `--font-sans: Inter` → **`Manrope`**
- `public/css/theme/dark-mode.css` — `--color-primary-soft` (light mode) dikemaskini match warna baru
- `public/css/components/scroll-to.css` — hardcoded `rgba(242,103,34,...)` → `rgba(232,85,42,...)` (ni rupanya literal RGB decimal, bukan hex — Hallmark reminder: selalu grep format RGB jugak bila cari warna, bukan hex je)
- `public/js/features/maps/map-colors.js` — `PRIMARY_COLOR` (warna route/polyline map) ikut sama
- `templates/svg/badge/svg-dreeve-badge.html.twig` — embeddable badge SVG, semua icon stroke/fill
- `src/Application/PageMetadata/OpenGraphImageCanvas.php` — bar bawah gambar OG auto-generate (dulu literal `0xFC5200`, warna brand **Strava** — sengaja tukar ke warna kita sendiri, elak visual association dgn Strava)
- `templates/html/layout/app-shell.html.twig` — `theme-color` meta, + `<link>` Google Fonts Manrope
- `templates/html/navigation/top-nav-bar.html.twig` — buang `hidden md:block` pada subtitle (fix asal: subtitle sentiasa nampak di phone)

**Bug besar jumpa & fix (penting untuk masa depan):**
- `docker/app/docker-entrypoint.sh` dan `bin/console` ada **CRLF line ending** (dari checkout Windows) → rosakkan shebang line bila jalan dalam Linux container (`exec ... no such file or directory`, `env: can't execute 'php'`). **Punca**: git checkout di Windows convert LF→CRLF automatik untuk fail text (bukan isu dalam git history asal). **Fix jangka panjang**: tambah `.gitattributes` (`*.sh text eol=lf`, `bin/* text eol=lf`) supaya tak berulang.

**Cara build (sejak Dreeve ship sebagai published image, bukan build dari source):**
1. `npm install` (Node dah ada local, PHP/Composer takde)
2. `npx @tailwindcss/cli -i public/css/tailwind.css -o public/css/dist/tailwind.min.css --minify` (+ versi unminified)
3. `node_modules/.bin/webpack --config webpack.config.js`
4. `docker run --rm -v "$(pwd):/app" composer:2 install --no-dev --ignore-platform-req=ext-{bcmath,gd,intl,zstd}` (generate `vendor/` guna Docker, sebab PHP/Composer takde kat host)
5. `docker build -f docker/app/Dockerfile -t dreeve-custom:local .`
6. `deploy/docker-compose.yml`: tukar `image: robiningelbrecht/dreeve:latest` → **`image: dreeve-custom:local`** (app + daemon)
7. `docker compose up -d`

**Confirmed working:** local (`localhost:8080`) & public (`trainlog.brodynhq.com`) dua-dua HTTP 200, warna baru di CSS served, font Manrope loaded, subtitle navbar tiada `hidden` class lagi. Commit `08ac56830`, pushed.

**Belum/boleh buat lepas ni:**
- Backfill import masih tergantung (Strava daily limit) — retry lepas ~8am MYT
- Kalau Dreeve keluar versi baru (`dreeveapp/dreeve` upstream update), kena re-build image custom ni semula (bukan auto-update macam guna published image) — ingat proses di atas

## 2026-11-24 — Design direction APPROVED (belum apply ke app sebenar)

User tengok mockup (bukan app sebenar, Artifact preview je — [link mockup](https://claude.ai/artifact/MaYaLhgoSUXDdaWVMVhZRJ)) dan **approve** arah design ni untuk custom build nanti:

- **Mode:** Light (bukan dark asal Dreeve) — light mode sebenarnya **dah wujud** dalam app asal (toggle Settings → Theme), cuma kita nak custom palette lagi premium dari default-nya
- **Warna/accent:** Teruskan dengan **oren** (refine dari oren asal Dreeve, versi light: `#e8552a` atas latar putih/light neutral `#f3f4f6`) — bukan pilih Brass/Moss/Petrol yang dicadang
- **Font:** **Manrope** (body) + **Hanken Grotesk** (heading/nombor besar) — ganti Inter+Space Grotesk asal (yang rupanya "overused AI default" ikut Hallmark). Dua-dua Google Fonts, percuma
- **Nombor:** semua figure (stat card, chip jarak) guna `font-variant-numeric: tabular-nums` untuk digit align kemas
- **Layout/posisi/fungsi:** **TIDAK berubah langsung** — user explicit nak warna/font/feel je, bukan struktur

**Asas keputusan:** guna prinsip skill **Hallmark** (anti-AI-slop design, dah install global) — elak purple-blue gradient, elak "near-black+neon pop", elak font banned-list (Inter, Roboto, dll), guna 1 accent je + neutral dengan hue bias deliberate.

**Belum settle:** macam mana nak apply ni ke app SEBENAR. Ingat — kita run dari **published Docker image**, bukan build dari source. Untuk betul-betul ubah warna/font Dreeve, kena:
1. Edit `tokens`/Tailwind config + template yang relevan dalam fork kita
2. Switch `deploy/docker-compose.yml` dari `image:` ke `build:` (guna `docker/app/Dockerfile` yang dah ada dalam repo — tapi **tiada composer install/npm build step** dalam Dockerfile tu, kena figure out macam mana asset compile — mungkin kena tambah build stage sendiri)
3. Rebuild (anggaran 10-20 minit first time) — **buat ni lepas backfill import siap**, elak ulang insiden container-recreate-interrupt-import macam hari ni (3 kali berlaku)

**Next session:** teruskan guna panduan Hallmark penuh (bukan cuma prinsip manual) untuk custom build sebenar, lepas backfill 1034 aktiviti siap (cron 2:30am patut dah selesaikan baki).

## 2026-11-24 — Fix APP_URL untuk WhatsApp/social link preview

- User nak share link `/dashboard` kat WhatsApp, tanya pasal preview (title/image).
- **Jumpa bug:** `og:url` tunjuk `http://localhost:8080/dashboard` (salah) sebab `APP_URL` dalam `deploy/.env` masih localhost dari setup awal. **Fixed** → `APP_URL=https://trainlog.brodynhq.com`, container `app`+`daemon` di-recreate (selamat, tiada import aktif waktu tu — jam 9:46 malam, cron baru 2:30 pagi).
- **Confirmed:** `og:description` auto-personalized dengan baik — `"Hafizz Dzulkifli · 646 activities · 2,689 km · 648 h"` (dari `PageMetadataRenderer.php`, cache-aware).
- **Nota penting:** sistem auto-generate OG image (avatar+nama+stats, 1200×630) **wujud** dalam Dreeve, tapi **cuma wired untuk route `/activities/{id}` & `/activities/collections/{id}`** — bukan `/dashboard`. Share link `/dashboard` takkan ada gambar preview; share link satu aktiviti individu akan ada.
- **Belum settle (optional, rendah prioriti):** Strava API app punya "Authorization Callback Domain" masih `localhost` — tak masalah buat masa ni (refresh token dah ada, tak perlu redo OAuth), tapi kalau perlu reconnect Strava suatu hari nanti, kena tukar setting tu ke `brodynhq.com` dulu (ikut APP_URL baru).

## 2026-11-24 — Backfill import: BELUM siap (kena Strava DAILY rate limit, bukan "habis")

- User lari petang/malam ni → run manual import, aktiviti baru terus masuk (1034→1035 total Strava).
- Run seterusnya lompat 646→876/1035, lepas tu run #3 "0 aktiviti baru, 11 saat" — **mulanya saya silap sangka ni maksud backlog dah habis**. Betul-betul check log penuh: sebenarnya kena **"You reached the daily Strava API rate limit. You will need to import the rest of your data tomorrow"** — run tu terus abort awal, bukan scan habis.
- **Status sebenar:** 876/1035 (baki ~159 lagi belum masuk), block oleh Strava daily quota (bukan 15-min window biasa).
- **Nota penting untuk esok:** had harian Strava biasanya reset ~tengah malam UTC (~8 pagi waktu Malaysia). Cron `30 2 * * *` (2:30 pagi MYT = ~18:30 UTC hari sebelum) **kemungkinan masih kena block** sebab belum sempat reset lagi waktu tu. Kalau macam tu, tak rosak apa-apa — tunggu lepas ~8 pagi MYT, retry manual (`docker compose exec app bin/console app:import:strava`) patut berjaya sambung baki.

## 2026-11-24 — Penutup sesi: semua clear, sistem OK

**Status akhir sesi ni:**
- ✅ 3 container Docker jalan: `dreeve` (healthy), `dreeve-daemon` (healthy), `dreeve-cloudflared`
- ✅ Public URL **https://trainlog.brodynhq.com/dashboard** — confirmed HTTP 200 (external curl)
- ✅ Local URL **http://localhost:8080/dashboard** — confirmed HTTP 200
- ✅ Git: working tree clean, semua commit pushed ke `origin/master` (github.com/brodyn-dev/hafizz-training-log), tiada unpushed changes
- ✅ Secrets selamat: `deploy/.env` (Strava Client ID/Secret/Refresh Token, Cloudflare Tunnel token, admin password hash, APP_SECRET) confirmed **gitignored**, tak pernah masuk git history
- ⏳ Backfill import ~600+/1034 aktiviti — baki akan sambung **automatik** via scheduled cron (`30 2 * * *`, 2:30 pagi) — tak perlu buat apa-apa

**Untuk sambung kerja lepas ni (chat baru pun boleh):**
1. Baca PROGRESS.md ni dari atas (CLAUDE.md dah arahkan macam tu)
2. Check `docker ps` — patut semua 3 container masih "Up" (kalau laptop/Docker Desktop tak dimatikan)
3. Check progress import kalau nak tahu dah berapa banyak masuk (`docker compose exec app bin/console` ada command relevant, atau tengok terus di dashboard "Activities" count)
4. Benda yang belum settle/boleh explore lepas ni: custom UI/design (Hallmark), tambah Garmin connector (kalau nak), rename nama fail assets/app-icon.png jadi lebih descriptive, consider domain `nadianalytics.com` ke tidak untuk projek lain

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
## 2026-11-24 — Hosting siap! Public URL jalan

- Subdomain final settle: **`trainlog.brodynhq.com`** (brainstorm: training→gains→grind [reject, nama Grindr]→trainlog, padan dengan nama app Strava "Hafizz Training Log").
- Service `cloudflared` ditambah ke `deploy/docker-compose.yml`, token di `deploy/.env` (`CLOUDFLARE_TUNNEL_TOKEN`, gitignored).
- User buat tunnel "dreeve-trainlog" di Cloudflare Zero Trust dashboard sendiri (Networking → Tunnels → Create Tunnel → Docker), copy token, docker compose up -d.
- Nota: masa `docker compose up -d` untuk start cloudflared, container `app` ter-**recreate**, sebabkan import batch #3 yang sedang jalan kena SIGKILL (exit 137). Di-resume semula — Dreeve resumable by design, tak ada data loss.
- Route ditambah di Cloudflare: Published application, hostname `trainlog.brodynhq.com` → Service URL `http://app:8080`. DNS CNAME auto-created oleh Cloudflare.
- **Confirmed public & jalan:** `curl https://trainlog.brodynhq.com/dashboard` dan `http://` versi dua-dua return **HTTP 200** dari luar. SSL automatik dari Cloudflare.
- **Scheduled actions diaktifkan:** "Import Strava data" (cron `30 2 * * *`, daily 2:30am) di-tick & save di Admin → Settings → Scheduled actions. Daemon container di-restart (`docker compose restart daemon` je, bukan `up -d`, supaya tak ganggu import manual yang masih jalan di container `app`). Lepas ni, aktiviti baru akan auto-import setiap hari tanpa perlu run command manual.
- ⚠️ Tunnel token sempat ter-paste dalam chat — token **dah di-rotate** di Cloudflare dashboard. Nota teknikal: lepas rotate, Overview page Cloudflare tak terus tunjuk token baru — kena klik "Add a replica" untuk bukak balik screen install (pilih OS "Docker") yang papar token semasa.
- **Pelajaran penting (lock mutex + docker recreate):**
  - `docker compose up -d` (tanpa `--no-deps`) boleh **recreate container `app`** walaupun kita cuma nak ubah service lain (contoh `cloudflared`) — ini SIGKILL proses `docker compose exec app ...` yang sedang jalan (import). Guna `docker compose up -d --no-deps --force-recreate <service>` untuk elak ni.
  - `docker compose restart daemon` (walaupun tak recreate container `app`) pun boleh sebabkan import gagal dengan error **"Cannot heartbeat: lock importData does not exist"** — rupanya lock mutex import dikongsi/reset bila daemon restart. Import kena di-resume manual lepas ni (resumable by design, takde data hilang, cuma kena re-run).
  - Import ter-interrupt 3 kali sepanjang proses (recreate app, restart daemon, recreate app lagi semasa fix token) — setiap kali resume semula dari mana ia berhenti (591 → terus) tanpa masalah.
  - **Root cause lock mechanism (confirmed dari source `Mutex.php`):** lock import disimpan dalam DB table `KeyValue` dengan heartbeat timestamp, auto-expire (jadi "stale") lepas **20 minit** (`STALE_THRESHOLD_IN_SECONDS = 1200`) tanpa heartbeat. Bila proses import kena SIGKILL (container recreate), `register_shutdown_function` yang patut release lock **tak sempat jalan**, so lock tertinggal "hidup" sampai 20 minit. Percubaan resume serta-merta lepas SIGKILL akan dapat mesej **"Postponing Strava import, another process is importing data"** — ni **expected & self-healing**, bukan error — tunggu ~20 minit dari interruption terakhir, retry akan jalan normal. Tak perlu manual clear apa-apa.
  - **Keputusan:** 2 percubaan retry manual lepas interruption masih kena "Postponing" (lock belum stale). Decide **biar cron auto-import (2:30am) handle** sambungan backfill — time tu lock dah pasti stale, tak perlu polling/retry manual lagi malam ni.

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
