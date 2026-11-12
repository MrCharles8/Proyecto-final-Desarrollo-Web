'use strict';
const botonMenu = document.querySelector('#alternarMenu');
const sidebar = document.querySelector('#barraNavegacion');
if (botonMenu && sidebar) botonMenu.addEventListener('click', () => {
  const open = sidebar.classList.toggle('open');
  botonMenu.setAttribute('aria-expanded', String(open));
});
document.querySelectorAll('[data-confirm]').forEach(button => {
  button.addEventListener('click', event => {
    if (!window.confirm(button.dataset.confirm)) event.preventDefault();
  });
});
const formularioEmpresa = document.querySelector('#formularioEmpresa');
if (formularioEmpresa) {
  document.querySelectorAll('[data-empresa]').forEach(button => {
    button.addEventListener('click', () => {
      const empresa = JSON.parse(button.dataset.empresa);
      for (const key of ['id','nombre','sector','sitio_web']) {
        const field = formularioEmpresa.elements.namedItem(key);
        if (field) field.value = empresa[key] ?? '';
      }
      formularioEmpresa.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
  });
  formularioEmpresa.addEventListener('reset', () => {
    window.setTimeout(() => { formularioEmpresa.elements.namedItem('id').value = ''; }, 0);
  });
}
