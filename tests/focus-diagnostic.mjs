/** No order submission: observe native rejected-empty-checkout focus without changing validation. */
import {chromium,firefox,webkit} from 'playwright';
import {wp,sentinel,hydrate} from './helpers.mjs';
const data=sentinel(wp('tests/storefront-data.php'),'ANOTTER_E2E');
for(const engine of [chromium,firefox,webkit]) {
  const browser=await engine.launch();const context=await browser.newContext();const page=await context.newPage();
  try {
    await page.goto(data.pages.product,{waitUntil:'networkidle'});
    await Promise.all([page.waitForNavigation({waitUntil:'networkidle'}),page.locator('.single_add_to_cart_button').click()]);
    await page.goto(data.pages.checkout,{waitUntil:'networkidle'});await hydrate(page,'checkout');
    await page.locator('.wc-block-components-checkout-place-order-button').click();
    await page.locator('input[aria-invalid=true]').first().waitFor();
    await page.waitForTimeout(1200);
    console.log(engine.name(),await page.evaluate(()=>({tag:document.activeElement.tagName,id:document.activeElement.id,inMain:Boolean(document.activeElement.closest('main'))})));
  } finally {await browser.close();}
}
