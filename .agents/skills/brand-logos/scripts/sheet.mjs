// node sheet.mjs <dir> <out.png> [filter]  -> contact sheet of PNG/SVG files on the card background
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import puppeteer from 'puppeteer-core';

const [dir, out, filter = ''] = process.argv.slice(2);
const files = fs.readdirSync(dir).filter((f) => /\.(png|svg)$/.test(f) && (filter ? f.includes(filter) : !f.includes('-page'))).sort();
const cells = files.map((f) => `<figure><div><img src="${pathToFileURL(path.join(dir, f))}"></div><figcaption>${f}</figcaption></figure>`).join('');
const html = `<style>body{margin:0;font:12px sans-serif;display:grid;grid-template-columns:repeat(4,1fr);gap:6px;padding:6px;background:#fff;width:1400px}
figure{margin:0;background:#ececf0;border-radius:6px;padding:6px}div{height:110px;display:grid;place-items:center}img{width:250px}</style>${cells}`;
const sheet = path.join(dir, '_sheet.html');
fs.writeFileSync(sheet, html);

const b = await puppeteer.launch({ executablePath: process.env.CHROME_PATH ?? 'C:/Program Files/Google/Chrome/Application/chrome.exe', args: ['--allow-file-access-from-files'] });
const p = await b.newPage();
await p.setViewport({ width: 1412, height: 400 });
await p.goto(pathToFileURL(sheet).href, { waitUntil: 'networkidle0' });
await p.screenshot({ path: out, fullPage: true });
await b.close();
