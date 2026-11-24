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
- Belum confirm: user dah tekan "Create" app di Strava ke belum.

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
