// Confirmed WC 11.2.1 error/skeleton semantics gaps. Native validation and focus stay in charge.
const checkout = document.querySelector('main');
if (checkout) {
  const addedDescriptions = new WeakMap();
  const enhance = () => {
    checkout.querySelectorAll('[aria-errormessage]').forEach((input) => {
      const previous = addedDescriptions.get(input) || [];
      const descriptions = (input.getAttribute('aria-describedby') || '').split(/\s+/).filter(id => id && !previous.includes(id));
      const errors = input.getAttribute('aria-invalid') === 'true'
        ? input.getAttribute('aria-errormessage').split(/\s+/).filter(id => document.getElementById(id)) : [];
      errors.forEach(id => {
        const message = document.getElementById(id);
        if (!message.hasAttribute('role') && !message.hasAttribute('aria-live')) {
          message.setAttribute('aria-live', 'polite');
          message.setAttribute('aria-atomic', 'true');
        }
      });
      const merged = [...new Set([...descriptions, ...errors])];
      if (merged.length) input.setAttribute('aria-describedby', merged.join(' '));
      else input.removeAttribute('aria-describedby');
      addedDescriptions.set(input, errors.filter(id => !descriptions.includes(id)));
    });
    checkout.querySelectorAll('.wc-block-components-skeleton__element[aria-label][aria-live]:not([role])')
      .forEach(element => element.setAttribute('role', 'status'));
  };
  enhance();
  // Observe only native state changes; our accessibility attributes cannot trigger a loop.
  new MutationObserver(enhance).observe(checkout, {subtree:true,childList:true,attributes:true,attributeFilter:['aria-invalid','aria-errormessage']});
}
