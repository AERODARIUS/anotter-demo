// Native HTML constraints and server validation do the work; focus the result after a POST.
const feedback = document.getElementById('aot-form-feedback');
if (feedback) feedback.focus({ preventScroll: true });

// Preserve WordPress's native dialog and focus management; expose its existing state.
document.querySelectorAll('.aot-main-navigation').forEach((navigation) => {
  const open = navigation.querySelector('.wp-block-navigation__responsive-container-open');
  const panel = navigation.querySelector('.wp-block-navigation__responsive-container');
  if (!open || !panel) return;
  if (panel.id && !open.hasAttribute('aria-controls')) open.setAttribute('aria-controls', panel.id);
  if (!open.hasAttribute('aria-expanded')) {
    const sync = () => open.setAttribute('aria-expanded', String(panel.classList.contains('is-menu-open')));
    sync();
    new MutationObserver(sync).observe(panel, { attributes: true, attributeFilter: ['class'] });
  }
});
