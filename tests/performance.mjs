/** Lighthouse lab samples on real HTTP pages. No payment or order is submitted. */
import fs from 'node:fs';
import path from 'node:path';
import os from 'node:os';
import assert from 'node:assert/strict';
import {chromium} from 'playwright';
import puppeteer from 'puppeteer-core';
import lighthouse from 'lighthouse';
import desktopConfig from 'lighthouse/core/config/desktop-config.js';
import {wp,sentinel,outputDir} from './helpers.mjs';
const label=process.argv[2]||'final';
if(!/^[a-z0-9-]+$/.test(label))throw Error('Use a simple report label');
const data=sentinel(wp('tests/storefront-data.php'),'ANOTTER_E2E');
const out=outputDir('performance');
const browser=await puppeteer.launch({headless:true,executablePath:chromium.executablePath()});
// Lighthouse's Puppeteer page uses the same native cookie cart for every sample.
const page=await browser.newPage();
await page.setViewport({width:1440,height:1000,deviceScaleFactor:1});
const results=[];
const browserVersion=await browser.version();
let settings;
try {
  await page.goto(data.pages.product,{waitUntil:'networkidle0'});
  await Promise.all([page.waitForNavigation({waitUntil:'networkidle0'}),page.click('.single_add_to_cart_button')]);
  for(const name of ['home','shop','product','cart','checkout']) {
    const cdp=await page.createCDPSession();
    await cdp.send('Network.clearBrowserCache');
    await cdp.send('Network.setCacheDisabled',{cacheDisabled:true});
    await page.goto(data.pages[name],{waitUntil:'networkidle0'});
    if(name==='cart')await page.waitForSelector('.wc-block-cart-items__row .wc-block-components-product-name');
    if(name==='checkout') {
      await page.waitForSelector('#email');
      await page.waitForFunction(product=>document.querySelector('.wc-block-components-order-summary-item__description')?.textContent.includes(product),{},data.product.name);
    }
    await page.waitForNetworkIdle({idleTime:1000});
    const payload=await page.evaluate(()=>{
      const entries=performance.getEntriesByType('resource');
      const type=e=>/\.js(?:\?|$)/.test(e.name)?'js':/\.css(?:\?|$)/.test(e.name)?'css':/\.(?:webp|png|jpg|jpeg|svg)(?:\?|$)/.test(e.name)?'image':'other';
      const size=t=>entries.filter(e=>type(e)===t).reduce((n,e)=>n+e.decodedBodySize,0);
      return {requests:entries.length+1,transfer_bytes:entries.reduce((n,e)=>n+e.transferSize,performance.getEntriesByType('navigation')[0].transferSize),js_decoded_bytes:size('js'),css_decoded_bytes:size('css'),image_decoded_bytes:size('image'),cache_hits:entries.filter(e=>e.transferSize===0&&e.decodedBodySize>0).length};
    });
    const {lhr}=await lighthouse(data.pages[name],{logLevel:'error',output:'json',onlyCategories:['performance'],disableStorageReset:false},
      {...desktopConfig,settings:{...desktopConfig.settings,clearStorageTypes:['cache_storage','service_workers'],screenEmulation:{mobile:false,width:1440,height:1000,deviceScaleFactor:1,disabled:false},throttlingMethod:'simulate'}},page);
    settings=lhr.configSettings;
    assert.equal(new URL(lhr.finalDisplayedUrl).pathname,new URL(data.pages[name]).pathname,'Audit must remain on the requested page');
    if(name==='cart')await page.waitForSelector('.wc-block-cart-items__row .wc-block-components-product-name');
    if(name==='checkout')await page.waitForFunction(product=>document.querySelector('.wc-block-components-order-summary-item__description')?.textContent.includes(product),{},data.product.name);
    const a=lhr.audits;
    const resources=a['resource-summary']?.details?.items||[];
    const size=type=>resources.find(r=>r.resourceType===type)?.transferSize??null;
    results.push({page:name,status:lhr.runtimeError?'FAIL':'PASS',error:lhr.runtimeError?.code??null,score:lhr.categories.performance.score,
      lcp_ms:a['largest-contentful-paint']?.numericValue??null,cls:a['cumulative-layout-shift']?.numericValue??null,
      tbt_ms:a['total-blocking-time']?.numericValue??null,server_response_ms:a['server-response-time']?.numericValue??null,
      observed:Object.fromEntries(Object.entries(a.metrics?.details?.items?.[0]||{}).filter(([key])=>key.startsWith('observed')&&/LargestContentfulPaint|TotalBlockingTime|CumulativeLayoutShift/.test(key))),
      payload,lighthouse_requests:resources.find(r=>r.resourceType==='total')?.requestCount??null,lighthouse_transfer_bytes:size('total'),
      lcp_details:a['lcp-breakdown-insight']?.details??null,
      warnings:lhr.runWarnings,opportunities:Object.values(a).filter(v=>v.details?.type==='opportunity'&&v.details.overallSavingsMs>0).map(v=>({id:v.id,savings_ms:v.details.overallSavingsMs}))});
    await cdp.detach();
    console.log(`LAB ${name}: LCP ${results.at(-1).lcp_ms?.toFixed(0)}ms CLS ${results.at(-1).cls} TBT ${results.at(-1).tbt_ms}ms`);
  }
} finally {
  fs.writeFileSync(path.join(out,`${label}.json`),JSON.stringify({date:new Date().toISOString(),environment:{os:os.platform(),cpu:os.cpus()[0]?.model,memory_gib:Math.round(os.totalmem()/2**30),node:process.version,browser:browserVersion,lighthouse:'13.6.0',url:'loopback',viewport:'1440x1000',throttling:settings?.throttling,cacheReset:!settings?.disableStorageReset,clearStorageTypes:settings?.clearStorageTypes,profile:'Lighthouse desktop simulated throttling; HTTP cache reset, native cookies/localStorage retained. Payload measured separately after fully hydrated pre-audit navigation; decoded bytes are not compressed transfer bytes. One sample per page.',inp:'NOT RUN — no representative field interactions; TBT is a lab diagnostic'},results},null,2));
  await browser.close();
}
if(results.some(r=>r.status==='FAIL')||results.length!==5)process.exitCode=1;
