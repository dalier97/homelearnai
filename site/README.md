# HomeLearnAI Website

This is the marketing website for HomeLearnAI deployed on Cloudflare Pages.

## 🚀 Deployment

### Automatic Deployment
The site is deployed to: **https://7625b0dc.homelearnai.pages.dev**

### Manual Deployment
```bash
npm run deploy
```

### Development
```bash
npm run dev
```

## 📁 Project Structure

```
site/
├── index.html          # Main landing page
├── _headers            # Cloudflare Pages headers config
├── _redirects          # URL redirects configuration
├── wrangler.toml       # Cloudflare Workers/Pages config
├── package.json        # Dependencies and scripts
└── README.md          # This file
```

## ⚙️ Configuration Files

- **wrangler.toml**: Cloudflare Pages configuration
- **_headers**: Security headers and caching rules
- **_redirects**: URL redirect rules (GitHub, docs, etc.)

## 🌐 Custom Domain Setup

To connect your `homelearnai.com` domain:

1. Go to [Cloudflare Dashboard](https://dash.cloudflare.com)
2. Navigate to Pages > homelearnai project
3. Go to Custom domains tab
4. Add `homelearnai.com` and `www.homelearnai.com`
5. Update DNS records as instructed

## 🔧 NPM Scripts

- `npm run deploy` - Deploy to Cloudflare Pages
- `npm run dev` - Start local development server
- `npm run preview` - Deploy preview version

## 🛡️ Security Features

- CSP headers for XSS protection
- Asset caching for performance
- Frame protection and content type sniffing prevention
- Secure referrer policy

## 📈 Performance

- CDN-delivered assets (Tailwind, Alpine.js)
- Optimized images and fonts
- Cache-first strategy for static assets
- Minimal JavaScript footprint