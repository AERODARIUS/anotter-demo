/** M03 real-service acceptance. All order-producing requests use owned test products. */
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import {spawnSync} from 'node:child_process';
import {fileURLToPath} from 'node:url';
import {chromium, request} from 'playwright';
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');
const output=path.resolve(process.env.SCREENSHOT_DIR||path.join(root,'work','m03-screenshots'));
fs.mkdirSync(output,{recursive:true});
const results=[],errors=[];
function wp(file,...args){
 const r=spawnSync(process.env.PYTHON||'python',['scripts/dev.py','wp','--','eval-file',`/opt/anotter/tests/${file}`,...args],{cwd:root,encoding:'utf8',timeout:180000});
 assert.equal(r.status,0,`${file}: ${r.stderr||r.stdout}`); return r.stdout;
}
function json(text,name){return JSON.parse(text.split(/\r?\n/).find(l=>l.startsWith(name+'=')).slice(name.length+1));}
function pass(text){results.push(text);console.log('PASS:',text);}
async function until(fn,text,ms=30000){const end=Date.now()+ms;while(Date.now()<end){if(await fn())return;await new Promise(r=>setTimeout(r,250));}throw Error(text);}
const data=json(wp('checkout-data.php'),'ANOTTER_CHECKOUT_DATA');
const base=new URL(data.pages.home).origin;
const apiPath=base+'/wp-json/wc/store/v1';
let browser,page,owned=false,failure=null;
const sessions=[];
// These non-deliverable, explicitly synthetic strings are test input, never customer/business details.
// Postal values exercise native validation only; no address existence or delivery claim is made.
function address(state='UY-MO',email){return {first_name:'PRUEBA',last_name:'NO ENTREGAR',company:'DEMO AUTOMATIZADA',address_1:'DATO SINTETICO - NO ENTREGAR',address_2:'',city:state==='UY-MO'?'Montevideo':'Canelones',state,country:'UY',postcode:state==='UY-MO'?'10000':'90000',phone:'00000000',...(email?{email}:{})};}
async function session(){
 const context=await request.newContext();sessions.push(context);
 const response=await context.get(apiPath+'/cart');assert.equal(response.status(),200);
 const token=response.headers()['cart-token'];assert.ok(token);
 async function call(route,body,method='POST'){
  const r=await context.fetch(apiPath+route,{method,headers:{'Cart-Token':token},...(body===undefined?{}:{data:body})});
  return {status:r.status(),body:await r.json()};
 }
 return {call,cart:async()=>{const r=await call('/cart',undefined,'GET');assert.equal(r.status,200);return r.body;},add:async(id,quantity=1)=>call('/cart/add-item',{id,quantity})};
}
async function empty(s){for(const item of (await s.cart()).items)assert.equal((await s.call('/cart/remove-item',{key:item.key})).status,200);}
async function destination(s,state='UY-MO',email){
 const r=await s.call('/cart/update-customer',{shipping_address:address(state),billing_address:address(state,email)});assert.equal(r.status,200,JSON.stringify(r.body));return r.body;
}
async function select(s,carrier){
 const cart=await s.cart();const group=cart.shipping_rates[0];assert.ok(group);
 const rate=group.shipping_rates.find(r=>r.name.startsWith(carrier));assert.ok(rate);
 const selected=await s.call('/cart/select-shipping-rate',{package_id:group.package_id,rate_id:rate.rate_id});assert.equal(selected.status,200);return selected.body;
}
function payload(email,state='UY-MO'){return {billing_address:address(state,email),shipping_address:address(state),payment_method:'bacs',payment_data:[],customer_note:'DEMO E2E M03 - NO ENTREGAR',create_account:false};}
async function snap(name){
 await page.evaluate(()=>window.scrollTo(0,0));
 await page.evaluate(()=>document.fonts.ready);
 await page.locator('main img').evaluateAll(es=>Promise.all(es.map(e=>e.decode().catch(()=>{}))));
 await page.screenshot({path:path.join(output,name+'.png'),fullPage:true});
}
async function layout(){
 assert.equal(await page.locator('main').count(),1);assert.equal(await page.locator('h1').count(),1);
 assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'Horizontal overflow at '+page.viewportSize().width);
 if(await page.locator('.aot-confirmation').count())assert.ok(await page.locator('td:last-child bdi').evaluateAll(es=>es.every(e=>{const r=document.createRange();r.selectNodeContents(e);return new Set([...r.getClientRects()].filter(v=>v.width>0).map(v=>Math.round(v.top))).size<=1;})),'Confirmation currency amount must stay on one line');
 for(const input of await page.locator('main input:not([type=hidden]),main select').all()){
  assert.ok(await input.evaluate(e=>Boolean(e.labels?.length||e.getAttribute('aria-label')||e.getAttribute('aria-labelledby'))),'Unlabelled input');
 }
}
try{
 const f=json(wp('commerce-fixtures.php','create'),'ANOTTER_M03_FIXTURES');owned=true;
 const p=f.products;
 assert.equal(data.currency,'UYU');assert.equal(Object.keys(data.states).length,19);assert.equal(data.states['UY-MO'],'Montevideo');assert.equal(data.hpos,true);
 pass('Installed Uruguay regions, UYU and HPOS verified');
 const s=await session();
 assert.equal((await s.add(p.main.id,2)).status,201);assert.equal((await s.add(p.second.id,1)).status,201);
 let cart=await s.cart();assert.equal(cart.items_count,3);
 assert.equal(Number(cart.totals.total_items),Math.round((Number(p.main.price)*2+Number(p.second.price))*100));
 const item=cart.items.find(i=>i.id===p.main.id);
 assert.equal((await s.call('/cart/update-item',{key:item.key,quantity:3})).status,200);
 assert.equal((await s.cart()).items_count,4);
 assert.ok((await s.call('/cart/update-item',{key:item.key,quantity:-1})).status>=400);
 assert.ok((await s.call('/cart/update-item',{key:item.key,quantity:7})).status>=400);
 assert.ok((await s.add(p.zero.id)).status>=400);
 pass('Native multi-product subtotals, quantity update and invalid/over-stock/zero-stock rejection');
 await empty(s);assert.equal((await s.cart()).items.length,0);
 assert.equal((await s.add(p.single.id)).status,201);assert.ok((await s.add(p.single.id)).status>=400);
 await empty(s);assert.equal((await s.add(p.variation.id,2)).status,201);assert.ok((await s.add(p.variation.id,2)).status>=400);
 await empty(s);pass('Removal, empty cart, sold-individually limits and native variation stock');
 await s.add(p.main.id,1);await destination(s,'UY-MO',f.email);
 for(const [state,zone] of [['UY-MO','montevideo'],['UY-CA','interior']]){
  cart=await destination(s,state,f.email);
  assert.equal(cart.shipping_rates[0].shipping_rates.length,2);
  for(const carrier of ['DAC','UES']){
   cart=await select(s,carrier);
   assert.equal(Number(cart.totals.total_shipping),Number(data.config.shipping[zone][carrier.toLowerCase()])*100);
   assert.equal(Number(cart.totals.total_price),Number(cart.totals.total_items)+Number(cart.totals.total_shipping));
  }
 }
 const unavailable=await s.call('/cart/select-shipping-rate',{package_id:0,rate_id:'flat_rate:999999'});
 assert.equal(unavailable.status,200);
 const actualRates=unavailable.body.shipping_rates[0].shipping_rates;
 assert.equal(actualRates.filter(rate=>rate.selected).length,1);
 assert.ok(actualRates.every(rate=>rate.rate_id!=='flat_rate:999999'));
 await select(s,'UES');
 pass('Montevideo/interior zone precedence, DAC/UES selection, safe unavailable-rate fallback and native totals');
 for(const code of ['not-a-real-m03-coupon',f.coupons.expired,f.coupons.used])assert.ok((await s.call('/cart/apply-coupon',{code})).status>=400);
 cart=(await s.call('/cart/apply-coupon',{code:f.coupons.valid})).body;
 assert.equal(Number(cart.totals.total_discount),Math.round(Number(p.main.price)*10));
 assert.equal(Number(cart.totals.total_shipping),Number(data.config.shipping.interior.ues)*100);
 assert.ok((await s.call('/cart/update-item',{key:cart.items[0].key,quantity:7})).status>=400);
 assert.equal((await s.call('/cart/remove-coupon',{code:f.coupons.valid})).status,200);
 pass('Valid, invalid, expired and exhausted coupons; discounts preserve shipping and stock restrictions');
 for(const change of [{billing_address:{...address('UY-MO',f.email),first_name:''}},{billing_address:{...address('UY-MO',f.email),email:'invalid'}},{shipping_address:{...address(),country:'AR',state:'B'}},{shipping_address:{...address(),state:'INVALID'}},{shipping_address:{...address(),postcode:''}},{billing_address:{...address('UY-MO',f.email),phone:''}},{expected_total:'1'}]){
  const r=await s.call('/checkout',{...payload(f.email),...change});assert.ok(r.status>=400,JSON.stringify(r));
 }
 pass('Server checkout rejects missing fields, malformed email, unsupported country/region, missing phone and stale total');
 await empty(s);

 browser=await chromium.launch({headless:true});
 const context=await browser.newContext({viewport:{width:1440,height:1000},reducedMotion:'reduce'});
 page=await context.newPage();page.on('pageerror',e=>errors.push(e.message));
 page.on('console',m=>{if(m.type()==='error')errors.push(m.text());});
 await page.goto(base+'/?add-to-cart='+p.main.id,{waitUntil:'networkidle'});
 await page.goto(data.pages.cart,{waitUntil:'networkidle'});
 await page.locator('.wc-block-cart-item__product').first().waitFor();
 const readCart=async()=>{const r=await context.request.get(apiPath+'/cart');assert.equal(r.status(),200);return r.json();};
 const quantity=page.locator('.wc-block-components-quantity-selector__input').first();
 await quantity.fill('2');await quantity.press('Tab');await until(async()=>(await readCart()).items_count===2,'UI quantity update');
 await page.reload({waitUntil:'networkidle'});await until(async()=>(await quantity.inputValue())==='2','Cart persistence');
 pass('Browser cart keyboard quantity controls and cookie-session persistence');
 await page.goto(base+'/?add-to-cart='+p.second.id,{waitUntil:'networkidle'});
 await page.goto(data.pages.cart,{waitUntil:'networkidle'});
 const secondRow=page.locator('.wc-block-cart-items__row').filter({hasText:p.second.name});
 const remove=secondRow.locator('.wc-block-cart-item__remove-link');await remove.focus();await remove.press('Enter');
 await until(async()=>(await readCart()).items_count===2,'Keyboard item removal');
 pass('Native remove-item button works by keyboard and recalculates cart totals');
 for(const width of [320,375,768,1024,1440]){
  await page.setViewportSize({width,height:1000});await layout();
  if([375,1440].includes(width))await snap('cart-'+width);
 }
 await page.getByRole('link',{name:/Finalizar compra|Proceder al pago|Proceed to checkout/i}).last().click();
 await page.waitForURL('**/checkout/');await page.locator('#email').waitFor();
 await page.locator('.wc-block-components-order-summary-item__description').first().waitFor();
 for(const width of [320,375,768,1024,1440]){
  await page.setViewportSize({width,height:1000});await layout();if([375,1440].includes(width))await snap('checkout-'+width);
 }
 pass('Cart/checkout landmarks, labels and no overflow at all five widths; desktop/mobile captures');
 await page.getByRole('button',{name:'Realizar el pedido',exact:true}).click();
 await page.locator('.wc-block-components-validation-error').first().waitFor();
 await snap('validation-1440');
 assert.ok(await page.locator('input[aria-invalid=true]').count());
 for(const [id,value] of Object.entries({email:f.email,'shipping-first_name':'PRUEBA','shipping-last_name':'NO ENTREGAR','shipping-address_1':'DATO SINTETICO - NO ENTREGAR','shipping-city':'Montevideo','shipping-postcode':'10000','shipping-phone':'00000000'}))await page.locator('#'+id).fill(value);
 await page.locator('#shipping-state').selectOption('UY-MO');await page.locator('#shipping-phone').press('Tab');
 await until(async()=>Number((await readCart()).totals.total_shipping)===Number(data.config.shipping.montevideo.dac)*100,'Montevideo rate');
 const ues=page.getByRole('radio',{name:/UES/});await ues.check();
 await until(async()=>Number((await readCart()).totals.total_shipping)===Number(data.config.shipping.montevideo.ues)*100,'UES total');
 await snap('shipping-1440');
 await page.locator('#shipping-state').selectOption('UY-CA');await page.locator('#shipping-city').fill('Canelones');await page.locator('#shipping-postcode').fill('90000');await page.locator('#shipping-phone').press('Tab');
 await until(async()=>Number((await readCart()).totals.total_shipping)===Number(data.config.shipping.interior.dac)*100,'Interior default rate');
 await page.getByRole('radio',{name:/UES/}).check();
 await until(async()=>Number((await readCart()).totals.total_shipping)===Number(data.config.shipping.interior.ues)*100,'Interior selected UES rate');
 await page.locator('#terms-and-conditions').check();
 await page.setViewportSize({width:375,height:1000});await layout();await snap('shipping-375');
 pass('Accessible client errors, Uruguay address fields and live department/carrier selection in checkout');
 const before=await readCart();
 // Delay only the real checkout request to inspect native loading/duplicate-click protection.
 let releaseCheckout,requestSeen;
 const gate=new Promise(resolve=>{releaseCheckout=resolve;});
 const submitted=new Promise(resolve=>{requestSeen=resolve;});
 await page.route('**/wc/store/v1/checkout**',async route=>{if(route.request().method()==='POST'){requestSeen();await gate;}await route.continue();});
 const submit=page.locator('.wc-block-components-checkout-place-order-button');
 const clicked=submit.click({noWaitAfter:true});
 await Promise.race([submitted,new Promise((_,reject)=>setTimeout(()=>reject(Error('Native checkout request was not sent')),20000))]);
 try{await until(async()=>await submit.isDisabled()||await submit.getAttribute('aria-disabled')==='true','Native checkout button must disable during submission');}finally{releaseCheckout();}
 await clicked;
 await page.waitForURL('**/order-received/**',{timeout:60000});await page.waitForLoadState('networkidle');
 assert.ok((await page.locator('main').innerText()).includes('Pago no verificado'));
 assert.ok((await page.locator('main').innerText()).includes('UES'),'Persisted carrier must be visible in confirmation');
 const confirmUrl=page.url();assert.ok(!confirmUrl.includes(f.email));
 for(const width of [320,375,768,1024,1440]){await page.setViewportSize({width,height:1000});await layout();if([375,1440].includes(width))await snap('confirmation-'+width);}
 let report=json(wp('commerce-fixtures.php','data'),'ANOTTER_M03_FIXTURES');
 const order=report.orders.find(o=>o.status==='on-hold');assert.ok(order);assert.equal(order.customer_id,0);assert.equal(order.payment,'bacs');assert.equal(order.paid,false);assert.equal(Number(order.total)*100,Number(before.totals.total_price));assert.ok(order.shipping_method.includes('UES'));assert.ok(order.notes>=2);
 assert.equal(report.products.main.stock,p.main.stock-2);assert.equal(report.orders.filter(o=>o.status==='on-hold').length,1);
 pass('Guest checkout creates exactly one unpaid on-hold HPOS order; summary/shipping and native stock reduction verified');
 const outsider=await browser.newContext();const outsiderPage=await outsider.newPage();
 await outsiderPage.goto(confirmUrl.replace(/key=[^&]+/,'key=invalid'),{waitUntil:'networkidle'});
 assert.ok(!(await outsiderPage.locator('body').innerText()).includes('DATO SINTETICO'));
 const noKey=new URL(confirmUrl);noKey.searchParams.delete('key');await outsiderPage.goto(noKey.href,{waitUntil:'networkidle'});
 assert.ok(!(await outsiderPage.locator('body').innerText()).includes('DATO SINTETICO'));await outsider.close();
 pass('Native confirmation access denies unrelated visitors without a valid order key');

 // Native wp-login/admin UI; credentials are read locally and never printed or exported.
 const env=Object.fromEntries(fs.readFileSync(process.env.ANOTTER_ENV_FILE||path.join(root,'.env'),'utf8').split(/\r?\n/).filter(l=>l&&!l.startsWith('#')).map(l=>{const i=l.indexOf('=');return [l.slice(0,i),l.slice(i+1).replace(/^['"]|['"]$/g,'')];}));
 const admin=await browser.newContext({viewport:{width:1440,height:1000}});const adminPage=await admin.newPage();
 await adminPage.goto(base+'/wp-login.php');await adminPage.locator('#user_login').fill(env.WP_ADMIN_USER);await adminPage.locator('#user_pass').fill(env.WP_ADMIN_PASSWORD);await adminPage.locator('#wp-submit').click();await adminPage.waitForURL('**/wp-admin/**');
 await adminPage.goto(order.admin,{waitUntil:'domcontentloaded'});await adminPage.locator('#woocommerce-order-items').waitFor();
 assert.ok((await adminPage.locator('body').innerText()).includes('UES'));await adminPage.screenshot({path:path.join(output,'order-admin-1440.png'),fullPage:true});
 await adminPage.goto(base+'/wp-admin/admin.php?page=anotter-demo-mail',{waitUntil:'domcontentloaded'});await adminPage.getByRole('heading',{name:'Correos DEMO — captura local',exact:true}).waitFor();assert.ok(await adminPage.locator('iframe').count());await adminPage.frameLocator('iframe').first().locator('body').waitFor();await adminPage.screenshot({path:path.join(output,'mail-capture-1440.png'),fullPage:false});await admin.close();
 pass('Native order administration, shipping/items/status/notes and protected local email capture verified');

 const a=await session(),b=await session();await a.add(p.last.id);await b.add(p.last.id);
 await destination(a,'UY-MO',f.email);await destination(b,'UY-MO',f.email);await select(a,'DAC');await select(b,'DAC');
 const raced=await Promise.all([a.call('/checkout',payload(f.email)),b.call('/checkout',payload(f.email))]);
 assert.equal(raced.filter(r=>r.status===200).length,1,'Concurrent checkout statuses: '+raced.map(r=>r.status).join(','));assert.ok(raced.some(r=>r.status>=400));
 report=json(wp('commerce-fixtures.php','data'),'ANOTTER_M03_FIXTURES');assert.equal(report.products.last.stock,0);
 const retry=await a.call('/checkout',payload(f.email));assert.ok(retry.status>=400);report=json(wp('commerce-fixtures.php','data'),'ANOTTER_M03_FIXTURES');assert.equal(report.orders.filter(o=>o.status==='on-hold').length,2);
 pass('Two real concurrent customers compete for the last unit: one order succeeds; retry cannot create another order');
 console.log(wp('commerce-fixtures.php','cancel').split(/\r?\n/).filter(l=>l.startsWith('Success:')).join('\n'));
 report=json(wp('commerce-fixtures.php','data'),'ANOTTER_M03_FIXTURES');assert.equal(report.products.main.stock,p.main.stock);assert.equal(report.products.last.stock,1);
 console.log(wp('commerce-fixtures.php','lifecycle').split(/\r?\n/).filter(l=>l.startsWith('Success:')).join('\n'));
 pass('Native cancellation/restoration, status transitions, refund restocking and aged pending-order expiration');
 await page.goto(data.pages.cart,{waitUntil:'networkidle'});await page.locator('.aot-empty-cart').waitFor();
 for(const width of [375,1440]){await page.setViewportSize({width,height:1000});await layout();await snap('empty-cart-'+width);}
 assert.equal(errors.length,0,errors.join('\n'));pass('Empty-cart CTA and no unexpected browser errors');
}catch(error){failure={message:error.message,path:page?new URL(page.url()).pathname:null};if(page)await page.screenshot({path:path.join(output,'failure.png'),fullPage:true}).catch(()=>{});throw error;}
finally{
 if(owned)try{wp('commerce-fixtures.php','cleanup');}catch(error){failure||={message:error.message};console.error('Fixture cleanup requires attention:',error.message);process.exitCode=1;}
 fs.writeFileSync(path.join(output,'results.json'),JSON.stringify({date:new Date().toISOString(),passed:!failure,failure,results,errors},null,2));
 if(!failure&&fs.existsSync(path.join(output,'failure.png')))fs.unlinkSync(path.join(output,'failure.png'));
 for(const s of sessions)await s.dispose();if(browser)await browser.close();
}
console.log(`Completed ${results.length} M03 acceptance groups. Screenshots: ${output}`);
