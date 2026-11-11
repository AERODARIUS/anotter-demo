/** Observe real browser vs Store API shipping persistence; no order or payment submitted. */
import {webkit} from 'playwright';
import {wp,sentinel,hydrate} from './helpers.mjs';
const d=sentinel(wp('tests/storefront-data.php'),'ANOTTER_E2E');
const b=await webkit.launch(),c=await b.newContext(),p=await c.newPage();
const expected={first_name:'PRUEBA',last_name:'NO ENTREGAR',address_1:'DATO SINTETICO - NO ENTREGAR',city:'Montevideo',postcode:'10000',phone:'00000000',state:'UY-MO'};
const report=async(label)=>{
  const a=await(await c.request.get(new URL('/wp-json/wc/store/v1/cart',d.pages.home).href)).json();
  console.log(label,JSON.stringify({shippingMatches:Object.fromEntries(Object.entries(expected).map(([k,v])=>[k,a.shipping_address?.[k]===v])),billingEmailPresent:Boolean(a.billing_address?.email),total:a.totals.total_shipping,uiUES:await p.getByRole('radio',{name:/UES/}).isChecked()}));
};
try {
  await p.goto(d.pages.product,{waitUntil:'networkidle'});
  await Promise.all([p.waitForNavigation({waitUntil:'networkidle'}),p.locator('.single_add_to_cart_button').click()]);
  await p.goto(d.pages.checkout,{waitUntil:'networkidle'});await hydrate(p,'checkout');
  await p.locator('#email').fill('shipping-diag@example.invalid');
  for(const [field,value] of Object.entries(expected).filter(([k])=>k!=='state'))await p.locator('#shipping-'+field).fill(value);
  await p.locator('#shipping-state').selectOption('UY-MO');await p.locator('#shipping-phone').press('Tab');
  await p.waitForTimeout(5000);await report('before selection');
  await p.getByRole('radio',{name:/UES/}).check();await p.waitForTimeout(2000);await report('after UES');
  await p.reload({waitUntil:'networkidle'});await hydrate(p,'checkout');await report('after refresh');
}finally{await b.close();}
