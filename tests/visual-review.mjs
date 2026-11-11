/** Real rendered editorial/error review artifacts, including server form feedback. */
import {chromium} from 'playwright';
import path from 'node:path';
import assert from 'node:assert/strict';
import {wp,sentinel,outputDir,capture} from './helpers.mjs';
const data=sentinel(wp('tests/storefront-data.php'),'ANOTTER_E2E');
const out=outputDir('visual');
const browser=await chromium.launch();
const context=await browser.newContext({reducedMotion:'reduce'});
const page=await context.newPage();
try {
  for(const width of [375,1440]) {
    await page.setViewportSize({width,height:1000});
    for(const name of ['how','faq','contact','404']) {
      const url=name==='404'?new URL('/m05-missing-visual/',data.pages.home).href:data.pages[name];
      assert.equal((await page.goto(url,{waitUntil:'networkidle'})).status(),name==='404'?404:200);
      if(name==='faq')await page.locator('main summary').first().click();
      await capture(page,path.join(out,`${name}-${width}.png`));
      if(name==='contact') {
        await page.locator('main form').evaluate(form=>{form.noValidate=true;});
        await Promise.all([page.waitForNavigation({waitUntil:'networkidle'}),page.getByRole('button',{name:/Probar formulario/}).click()]);
        assert.equal(await page.evaluate(()=>document.activeElement.id),'aot-form-feedback');
        assert.ok(await page.getByRole('alert').isVisible());
        await capture(page,path.join(out,`contact-errors-${width}.png`));
      }
    }
  }
  console.log('PASS: rendered How/FAQ/contact/server feedback/404 captures at mobile and desktop');
} finally {await browser.close();}
