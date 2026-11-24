#!/usr/bin/env python3
"""Validate every article in articles/<slug>/ before pushing.

Usage: python3 validate.py            (all articles)
       python3 validate.py <slug>     (one article)

Exits non-zero if any article breaks a mechanical rule. Editorial rules
(facts, tone, no unverifiable claims) are in CONTENT_GUIDE.md.
"""
import json
import pathlib
import re
import sys

ROOT = pathlib.Path(__file__).parent
ARTICLES = ROOT / "articles"
SITE = "https://indonesiabriquettescharcoal.net"
REQUIRED = ["slug", "date", "title", "seo_title", "description", "excerpt",
            "primary_keyword", "keywords", "image"]
CATALOG = {c["file"]: c for c in json.loads((ROOT / "images" / "catalog.json").read_text())}
BANNED_TAGS = re.compile(r"<\s*(script|style|iframe|object|embed|form|input|h1)\b", re.I)
EVENT_ATTR = re.compile(r"\son[a-z]+\s*=", re.I)


def words(html):
    return re.findall(r"[A-Za-z0-9'%–-]+", re.sub(r"<[^>]+>", " ", html))


def check(folder):
    errs, warns = [], []
    meta_p, body_p = folder / "meta.json", folder / "body.html"
    if not meta_p.exists() or not body_p.exists():
        return [f"missing meta.json or body.html"], warns
    meta = json.loads(meta_p.read_text())
    body = body_p.read_text()
    for k in REQUIRED:
        if not meta.get(k):
            errs.append(f"meta.{k} missing")
    if errs:
        return errs, warns
    slug, kw = meta["slug"], meta["primary_keyword"].lower()
    if slug != folder.name:
        errs.append("slug must equal folder name")
    if not re.fullmatch(r"[a-z0-9]+(-[a-z0-9]+)*", slug) or len(slug) > 70:
        errs.append("slug: lowercase words joined by hyphens, max 70 chars")
    if not re.fullmatch(r"\d{4}-\d{2}-\d{2}", meta["date"]):
        errs.append("date must be YYYY-MM-DD")
    if len(meta["title"]) > 70:
        errs.append("title > 70 chars")
    if len(meta["seo_title"]) > 60:
        errs.append("seo_title > 60 chars")
    if not 120 <= len(meta["description"]) <= 160:
        errs.append(f"description must be 120-160 chars (is {len(meta['description'])})")
    if meta["image"] not in CATALOG or not (ROOT / meta["image"]).is_file():
        errs.append(f"image must be one of images/catalog.json (got {meta['image']})")
    if "<img" in body.lower():
        errs.append("no <img> in body.html; use meta.image (featured image) instead")
    if len(meta["excerpt"]) > 160:
        errs.append("excerpt > 160 chars")
    if BANNED_TAGS.search(body) or EVENT_ATTR.search(body):
        errs.append("body has forbidden HTML (script/style/iframe/form/h1/on* attributes)")
    w = words(body)
    n = len(w)
    if not 700 <= n <= 1500:
        errs.append(f"body must be 700-1500 words (is {n})")
    text = " ".join(w).lower()
    count = text.count(kw)
    first100 = " ".join(w[:100]).lower()
    for place, ok in [("title", kw in meta["title"].lower()),
                      ("seo_title", kw in meta["seo_title"].lower()),
                      ("description", kw in meta["description"].lower()),
                      ("first 100 words", kw in first100)]:
        if not ok:
            errs.append(f"primary keyword not in {place}")
    if count < 4:
        errs.append(f"primary keyword appears only {count}x (min 4)")
    if n and count / n > 0.025:
        errs.append(f"primary keyword density too high ({count}/{n})")
    heads = re.findall(r"<h[23][^>]*>(.*?)</h[23]>", body, re.S)
    if not any(kw in re.sub(r"<[^>]+>", "", h).lower() for h in heads):
        warns.append("primary keyword not in any subheading")
    internal = set(re.findall(r'href="(' + re.escape(SITE) + r'[^"]*)"', body))
    if len(internal) < 3:
        errs.append(f"needs >= 3 different internal links (has {len(internal)})")
    if not any("/wholesale-" in u for u in internal):
        errs.append("must link to a /wholesale-... product page")
    if f"{SITE}/contact/" not in internal:
        errs.append("must link to /contact/")
    if "Frequently asked questions" not in body:
        warns.append("no 'Frequently asked questions' section")
    return errs, warns


def main():
    only = sys.argv[1] if len(sys.argv) > 1 else None
    folders = sorted(p for p in ARTICLES.iterdir() if p.is_dir())
    seen_kw, bad = {}, 0
    for f in folders:
        meta_p = f / "meta.json"
        if meta_p.exists():
            k = json.loads(meta_p.read_text()).get("primary_keyword", "").lower()
            if k in seen_kw:
                print(f"ERROR {f.name}: primary keyword already used by {seen_kw[k]}")
                bad += 1
            seen_kw[k] = f.name
        if only and f.name != only:
            continue
        errs, warns = check(f)
        for e in errs:
            print(f"ERROR {f.name}: {e}")
        for w in warns:
            print(f"WARN  {f.name}: {w}")
        if not errs:
            print(f"OK    {f.name}")
        bad += len(errs)
    sys.exit(1 if bad else 0)


if __name__ == "__main__":
    main()
