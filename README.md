# Portfolio Website

Node.js portfolio for Alvi Ador, focused on cybersecurity interest, software projects, and web development work.

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

## Includes

## Includes

- Cybersecurity-focused hero section
- GitHub, LinkedIn, Discord, and Reddit links from the public GitHub profile
- Contact email: `sarker.faizal2537@gmail.com`
- Project sections for ANS Hospital, Job Portal, DX-Ball, Mobile Shop Management, and related work
- Project visuals from available local project assets
- Cookie preference banner for theme and display settings
- Node.js route handling for all site pages
- Example contact API endpoint at `/api/contact`

LinkedIn profile content is not copied because the public page redirects to LinkedIn's auth wall without login access.

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
