# Portfolio Website

Node.js portfolio for Md. Faizal Alvi Sarker, focused on cybersecurity, machine learning, software engineering, and public project work.

## Runtime

- Node.js 18+
- Express server (`server.js`)
- Vercel deployment (`vercel.json`)

## Run locally

```bash
npm install
npm run dev
```

Open `http://localhost:3000`.

## Deploy to Vercel

1. Push this repository to GitHub.
2. Import the repo in Vercel.
3. Keep framework preset as `Other`.
4. Build settings are auto-detected from `vercel.json`.
5. Deploy.

Optional CLI deploy:

```bash
npm i -g vercel
vercel
vercel --prod
```

## Public profile snapshot

- Name: Md. Faizal Alvi Sarker
- Education: BSc in Computer Science & Engineering at American International University-Bangladesh, 2022-2026
- Focus: Cybersecurity, machine learning, full-stack development, and practical software engineering
- Leadership: Co-Founder at Jersey Stall BD
- Certifications: Cisco IT Essentials, IELTS 7.0

## Includes

- LinkedIn-aligned hero and about copy
- GitHub project highlights for ANS Hospital Management System, DX-Ball, Mobile Shop Management System, Sales Intelligence Data Analysis, CNN Development on Custom Dataset, and the portfolio website itself
- Thumbnail-led project cards with consistent image shapes, overlays, and icon badges
- Social links for GitHub and LinkedIn
- Contact email: `sarker.faizal2537@gmail.com`
- Project visuals from available local project assets
- Cookie preference banner for theme and display settings
- Node.js route handling for all site pages
- Example contact API endpoint at `/api/contact`

## Assets reorganization

I reorganized image and asset files to make the repo easier to maintain:

- Stylesheet: `assets/css/styles.css` (single consolidated stylesheet)
- JavaScript: `assets/js/app.js` (main site behavior)
- Images: moved into `assets/img/` with subfolders:
	- `assets/img/screens/` — project screenshots and UI images
	- `assets/img/visuals/` — illustrative visuals
	- `assets/img/` — misc images

Update notes:
- HTML pages were updated to reference the new image paths.
- Duplicate legacy files `assets/styles.css` and `assets/app.js` were removed.

If you'd like a different layout (for example `assets/images/` instead of `assets/img/`), I can rename everything consistently and update references.
