/** Allow precise entry beyond the Elementor slider's draggable range. */
document.addEventListener('focusin', function (event) {
  const input = event.target;
  if (!input.matches('.sp-hero-measure input[data-setting="size"]')) return;
  input.removeAttribute('max');
  input.setAttribute('step', 'any');
  if (input.closest('.sp-hero-measure--signed')) input.removeAttribute('min');
  else input.setAttribute('min', '0');
});
