/** Real browser acceptance tests. Uses current WordPress/WooCommerce data, no commerce mocks. */
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { createRequire } from 'node:module';
import { spawnSync } from 'node:child_process';
const require = createRequire(import.meta.url);
const { chromium } = require('playwright');
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const output = path.resolve(process.env.SCREENSHOT_DIR || path.join(root, 'work', 'screenshots'));
const screenshotsOnly = process.argv.includes('--screenshots-only');
const results = [];
const errors = [];
function wp(file, ...args) {
  const result = spawnSync(process.env.PYTHON || 'python', ['scripts/dev.py', 'wp', '--', 'eval-file', `/opt/anotter/tests/${file}`, ...args], {cwd:root,encoding:'utf8',timeout:120000});
  assert.equal(result.status, 0, `${file} failed: ${result.stderr || result.stdout}`);
  return result.stdout;
}
function sentinel(text, name) {
  const line = text.split(/\r?\n/).find(line => line.startsWith(name + '='));
  assert.ok(line, `Missing ${name} output`);
  return JSON.parse(line.slice(name.length + 1));
}
function pass(name) { results.push(name); console.log(`PASS: ${name}`); }
async function waitUntil(fn, message, timeout=20000) {
  const end = Date.now() + timeout;
  while (Date.now() < end) { if (await fn()) return; await new Promise(resolve=>setTimeout(resolve,300)); }
  throw new Error(message);
}
const data = sentinel(wp('storefront-data.php'), 'ANOTTER_E2E');
const base = new URL(data.pages.home).origin;
assert.equal(data.currency,'UYU');
fs.mkdirSync(output,{recursive:true});
const browser = await chromium.launch({headless:true});
const context = await browser.newContext({viewport:{width:1440,height:1000},reducedMotion:'reduce'});
const page = await context.newPage();
page.on('pageerror', error=>errors.push(error.message));
page.on('console', message=>{ if (message.type()==='error') errors.push(message.text()); });
let stockFixture=false;
let failure=null;
async function visit(url) {
  const response=await page.goto(url,{waitUntil:'networkidle',timeout:60000});
  assert.equal(response.status(),200,`HTTP ${response.status()} ${url}`);
  await page.evaluate(()=>document.fonts.ready);
  assert.equal(await page.locator('main').count(),1,'Exactly one main landmark');
  assert.equal(await page.locator('h1').count(),1,'Exactly one H1');
  assert.ok((await page.locator('body').innerText()).includes('DEMO INDEPENDIENTE'));
}
async function cart() { const response=await context.request.get(base+'/wp-json/wc/store/v1/cart'); assert.equal(response.status(),200); return response.json(); }
async function prepareScreenshot() {
  // Scroll naturally so below-the-fold lazy media is present in exported full-page captures.
  await page.evaluate(async()=>{
    for (let y=0;y<document.documentElement.scrollHeight;y+=window.innerHeight*.8) {
      window.scrollTo(0,y); await new Promise(resolve=>setTimeout(resolve,80));
    }
    window.scrollTo(0,0);
  });
  await page.waitForLoadState('networkidle');
  await page.locator('main img').evaluateAll(images=>Promise.all(images.map(image=>image.decode().catch(()=>{}))));
}
try {
  for (const width of [320,375,768,1024,1440]) {
    await page.setViewportSize({width,height:1000});
    for (const name of ['home','shop','product','how','faq','contact']) {
      await visit(data.pages[name]);
      const dimensions=await page.evaluate(()=>({viewport:document.documentElement.clientWidth,scroll:document.documentElement.scrollWidth}));
      assert.ok(dimensions.scroll<=dimensions.viewport+1,`${name} overflows at ${width}: ${JSON.stringify(dimensions)}`);
      const broken=await page.locator('main img').evaluateAll(images=>images.filter(image=>image.complete && image.naturalWidth===0).map(image=>image.src));
      assert.deepEqual(broken,[],`Broken images on ${name}`);
      if ([375,1440].includes(width) && ['home','shop','product'].includes(name)) {
        await prepareScreenshot();
        await page.screenshot({path:path.join(output,`${name}-${width}.png`),fullPage:true});
        if (name==='home') await page.screenshot({path:path.join(output,`${name}-${width}-top.png`)});
      }
    }
    pass(`Six pages, landmarks, images and no horizontal overflow at ${width}px`);
  }
  if (!screenshotsOnly) {
    await page.setViewportSize({width:1440,height:1000});
    await visit(data.pages.home);
    const links=await page.locator('header nav a, footer a').evaluateAll(nodes=>[...new Set(nodes.map(node=>node.href))]);
    for (const url of links) {
      assert.equal(new URL(url).origin,base,'Demo navigation must stay on the demo');
      const response=await context.request.get(url); assert.equal(response.status(),200,`Broken navigation link ${url}`);
    }
    pass('Header, footer and legal navigation links return HTTP 200');
    const hero=page.locator('.aot-hero img');
    assert.equal(await hero.getAttribute('loading'),'eager');
    assert.equal(await hero.getAttribute('fetchpriority'),'high');
    pass('Primary hero image loads eagerly at high priority');

    await page.setViewportSize({width:375,height:844});
    const open=page.locator('header .wp-block-navigation__responsive-container-open');
    assert.equal(await open.getAttribute('aria-expanded'),'false');
    assert.equal(await open.getAttribute('aria-label'),'Abrir menú');
    await open.focus(); await page.keyboard.press('Enter');
    const menu=page.locator('header .wp-block-navigation__responsive-container.is-menu-open');
    await menu.waitFor({state:'visible'});
    assert.equal(await open.getAttribute('aria-expanded'),'true');
    assert.ok(await menu.getByRole('link',{name:'Tienda',exact:true}).isVisible());
    assert.ok(await page.evaluate(()=>document.querySelector('header .is-menu-open').contains(document.activeElement)),'Menu must receive keyboard focus');
    await page.keyboard.press('Escape');
    await menu.waitFor({state:'hidden'});
    assert.equal(await open.getAttribute('aria-expanded'),'false');
    assert.ok(await open.evaluate(element=>element===document.activeElement),'Closing menu must restore focus');
    pass('Mobile navigation opens by keyboard, receives focus and restores focus on Escape');

    await page.setViewportSize({width:1440,height:1000});
    await visit(data.pages.shop);
    assert.ok(await page.locator('.wc-block-product').count()>=3,'Catalog must display seeded products');
    const sorting=page.locator('select.orderby');
    await Promise.all([page.waitForURL(/orderby=price-desc/), sorting.selectOption('price-desc')]);
    await page.waitForLoadState('networkidle');
    const prices=await page.locator('.aot-product-grid .wc-block-components-product-price').allTextContents();
    const values=prices.map(text=>Number(text.replace(/[^\d,.]/g,'').replaceAll('.','').replace(',','.')));
    assert.deepEqual(values,[...values].sort((a,b)=>b-a),'Native sorting must order current prices descending');
    const category=page.locator('.aot-category-filter a').first();
    assert.ok(await category.count()); await category.click(); await page.waitForLoadState('networkidle');
    assert.ok(await page.locator('.wc-block-product').count()>=1,'Native category archive must show products');
    pass('Native WooCommerce catalog, price sorting and category discovery work');

    await visit(data.pages.product);
    assert.ok((await page.locator('h1').innerText()).includes(data.product.name));
    const response=await context.request.get(base+`/wp-json/wc/store/v1/products/${data.product.id}`);
    const product=await response.json();
    assert.equal(Number(product.prices.price)/10**product.prices.currency_minor_unit,Number(data.product.price));
    assert.equal(product.prices.currency_code,'UYU');
    const schemas=await page.locator('script[type="application/ld+json"]').evaluateAll(nodes=>nodes.flatMap(node=>{const data=JSON.parse(node.textContent);return data['@graph'] || [data];}));
    const schema=schemas.find(item=>item['@type']==='Product');
    assert.ok(schema,'WooCommerce must supply Product structured data');
    assert.ok(await page.locator('.aot-product-main .aot-product-grid .wc-block-product').count()>=1,'Native related products must be present');
    assert.ok(await page.getByText('Pesos uruguayos · UYU',{exact:true}).count());
    await page.locator('input.qty').fill('2');
    await page.locator('.single_add_to_cart_button').click();
    await page.waitForLoadState('networkidle');
    await waitUntil(async()=> (await cart()).items_count===2,'Native add-to-cart did not add two units');
    await visit(data.pages.cart);
    const quantity=page.locator('.wc-block-components-quantity-selector__input').first();
    assert.equal(await quantity.inputValue(),'2');
    await quantity.fill('3'); await quantity.press('Tab');
    await waitUntil(async()=> (await cart()).items_count===3,'Cart quantity did not update');
    const current=await cart();
    assert.equal(Number(current.totals.total_items),Math.round(Number(data.product.price)*100*3));
    assert.ok(await page.locator('.wc-block-cart').isVisible());
    pass('Current product title/price/currency, native add-to-cart, quantity updates and three-unit subtotal');

    await visit(data.pages.faq);
    const summary=page.locator('main details summary').first();
    await summary.focus(); await summary.press('Enter');
    assert.ok(await summary.evaluate(element=>element.parentElement.open));
    await summary.press('Enter');
    assert.equal(await summary.evaluate(element=>element.parentElement.open),false);
    pass('FAQ disclosures open and close by keyboard without custom JS');

    await visit(data.pages.contact);
    await page.getByRole('button',{name:/Probar formulario/}).click();
    assert.equal(await page.locator('#aot-contact-name').evaluate(element=>element.validity.valueMissing),true);
    assert.equal(await page.locator('#aot-contact-name').evaluate(element=>element===document.activeElement),true);
    const nonce=await page.locator('[name="anotter_contact_nonce"]').inputValue();
    const invalid=await context.request.post(data.pages.contact,{form:{anotter_contact_submit:'1',anotter_contact_nonce:nonce,anotter_name:'',anotter_email:'bad',anotter_subject:'',anotter_message:''}});
    const invalidHtml=await invalid.text();
    assert.ok(invalidHtml.includes('aria-invalid="true"') && invalidHtml.includes('Revisá los campos'));
    const csrf=await context.request.post(data.pages.contact,{form:{anotter_contact_submit:'1',anotter_contact_nonce:'invalid'}});
    assert.ok((await csrf.text()).includes('La sesión del formulario venció'));
    const spam=await context.request.post(data.pages.contact,{form:{anotter_contact_submit:'1',anotter_contact_nonce:nonce,website:'bot.example'}});
    assert.ok((await spam.text()).includes('No pudimos validar esta solicitud'));
    for (const [field,value] of Object.entries({name:'Persona ficticia',email:'demo@example.invalid',subject:'Consulta ficticia',message:'Este mensaje es solo una prueba.'})) await page.locator(`[name="anotter_${field}"]`).fill(value);
    await page.getByRole('button',{name:/Probar formulario/}).click();
    await page.waitForLoadState('networkidle');
    assert.ok(await page.getByRole('status').getByText(/Tu mensaje no se envió ni se guardó/).isVisible());
    assert.equal(await page.evaluate(()=>document.activeElement.id),'aot-form-feedback');
    pass('Contact HTML/server validation, CSRF, honeypot and honest no-delivery feedback');

    const fixture=sentinel(wp('stock-fixture.php'),'ANOTTER_STOCK'); stockFixture=true;
    await visit(fixture.url);
    assert.equal(await page.locator('.single_add_to_cart_button').count(),0,'Out-of-stock product must not offer purchase');
    await page.goto(fixture.url+`?add-to-cart=${fixture.id}`,{waitUntil:'networkidle'});
    assert.equal((await cart()).items_count,3,'Server must reject an out-of-stock add-to-cart request');
    pass('Out-of-stock UI and native server-side purchasing validation');
  }
  assert.deepEqual(errors,[],'Unexpected browser JavaScript/console errors');
  pass('No unexpected JavaScript or console errors');
} catch (error) {
  failure={message:error.message,path:new URL(page.url()).pathname};
  await page.screenshot({path:path.join(output,'failure.png'),fullPage:true}).catch(()=>{});
  throw error;
} finally {
  if (stockFixture) wp('stock-fixture.php','cleanup');
  await context.close(); await browser.close();
  fs.writeFileSync(path.join(output,'results.json'),JSON.stringify({date:new Date().toISOString(),passed:!failure,failure,results,errors},null,2)+'\n');
}
console.log(`Completed ${results.length} acceptance groups. Screenshots: ${output}`);
