/** Real official receiver rejection and browser-return checks. No fabricated valid notifications. */
import assert from 'node:assert/strict';
import fs from 'node:fs';
import {spawnSync} from 'node:child_process';
import {chromium, request} from 'playwright';
const dotenv=fs.readFileSync(process.env.ANOTTER_ENV_FILE || '.env','utf8');
const value=name=>(dotenv.match(new RegExp('^'+name+'=(.*)$','m'))?.[1]||'').trim();
const base=value('PUBLIC_SITE_URL')||value('SITE_URL');
function wp(file,action){
 const r=spawnSync(process.env.PYTHON||'python',['scripts/dev.py','wp','--','--url='+base,'eval-file','/opt/anotter/tests/'+file,...(action?[action]:[])],{encoding:'utf8',timeout:180000});
 assert.equal(r.status,0,'CLI assertion failed; inspect the safe configuration checks');
 return r.stdout;
}
function fixture(action){const text=wp('payment-fixture.php',action);return action==='cleanup'?null:JSON.parse(text.split(/\r?\n/).find(x=>x.startsWith('ANOTTER_M04_FIXTURE=')).slice('ANOTTER_M04_FIXTURE='.length));}
const api=await request.newContext();
let browser,owned=false;
try{
 wp('payments.php');
 const local=value('SITE_URL');
 const forged=await api.get(local+'/',{headers:{Host:'unconfigured.example.invalid','X-Forwarded-Proto':'https'}});
 assert.equal(forged.status(),421);
 const forwarded=await api.get(local+'/',{headers:{'X-Forwarded-Proto':'https','X-Forwarded-Host':'unconfigured.example.invalid'}});
 assert.equal(forwarded.status(),200);assert.ok(!(await forwarded.text()).includes('https://unconfigured.example.invalid'));
 console.log('PASS: Unknown Host rejected; arbitrary forwarded host/proto do not control local URL generation');
 const before=fixture('create');owned=true;
 for(const url of [before.return_url,before.cancel_url])assert.equal(new URL(url).origin,base,'Native return URLs must use the selected origin');
 const endpoint=base+'/?wc-api=WC_WooMercadoPago_Basic_Gateway&source_news=ipn';
 for(let i=0;i<2;i++){
  const response=await api.post(endpoint,{data:{status:'approved',external_reference:'untrusted'}});
  assert.equal(response.status(),400,'Malformed Core notification must be rejected');
 }
 const missing=await api.post(endpoint+'&topic=merchant_order',{data:{status:'approved'}});
 assert.equal(missing.status(),422,'IPN missing id must be rejected');
 assert.deepEqual(fixture('inspect'),before,'Invalid/repeated requests must not change payment, status or stock');
 console.log('PASS: Real official receiver rejects malformed and repeated invalid requests; unpaid order/stock unchanged');
 browser=await chromium.launch({headless:true});
 const page=await browser.newPage();
 const errors=[];page.on('pageerror',e=>errors.push(e.message));
 page.on('console',message=>{if(message.type()==='error' && /mixed content/i.test(message.text()))errors.push('Mixed content error');});
 const url=new URL(before.return_url);url.searchParams.set('status','approved');url.searchParams.set('collection_status','approved');
 const returned=await page.goto(url.href,{waitUntil:'domcontentloaded',timeout:60000});
 assert.equal(returned.status(),200);
 await page.locator('main.aot-confirmation').waitFor({state:'visible'});
 assert.deepEqual(fixture('inspect'),before,'Browser status parameters must not complete payment');
 console.log('PASS: Browser return with forged approved parameters leaves pending order unpaid and stock unreduced');
 // A Cart-Token client is a separate native Store API session. Exercise the
 // browser's own cookie session so checkout cannot silently redirect to empty cart.
 await page.goto(base+'/?add-to-cart='+before.product,{waitUntil:'domcontentloaded',timeout:60000});
 const cart=await page.request.get(base+'/wp-json/wc/store/v1/cart');assert.equal(cart.status(),200);
 assert.ok((await cart.json()).items.some(item=>item.id===before.product),'Browser cookie cart must contain the owned item');
 const checkout=await page.goto(base+'/checkout/',{waitUntil:'domcontentloaded',timeout:60000});assert.equal(checkout.status(),200);
 assert.equal(new URL(page.url()).origin,base);
 assert.equal(new URL(page.url()).pathname,'/checkout/','Nonempty checkout must not redirect to cart');
 await page.locator('[data-block-name="woocommerce/checkout"]').waitFor({state:'visible'});
 await page.getByText('DEMO — M04 negative notification test',{exact:true}).first().waitFor({state:'visible',timeout:60000});
 assert.equal(errors.length,0,'Checkout must have no page errors');
 if(base.startsWith('https://')){
  const insecure=await page.locator('[src],[href]').evaluateAll(es=>es.map(e=>e.getAttribute('src')||e.getAttribute('href')).filter(v=>/^http:\/\/(localhost|127\.0\.0\.1)/i.test(v)));
  assert.equal(insecure.length,0,'Public checkout must not contain local HTTP links/assets');
 }
 fs.mkdirSync('work/m04/screenshots',{recursive:true});
 await page.screenshot({path:'work/m04/screenshots/checkout.png',fullPage:true});
 console.log('PASS: Native cart session and Checkout Blocks render with correct origin after browser return');
 const denied=await api.get(base+'/wp-content/uploads/wc-logs/');assert.ok([403,404].includes(denied.status()));
 console.log('PASS: WooCommerce diagnostic directory is not publicly readable');
 console.log('BLOCKED: Approved/rejected/pending provider purchases and valid-notification replay require linked test accounts. No payment was simulated.');
}catch(error){
 const message=error.message.replace(/(https?:\/\/[^\s"'<>?]+)\?[^\s"'<>]*/g,'$1').replace(/\bwc_order_[\w-]+/g,'[order key redacted]');
 console.error(message);
 process.exitCode=1;
}finally{
 await browser?.close();await api.dispose();if(owned)fixture('cleanup');
}
