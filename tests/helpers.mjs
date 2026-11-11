/** Shared real-service test utilities. Never log secrets, order keys or full API bodies. */
import assert from 'node:assert/strict';
import path from 'node:path';
import fs from 'node:fs';
import {fileURLToPath} from 'node:url';
import {spawnSync} from 'node:child_process';
export const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
export function wp(file, ...args) {
  const result = spawnSync(process.env.PYTHON || 'python', ['scripts/dev.py', 'wp', '--', 'eval-file', `/opt/anotter/${file}`, ...args], {cwd:root, encoding:'utf8', timeout:180000});
  assert.equal(result.status, 0, `CLI failed: ${file}; inspect private local diagnostics`);
  return result.stdout;
}
export function sentinel(output, name) {
  const line = output.split(/\r?\n/).find(line => line.startsWith(name+'='));
  assert.ok(line, `Missing ${name}`);
  return JSON.parse(line.slice(name.length+1));
}
export function outputDir(name) {
  const dir = path.resolve(root, 'work', 'm05', name);
  fs.mkdirSync(dir, {recursive:true});
  return dir;
}
export async function until(fn, message, timeout=30000) {
  const deadline=Date.now()+timeout;
  while(Date.now()<deadline) {if(await fn())return;await new Promise(r=>setTimeout(r,200));}
  throw Error(message);
}
export async function hydrate(page, kind) {
  await page.evaluate(()=>document.fonts.ready);
  if(kind==='cart')await page.locator('.wc-block-cart-items__row .wc-block-components-product-name').first().waitFor();
  if(kind==='checkout')await page.locator('#email').waitFor();
  await page.waitForLoadState('networkidle');
}

export async function capture(page, file) {
  // Trigger actual lazy loading before exporting a full page; blank lazy media is not visual evidence.
  await page.evaluate(async()=>{
    for(let y=0;y<document.documentElement.scrollHeight;y+=innerHeight*.8){scrollTo(0,y);await new Promise(r=>setTimeout(r,60));}
    scrollTo(0,0);
  });
  await page.waitForLoadState('networkidle');
  await page.locator('main img').evaluateAll(es=>Promise.all(es.map(e=>e.decode().catch(()=>{}))));
  await page.screenshot({path:file,fullPage:true});
  await page.screenshot({path:file.replace(/\.png$/,'-top.png'),fullPage:false});
}
