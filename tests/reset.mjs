/** Actual CLI reset against isolated WordPress/WooCommerce; never the normal demo. */
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import {spawnSync} from 'node:child_process';
import {root,wp,sentinel,outputDir} from './helpers.mjs';
const out=outputDir('reset'),results=[];
function command(...args){return spawnSync(process.env.PYTHON||'python',['scripts/dev.py',...args],{cwd:root,encoding:'utf8',timeout:180000});}
function fixture(action){return sentinel(wp('tests/reset-fixture.php',action),'ANOTTER_RESET_TEST');}
function pass(name){results.push(name);console.log('PASS:',name);}
let owned=false,failure=null;
try {
  const original=fixture('create');owned=true;
  assert.equal(command('demo-reset','--apply','--confirm-project','wrong').status,1);pass('Wrong-project write refused');
  const tagged=command('demo-tag','--order',String(original.eligible),'--session','m05-reset-test','--apply','--confirm-project','anotter-m05-qa');assert.equal(tagged.status,0,'Tag eligible offline order');
  const before=fixture('inspect');
  assert.equal(command('demo-reset','--session','m05-reset-test','--delete-orders','--apply','--confirm-project','anotter-m05-qa').status,1);
  assert.deepEqual(fixture('inspect'),before);pass('Unknown active order blocks entire reset before mutation');
  wp('tests/reset-fixture.php','cancel-foreign');
  const ready=fixture('inspect');
  const dry=command('demo-reset','--session','m05-reset-test','--delete-orders','--dry-run');assert.equal(dry.status,0);assert.deepEqual(fixture('inspect'),ready);pass('Dry run leaves order, stock, price and configuration unchanged');
  const reset=command('demo-reset','--session','m05-reset-test','--delete-orders','--apply','--confirm-project','anotter-m05-qa');assert.equal(reset.status,0,'Apply eligible reset');
  const after=fixture('inspect');
  assert.equal(after.eligible_exists,false);assert.equal(after.foreign_exists,true);assert.equal(after.main_stock,24);assert.equal(Number(after.main_price),890);
  assert.equal(after.unrelated_stock,7);assert.equal(Number(after.unrelated_price),77);assert.deepEqual(after.plugins,original.plugins);assert.deepEqual(after.gateways,original.gateways);assert.equal(after.theme,original.theme);assert.equal(after.admin_count,original.admin_count);
  pass('Native cancellation/deletion restores fixtures; unselected order/product, plugins, theme, administrator and payment settings preserved');
  assert.equal(command('demo-reset','--session','m05-reset-test','--delete-orders','--apply','--confirm-project','anotter-m05-qa').status,0);assert.deepEqual(fixture('inspect'),after);pass('Repeat reset is idempotent');
}catch(error){failure=error.message;throw error;}
finally {
  if(owned)wp('tests/reset-fixture.php','cleanup');
  fs.writeFileSync(path.join(out,'results.json'),JSON.stringify({date:new Date().toISOString(),results,failure},null,2));
}
