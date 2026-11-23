# indonesiabriquettes-news

Source of the News & Insights articles of https://indonesiabriquettescharcoal.net (PT Dharma Artha Nusantara, WordPress).

- `articles/<slug>/meta.json` + `body.html` — one folder per article
- `CONTENT_GUIDE.md` — keywords, allowed facts, SEO and editorial rules (read before writing)
- `topics-used.md` — log of topics / primary keywords already used
- `validate.py` — checks every article before pushing (`python3 validate.py`)
- `settings.json` — `post_status` for NEW posts: `"draft"` (owner reviews and clicks Publish) or `"publish"` (goes live automatically)
- `deploy/` — reference copies of the hourly cron job on the hosting server

Publishing flow: add `articles/<slug>/` → `python3 validate.py` → commit → push to `main`.
The hosting server pulls `main` every hour (minute 23) and creates a WordPress post for every slug that does not exist yet.
It never edits, unpublishes or deletes existing posts or pages — to change a published article, edit it in WordPress.
