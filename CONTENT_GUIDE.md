# Content Guide — indonesiabriquettescharcoal.net News & Insights

Every new article MUST follow this guide. `validate.py` enforces the mechanical rules; the editorial rules are your responsibility.

## Goal
Bring international B2B buyers (importers, distributors, wholesalers, private-label brand owners) to indonesiabriquettescharcoal.net from Google and turn them into quote requests. Each article targets **one primary keyword** a buyer would really type into Google, plus 2–4 supporting keywords.

## Schedule
Two articles per week (Monday and Thursday). Quality over quantity: if you cannot write a genuinely useful, non-duplicative article, skip and report why.

## Article format (one folder per article)
`articles/<slug>/meta.json`
```json
{
  "slug": "same-as-folder-name",
  "date": "YYYY-MM-DD",
  "title": "H1 title, <=70 chars, primary keyword near the start",
  "seo_title": "<=60 chars incl. primary keyword (site adds ' | PT Dharma Artha Nusantara')",
  "description": "120-160 chars meta description incl. primary keyword, invites the click",
  "excerpt": "<=160 chars summary",
  "primary_keyword": "...",
  "keywords": ["secondary 1", "secondary 2"],
  "sources": ["https://... pages actually opened"],
  "image": "images/<file>.jpg  (REQUIRED, from images/catalog.json)",
  "image_alt": "optional: alt text if the catalogue alt does not fit this article"
}
```
`articles/<slug>/body.html` — plain HTML only: `<p> <h2> <h3> <ul> <ol> <li> <table> <thead> <tbody> <tr> <th> <td> <a> <strong> <em>`. No `<h1>` (the title is the H1), no CSS/scripts/images.

## Featured image (required)
Every article sets `"image"` to ONE photo from `images/catalog.json` (real photos taken at PT DAN's production partner in Indonesia, Sept 2026). The server adds it to the WordPress Media Library and sets it as the featured image (also used as the share preview).
- Pick the photo that best matches the topic; prefer one not used by the latest 3 articles.
- Captions/text must never claim the photo shows PT DAN's own factory — it is a **production partner** facility.
- Never add new photos, people's faces, documents or other brands' packaging. Never put `<img>` in body.html.

| File | Shows |
| --- | --- |
| images/coconut-charcoal-cubes-drying-tray.jpg | cubes on a wooden drying tray |
| images/coconut-charcoal-cube-burning-check.jpg | cube glowing on an electric heater (burning check) — used 2026-11-23 |
| images/coconut-charcoal-cubes-cutting-line.jpg | freshly cut cubes on the cutting line |
| images/coconut-charcoal-cube-cutting-machine.jpg | cube cutting machine |
| images/coconut-charcoal-cube-weight-check.jpg | single cube on a digital scale |
| images/coconut-shell-charcoal-raw-material-weighing.jpg | sack of raw coconut shell charcoal on a scale |
| images/coconut-charcoal-mixing-machine.jpg | mixer blending charcoal powder with binder |
| images/coconut-charcoal-briquette-extruder.jpg | extruder pressing charcoal strands |
| images/coconut-charcoal-drying-racks.jpg | drying racks with trays |
| images/coconut-charcoal-briquettes-stacked-trays.jpg | stacked trays of briquettes on a cart |
| images/coconut-charcoal-briquette-drying-oven.jpg | tray going into a drying oven |
| images/coconut-charcoal-packed-pallets-warehouse.jpg | shrink-wrapped pallets in the warehouse |
| images/coconut-charcoal-briquette-production-hall.jpg | production hall overview |
| images/coconut-shell-charcoal-grinding-machine.jpg | grinding machine and cyclone |
| images/coconut-charcoal-final-inspection-area.jpg | "Final inspection area" sign on the line — used 2026-11-26 |

## SEO rules
1. Keyword research first: check how buyers search (top results, "People also ask"), pick 2–4 secondary keywords and natural variants (shisha/hookah charcoal, coconut charcoal/coconut shell charcoal briquettes, supplier/exporter from Indonesia, wholesale/bulk).
2. Primary keyword (exact or very close) in: title, seo_title, slug, first 100 words, at least one `<h2>`, the conclusion, description and excerpt. Density about 0.8–1.5% (6–12× in 900 words). Never keyword stuffing — sentences must read naturally.
3. 800–1,200 words, short paragraphs (<=3 sentences), descriptive keyword subheadings (not slogans), at least one list or table that answers the question directly.
4. `<h2>Frequently asked questions</h2>` with 3–4 real buyer questions as `<h3>` + 2–3 sentence answers.
5. Internal links with keyword-rich anchor text (never "click here"), some inside the body:
   - shisha topics → `/wholesale-shisha-hookah-charcoal-briquettes/` (e.g. "wholesale coconut shisha charcoal from Indonesia"); BBQ → `/wholesale-bbq-charcoal-briquettes/`; general → both;
   - plus 2–4 of `/specifications/`, `/packaging/`, `/order-shipping/`, `/quality-process/`, published articles;
   - always end with a link to `/contact/` (e.g. "request a coconut charcoal quotation").
   - 1–2 outbound links to authoritative sources when general facts are cited (ICC, ASTM, IMO, FAO…).
6. No cannibalisation: each primary keyword is used by ONE article only (validate.py checks the repo; also check published post titles). "wholesale shisha charcoal" and "wholesale BBQ charcoal briquettes" belong to the product pages — articles only link to them.
7. End with `<h2>Related buyer resources</h2>` (3–5 links) and `<h2>Conclusion</h2>`; the last paragraph says final specification, packaging, documents and terms are confirmed in PT DAN's written quotation, with the /contact/ link.

## Facts you may state (source of truth)
- Company: PT Dharma Artha Nusantara (PT DAN), Jakarta Timur, Indonesia — an export trader/coordinator working with selected production partners. NEVER call PT DAN a factory or manufacturer.
- Product specifications: ONLY the values on https://indonesiabriquettescharcoal.net/specifications/ (read the page first). Shisha Premium: 100% coconut shell charcoal, moisture max 8%, ash 1.8–2.5%, fixed carbon min 75%, volatile matter 13–15%, >7,300 kcal/kg. Shisha Medium: ash 2.5–3.0%, FC min 72%, >7,000 kcal/kg. BBQ Basic: hardwood charcoal mixed with coconut charcoal granules, ash 8–15%, >6,500 kcal/kg. Cubes 22/25/26/28 mm. Values are "published production-partner catalogue specifications"; final values agreed per order.
- Do NOT state PT DAN's burn time until it appears on /specifications/.
- PT DAN quotes at most on FOB terms (explain other Incoterms only in general).
- Never: client names, shipment counts, factory capacity, certificates (ISO etc.), prices, testimonials, or market data without a dated source.
- General facts (Incoterms, HS codes, test standards, shipping rules, market data) must be verified from official/reliable sources; paraphrase, never copy sentences.

## Topic bank (topic → suggested primary keyword)
Mark used topics in `topics-used.md`.
1. Natural coconut shisha charcoal vs quick-light charcoal → natural coconut shisha charcoal (DONE 2026-11-23)
2. How to vet an Indonesian coconut charcoal supplier (due-diligence checklist) → coconut charcoal supplier Indonesia (DONE 2026-11-26)
3. Hardwood vs coconut shell BBQ briquettes: buyer comparison → coconut BBQ briquettes
4. Keeping coconut charcoal dry from factory to container → coconut charcoal moisture
5. What ash colour and ash content tell shisha charcoal buyers → shisha charcoal ash content
6. HS codes and export documents for coconut charcoal briquettes from Indonesia → coconut charcoal HS code
7. Why hexagonal BBQ briquettes have a central hole → hexagonal charcoal briquettes
8. Lab tests to request for coconut charcoal and common test methods → coconut charcoal lab test
9. Shipping charcoal by sea: dangerous-goods questions importers should ask → charcoal dangerous goods shipping
10. Planning a first order for a private-label shisha charcoal brand → private label shisha charcoal
11. How many cartons fit in a 20ft container (approximate, confirm per packaging) → coconut charcoal container load
12. Pre-shipment inspection for coconut charcoal → charcoal pre-shipment inspection
13. Writing an RFQ for shisha charcoal → shisha charcoal RFQ
14. Payment terms in the charcoal trade: T/T deposit vs letter of credit (general, no PT DAN terms) → charcoal import payment terms
15. BBQ briquettes for retail vs foodservice buyers → BBQ briquettes for foodservice
16. Coconut charcoal demand trends in the Middle East and Europe (dated, sourced data only) → coconut charcoal market
