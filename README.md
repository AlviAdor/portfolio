# Portfolio Website

Static portfolio for Alvi Ador, focused on cybersecurity interest, software projects, and web development work.

## Includes

- Cybersecurity-focused hero section
- GitHub, LinkedIn, Discord, and Reddit links from the public GitHub profile
- Contact email: `sarker.faizal2537@gmail.com`
- Project sections for ANS Hospital, Job Portal, DX-Ball, Mobile Shop Management, and related work
- Project visuals from available local project assets
- Cookie preference banner for theme and display settings

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
