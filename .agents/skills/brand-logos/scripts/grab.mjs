// node grab.mjs <outDir> token=url[|css selector] ...
// Finds the site logo (home link / [class*=logo] in the top area) and saves
//   <token>.svg / <token>.inline.svg  (standalone SVG file / inline svg of the logo)
//   <token>.src.img  (original file of a single logo image)
//   <token>.png  (element screenshot, 3x, transparent: everything else hidden, no backgrounds)
//   <token>-page.png (top of the page for review)
import fs from 'node:fs';
import path from 'node:path';
import puppeteer from 'puppeteer-core';

const [outDir, ...jobs] = process.argv.slice(2);
fs.mkdirSync(outDir, { recursive: true });

const browser = await puppeteer.launch({
    executablePath: process.env.CHROME_PATH ?? 'C:/Program Files/Google/Chrome/Application/chrome.exe',
    headless: true,
    args: ['--lang=de-DE,de', '--no-first-run', '--ignore-certificate-errors'],
});

for (const job of jobs) {
    // token=url or token=url|css selector of the logo
    const [token, rest] = job.split(/=(.*)/s);
    const [url, selector] = rest.split('|');
    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 900, deviceScaleFactor: 3 });
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36');
    try {
        await page.goto(url, { waitUntil: 'networkidle2', timeout: 45000 }).catch(() => {});
        await new Promise((r) => setTimeout(r, 2500));

        // hide cookie banners and other overlays that are not part of the header
        await page.evaluate(() => {
            for (const el of document.querySelectorAll('body *')) {
                const cs = getComputedStyle(el);
                const id = (el.id + ' ' + el.className).toString().toLowerCase();
                if (/cookie|consent|gdpr|cmp|usercentrics|onetrust|borlabs|klaro|modal|popup|newsletter/.test(id) && !/logo/.test(id)) {
                    el.style.setProperty('display', 'none', 'important');
                } else if ((cs.position === 'fixed' || cs.position === 'sticky') && el.getBoundingClientRect().top > 200) {
                    el.style.setProperty('display', 'none', 'important');
                }
            }
            document.documentElement.style.overflow = 'auto';
            document.body.style.overflow = 'auto';
        });

        await page.screenshot({ path: path.join(outDir, `${token}-page.png`), clip: { x: 0, y: 0, width: 1440, height: 400 } });

        const handle = await page.evaluateHandle((token, selector) => {
            if (selector) return document.querySelector(selector);
            const visible = (el) => {
                const r = el.getBoundingClientRect();
                const cs = getComputedStyle(el);
                return r.width > 20 && r.height > 10 && r.top < 260 && r.top > -5 && cs.visibility !== 'hidden' && cs.display !== 'none' && +cs.opacity > 0.1;
            };
            const attrs = (el) => [el.id, el.getAttribute('class'), el.getAttribute('alt'), el.getAttribute('src'), el.getAttribute('aria-label'), el.getAttribute('title'), el.getAttribute('href')].join(' ').toLowerCase();
            const home = (a) => {
                try {
                    const u = new URL(a.href, location.href);
                    return u.host.replace(/^www\./, '') === location.host.replace(/^www\./, '') && /^\/?(index\.\w+|de|en|de\/|en\/)?$/.test(u.pathname) && !u.hash;
                } catch { return false; }
            };
            const cands = [...document.querySelectorAll('a, [class*=logo i], [id*=logo i], img, svg, picture')].filter(visible);
            let best = null, bestScore = -1;
            for (const el of cands) {
                const r = el.getBoundingClientRect();
                if (r.width > 700 || r.height > 220) continue;
                const a = attrs(el);
                let s = 0;
                if (/logo|brand/.test(a)) s += 4;
                if (a.includes(token)) s += 3;
                if (el.tagName === 'A' && home(el)) s += 4;
                if (el.closest('header, nav, [class*=header i], [role=banner]')) s += 2;
                if (el.querySelector('img, svg') || /^(IMG|SVG|PICTURE)$/i.test(el.tagName)) s += 2;
                if (r.left < 500) s += 1;
                if (r.top < 150) s += 1;
                if (s > bestScore) { bestScore = s; best = el; }
            }
            // prefer the home link that wraps the logo (icon + wordmark)
            const link = best?.closest('a');
            if (link && home(link) && visible(link) && link.getBoundingClientRect().width < 700) best = link;
            return best;
        }, token, selector);

        const el = handle.asElement();
        if (!el) { console.log(`${token}: no logo found`); continue; }

        const info = await el.evaluate((el) => {
            const imgs = [...el.querySelectorAll('img')].concat(el.tagName === 'IMG' ? [el] : []);
            const svgs = [...el.querySelectorAll('svg')].concat(el.tagName.toLowerCase() === 'svg' ? [el] : []);
            const text = el.innerText?.trim() ?? '';
            return {
                tag: el.tagName, cls: el.getAttribute('class'), text,
                img: imgs.map((i) => i.currentSrc || i.src),
                svgCount: svgs.length,
                svg: svgs.length === 1 && !imgs.length && !text ? svgs[0].outerHTML : null,
            };
        });

        // standalone SVG file
        const svgSrc = info.img.find((s) => /\.svg(\?|$)/i.test(s));
        if (svgSrc && info.img.length === 1 && !info.text) {
            const body = await page.evaluate(async (s) => (await fetch(s)).text(), svgSrc).catch(() => null);
            if (body?.includes('<svg')) fs.writeFileSync(path.join(outDir, `${token}.svg`), body);
        } else if (info.svg) {
            fs.writeFileSync(path.join(outDir, `${token}.inline.svg`), info.svg);
        }

        // original raster file of a single logo image (full resolution, no CSS filters)
        const raster = info.img.length === 1 && !info.text && !svgSrc ? info.img[0] : null;
        if (raster) {
            const bytes = await page.evaluate(async (s) => {
                const r = await fetch(s);
                return [...new Uint8Array(await r.arrayBuffer())];
            }, raster).catch(() => null);
            if (bytes) fs.writeFileSync(path.join(outDir, `${token}.src.img`), Buffer.from(bytes));
        }

        // only the logo stays visible: siblings along the way up hidden, all backgrounds above it transparent
        await el.evaluate((el) => {
            for (let node = el; node && node !== document.documentElement; node = node.parentElement) {
                for (const sib of node.parentElement?.children ?? []) {
                    if (sib !== node) sib.style.setProperty('visibility', 'hidden', 'important');
                }
                const parent = node.parentElement;
                if (parent) {
                    parent.style.setProperty('background', 'transparent', 'important');
                    parent.style.setProperty('box-shadow', 'none', 'important');
                    parent.style.setProperty('backdrop-filter', 'none', 'important');
                }
            }
            el.style.setProperty('background', 'transparent', 'important');
            for (const pseudo of document.querySelectorAll('body *')) {
                if (!el.contains(pseudo) && !pseudo.contains(el)) pseudo.style.setProperty('visibility', 'hidden', 'important');
            }
        });
        await page.evaluate(() => document.documentElement.style.setProperty('background', 'transparent', 'important'));
        await el.screenshot({ path: path.join(outDir, `${token}.png`), omitBackground: true });
        console.log(`${token}: ${info.tag}.${info.cls ?? ''} text="${info.text.slice(0, 40)}" img=${info.img.join(',').slice(0, 120)} svg=${info.svgCount}`);
    } catch (e) {
        console.log(`${token}: ERROR ${e.message}`);
    } finally {
        await page.close();
    }
}

await browser.close();
