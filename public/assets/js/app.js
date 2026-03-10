(function () {
  const root = document.documentElement;
  const toggle = document.getElementById('themeToggle');
  const saved = localStorage.getItem('theme') || 'light';
  root.setAttribute('data-theme', saved);
  if (toggle) {
    toggle.textContent = saved === 'dark' ? 'Modo light' : 'Modo dark';
    toggle.addEventListener('click', function () {
      const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      localStorage.setItem('theme', next);
      toggle.textContent = next === 'dark' ? 'Modo light' : 'Modo dark';
    });
  }

  function bindResponsableScope(scope) {
    const toggleEl = scope.querySelector('[data-responsable-toggle]');
    const block = scope.querySelector('[data-responsable-fields]');
    if (!toggleEl || !block) return;
    const inputs = block.querySelectorAll('[data-responsable-input]');

    function sync() {
      const active = toggleEl.checked;
      block.style.display = active ? 'block' : 'none';
      inputs.forEach((input) => {
        input.required = active;
        if (!active) input.value = '';
      });
    }

    toggleEl.addEventListener('change', sync);
    sync();
  }

  document.querySelectorAll('[data-responsable-form], [data-cita-form]').forEach(bindResponsableScope);

  const citaForm = document.querySelector('[data-cita-form]');
  if (citaForm) {
    const modeSel = citaForm.querySelector('[data-cliente-mode]');
    const existentes = citaForm.querySelectorAll('[data-cliente-existente]');
    const manuales = citaForm.querySelectorAll('[data-cliente-manual]');
    const nuevo = citaForm.querySelector('[data-cliente-nuevo]');
    const nuevoInputs = citaForm.querySelectorAll('[data-nuevo-input]');

    function syncMode() {
      const mode = modeSel.value;
      existentes.forEach((el) => { el.style.display = mode === 'existente' ? 'block' : 'none'; });
      manuales.forEach((el) => {
        el.style.display = mode === 'manual' ? 'block' : 'none';
        el.querySelectorAll('input').forEach((i) => { i.required = mode === 'manual'; });
      });
      if (nuevo) {
        nuevo.style.display = mode === 'nuevo' ? 'block' : 'none';
      }
      nuevoInputs.forEach((i) => { i.required = mode === 'nuevo'; });
    }

    if (modeSel) {
      modeSel.addEventListener('change', syncMode);
      syncMode();
    }
  }
})();
