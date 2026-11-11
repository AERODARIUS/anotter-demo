/** M05 cross-engine responsive, accessibility, resilience and native purchasing QA. */
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import {chromium,firefox,webkit} from 'playwright';
import AxeBuilder from '@axe-core/playwright';
import {wp,sentinel,outputDir,hydrate,until,capture} from '../helpers.mjs';
const out=outputDir('browser');
const data=sentinel(wp('tests/storefront-data.php'),'ANOTTER_E2E');
const base=new URL(data.pages.home).origin;
const results=[],audits=[],failures=[];
const engines=process.env.M05_BROWSERS?.split(',')||['chromium','firefox','webkit'];
let owned=false;
const pass=(browser,name)=>{results.push({browser,name,status:'PASS'});console.log(`PASS ${browser}: ${name}`);};
async function audit(page,browser,name,width) {
  const result=await new AxeBuilder({page}).withTags(['wcag2a','wcag2aa','wcag21a','wcag21aa','wcag22aa']).analyze();
  const violations=result.violations.map(v=>({id:v.id,impact:v.impact,description:v.description,helpUrl:v.helpUrl,nodes:v.nodes.map(n=>({target:n.target,summary:n.failureSummary}))}));
  audits.push({browser,page:name,width,violations,incomplete:result.incomplete.map(v=>({id:v.id,nodes:v.nodes.length})),passedRules:result.passes.length});
}
try {
  const f=sentinel(wp('tests/commerce-fixtures.php','create'),'ANOTTER_M03_FIXTURES');owned=true;
  for(const engine of engines) {
    let browser;
    try{browser=await ({chromium,firefox,webkit}[engine]).launch({headless:true});}
    catch{results.push({browser:engine,status:'BLOCKED',name:'Engine unavailable'});continue;}
    const context=await browser.newContext({viewport:{width:1440,height:1000},reducedMotion:'reduce'});
    const page=await context.newPage();
    const errors=[];page.on('pageerror',e=>errors.push(e.message));
    try {
      const pages={...data.pages,product:f.products.main.url};
      for(const width of [320,375,768,1024,1440]) {
        await page.setViewportSize({width,height:1000});
        for(const name of ['home','shop','product','how','faq','contact']) {
          assert.equal((await page.goto(pages[name],{waitUntil:'networkidle'})).status(),200);
          await hydrate(page,name);
          assert.equal(await page.locator('main').count(),1,`${name} main landmark`);
          assert.equal(await page.locator('h1').count(),1,`${name} H1`);
          assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),`${name} overflow at ${width}`);
          assert.equal(await page.locator('main img').evaluateAll(es=>es.filter(e=>e.complete&&!e.naturalWidth).length),0,`${name} broken image`);
          if([375,1440].includes(width)) {
            await audit(page,engine,name,width);
            if(['home','shop','product'].includes(name))await capture(page,path.join(out,`${engine}-${name}-${width}.png`));
          }
        }
        pass(engine,`Navigation/product/forms rendered at ${width}px without overflow`);
      }
      await page.setViewportSize({width:375,height:844});await page.goto(pages.home,{waitUntil:'networkidle'});
      const drawer=page.locator('.wc-block-mini-cart__drawer');
      assert.equal(await drawer.evaluate(e=>getComputedStyle(e).visibility),'hidden');
      await page.locator('.wc-block-mini-cart__button').click();
      await until(async()=>await drawer.getAttribute('aria-hidden')==='false','Native mini-cart opening');
      assert.equal(await drawer.evaluate(e=>getComputedStyle(e).visibility),'visible');
      await audit(page,engine,'mini-cart-open',375);
      await drawer.locator('.wc-block-components-drawer__close').click();
      await until(async()=>await drawer.getAttribute('aria-hidden')==='true','Native mini-cart closing');
      const open=page.getByRole('button',{name:'Abrir menú'});
      await open.focus();await page.keyboard.press('Enter');
      const menu=page.locator('header .is-menu-open');await menu.waitFor();
      assert.ok(await menu.evaluate(e=>e.contains(document.activeElement)));
      await page.keyboard.press('Escape');assert.ok(await open.evaluate(e=>e===document.activeElement));
      await page.goto(pages.faq,{waitUntil:'networkidle'});const summary=page.locator('main summary').first();await summary.focus();await summary.press('Enter');assert.ok(await summary.evaluate(e=>e.parentElement.open));
      pass(engine,'Native mini-cart hidden/open accessibility, keyboard menu focus/restore, Escape and FAQ');
      const missing=await page.goto(base+'/m05-this-page-does-not-exist/',{waitUntil:'networkidle'});
      assert.equal(missing.status(),404);assert.equal(await page.locator('h1').count(),1,'404 must offer a useful heading');
      assert.ok(await page.getByRole('link',{name:/Volver al inicio/}).isVisible(),'404 recovery link');
      pass(engine,'404 status and useful recovery');
      for(const name of ['orders','coupons']) {
        const denied=await context.request.get(base+`/wp-json/wc/v3/${name}`);
        assert.ok([401,403].includes(denied.status()),`Guest cannot read private ${name}`);
      }
      const badProduct=await context.request.post(base+'/wp-json/wc/store/v1/cart/add-item',{data:{id:-1,quantity:1}});
      assert.ok(badProduct.status()>=400);
      pass(engine,'Anonymous private commerce API access and invalid product rejected');
      // Each engine has a fresh native browser cookie session; orders use only owned hidden stock.
      const readCart=async()=>{const response=await context.request.get(base+'/wp-json/wc/store/v1/cart');assert.equal(response.status(),200);return response.json();};
      const addressReady=async()=>{
        const c=await readCart(),a=c.shipping_address;
        return a.state==='UY-MO'&&a.first_name==='PRUEBA'&&a.last_name==='NO ENTREGAR'&&Boolean(a.address_1)&&a.postcode==='10000'&&c.billing_address.email===f.email&&[18000,21000].includes(Number(c.totals.total_shipping));
      };
      await page.goto(f.products.main.url,{waitUntil:'networkidle'});
      await Promise.all([page.waitForNavigation({waitUntil:'networkidle'}),page.locator('.single_add_to_cart_button').click()]);
      await until(async()=>(await readCart()).items.some(item=>item.id===f.products.main.id),'Native browser cart addition persisted');
      await page.goto(pages.cart,{waitUntil:'networkidle'});await hydrate(page,'cart');
      for(const width of [320,375,768,1024,1440]) {
        await page.setViewportSize({width,height:1000});
        assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1));
        if([375,1440].includes(width)){await audit(page,engine,'cart',width);await capture(page,path.join(out,`${engine}-cart-${width}.png`));}
      }
      await page.goto(pages.checkout,{waitUntil:'networkidle'});await hydrate(page,'checkout');
      const submit=page.locator('.wc-block-components-checkout-place-order-button');
      await submit.click();await page.locator('input[aria-invalid=true]').first().waitFor();
      await until(()=>page.evaluate(()=>Boolean(document.activeElement.closest('main'))),'Native validation must move focus into checkout');
      await audit(page,engine,'checkout-errors',1440);
      await page.locator('#email').fill('invalid');await page.locator('#email').press('Tab');await submit.click();
      assert.equal(await page.locator('#email').getAttribute('aria-invalid'),'true');
      for(const [id,value] of Object.entries({email:f.email,'shipping-first_name':'PRUEBA','shipping-last_name':'NO ENTREGAR','shipping-address_1':'DATO SINTETICO - NO ENTREGAR','shipping-city':'Montevideo','shipping-postcode':'10000','shipping-phone':'00000000'}))await page.locator('#'+id).fill(value);
      await page.locator('#shipping-state').selectOption('UY-MO');await page.locator('#shipping-phone').press('Tab');
      await until(addressReady,'Complete persisted address before keyboard selection');
      await page.waitForLoadState('networkidle');
      const ues=page.getByRole('radio',{name:/UES/});await ues.focus();await page.keyboard.press('Space');await until(()=>ues.isChecked(),'Keyboard shipping selection');
      await until(async()=>Number((await readCart()).totals.total_shipping)===21000,'Keyboard UES selection persisted before refresh');
      await page.waitForLoadState('networkidle');
      await page.reload({waitUntil:'networkidle'});await hydrate(page,'checkout');
      assert.ok((await readCart()).items.some(item=>item.id===f.products.main.id),'Refresh preserves native cart');
      // Unsubmitted guest fields can clear on native refresh. The cart survives;
      // re-enter the synthetic address and prove recovery rather than assuming persistence.
      for(const [id,value] of Object.entries({email:f.email,'shipping-first_name':'PRUEBA','shipping-last_name':'NO ENTREGAR','shipping-address_1':'DATO SINTETICO - NO ENTREGAR','shipping-city':'Montevideo','shipping-postcode':'10000','shipping-phone':'00000000'})){
        const input=page.locator('#'+id);if(await input.isVisible()&&await input.inputValue()!==value)await input.fill(value);
      }
      if(await page.locator('#shipping-state').isVisible()&&await page.locator('#shipping-state').inputValue()!=='UY-MO')await page.locator('#shipping-state').selectOption('UY-MO');
      await page.locator('#email').press('Tab');
      await until(addressReady,'Complete persisted address after refresh');
      await until(async()=>await page.locator('.wc-block-components-skeleton__element:visible').count()===0,'Native totals calculation settled');
      await page.waitForLoadState('networkidle');
      await page.getByRole('radio',{name:/UES/}).check();
      await until(async()=>Number((await readCart()).totals.total_shipping)===21000,'Persisted Montevideo UES selection');
      await page.waitForLoadState('networkidle');
      await page.locator('#terms-and-conditions').check();
      for(const width of [320,375,768,1024,1440]) {
        await page.setViewportSize({width,height:1000});assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1));
        if([375,1440].includes(width)){await audit(page,engine,'checkout',width);await capture(page,path.join(out,`${engine}-checkout-${width}.png`));}
      }
      const before=sentinel(wp('tests/commerce-fixtures.php','data'),'ANOTTER_M03_FIXTURES');
      await submit.click();await page.waitForURL('**/order-received/**',{timeout:60000});await page.waitForLoadState('networkidle');
      assert.ok((await page.locator('main').innerText()).includes('Pago no verificado'));
      const after=sentinel(wp('tests/commerce-fixtures.php','data'),'ANOTTER_M03_FIXTURES');
      assert.equal(after.orders.filter(o=>o.status==='on-hold').length,before.orders.filter(o=>o.status==='on-hold').length+1);
      assert.equal(after.products.main.stock,before.products.main.stock-1);
      const created=after.orders.find(o=>!before.orders.some(b=>b.id===o.id));assert.equal(created.paid,false);assert.ok(created.shipping_method.includes('UES'));
      for(const width of [320,375,768,1024,1440]){await page.setViewportSize({width,height:1000});assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1));if([375,1440].includes(width)){await audit(page,engine,'confirmation',width);await capture(page,path.join(out,`${engine}-confirmation-${width}.png`));}}
      await page.reload({waitUntil:'networkidle'});const repeated=sentinel(wp('tests/commerce-fixtures.php','data'),'ANOTTER_M03_FIXTURES');assert.equal(repeated.orders.filter(o=>o.status==='on-hold').length,after.orders.filter(o=>o.status==='on-hold').length);assert.equal(repeated.products.main.stock,after.products.main.stock);
      const stranger=await browser.newContext();const strangerPage=await stranger.newPage();await strangerPage.goto(pages.checkout,{waitUntil:'networkidle'});assert.ok(!strangerPage.url().includes('/checkout/'),'Fresh/expired session recovers to cart');await stranger.close();
      pass(engine,'Guest checkout, validation, keyboard UES, refresh, unpaid confirmation, one stock reduction and fresh-session recovery');
      assert.deepEqual(errors,[],'Unexpected browser runtime errors');
    } catch(error) {
      const focus=await page.evaluate(()=>({tag:document.activeElement.tagName,id:document.activeElement.id})).catch(()=>null);
      const shipping=await context.request.get(base+'/wp-json/wc/store/v1/cart').then(r=>r.json()).then(c=>({total:c.totals?.total_shipping,selected:c.shipping_rates?.flatMap(p=>p.shipping_rates.filter(r=>r.selected).map(r=>r.name))})).catch(()=>null);
      failures.push({browser:engine,message:error.message,focus,shipping});console.error(`FAIL ${engine}: ${error.message}`);
      await page.screenshot({path:path.join(out,`${engine}-failure.png`),fullPage:true}).catch(()=>{});
    } finally {await context.close();await browser.close();}
  }
} finally {
  if(owned)try{wp('tests/commerce-fixtures.php','cleanup');}catch{failures.push({message:'Owned fixture cleanup requires inspection'});}
  const serious=audits.flatMap(a=>a.violations.filter(v=>['critical','serious'].includes(v.impact)).map(v=>({browser:a.browser,page:a.page,width:a.width,id:v.id})));
  const report={date:new Date().toISOString(),engines,results,audits,failures,serious};
  fs.writeFileSync(path.join(out,`results-${engines.join('-')}.json`),JSON.stringify(report,null,2));
  fs.writeFileSync(path.join(out,'results.json'),JSON.stringify(report,null,2));
  if(failures.length||serious.length||results.some(r=>r.status==='BLOCKED'))process.exitCode=1;
  console.log(`${results.filter(r=>r.status==='PASS').length} groups; ${audits.length} axe scans; ${serious.length} serious/critical findings; ${failures.length} failures.`);
}
