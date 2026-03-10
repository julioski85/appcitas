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

  document.querySelectorAll('[data-responsable-form], [data-cita-form], [data-prospect-form]').forEach(bindResponsableScope);

  const citaForm = document.querySelector('[data-cita-form]');
  if (!citaForm) return;

  const modeSel = citaForm.querySelector('[data-cliente-mode]');
  const existentes = citaForm.querySelectorAll('[data-cliente-existente]');
  const manuales = citaForm.querySelectorAll('[data-cliente-manual]');
  const hiddenClienteId = citaForm.querySelector('[data-cliente-id]');
  const searchInput = citaForm.querySelector('[data-cliente-search]');
  const resultsBox = citaForm.querySelector('[data-cliente-results]');
  const searchUrl = citaForm.dataset.clientSearchUrl || '';

  function syncMode() {
    const mode = modeSel ? modeSel.value : 'manual';
    existentes.forEach((el) => { el.style.display = mode === 'existente' ? 'block' : 'none'; });
    manuales.forEach((el) => {
      el.style.display = mode === 'manual' ? 'block' : 'none';
      el.querySelectorAll('input').forEach((i) => { i.required = mode === 'manual'; });
    });
    if (mode === 'manual' && hiddenClienteId) hiddenClienteId.value = '';
  }

  if (modeSel) {
    modeSel.addEventListener('change', syncMode);
    syncMode();
  }

  let debounceTimer;
  async function doSearch(query) {
    if (!searchUrl || query.length < 2) {
      resultsBox.innerHTML = '';
      resultsBox.style.display = 'none';
      return;
    }

    const response = await fetch(searchUrl + '?q=' + encodeURIComponent(query), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const payload = await response.json();
    const items = payload && payload.data ? payload.data : [];

    if (!items.length) {
      resultsBox.innerHTML = '<div class="autocomplete-item">Sin coincidencias</div>';
      resultsBox.style.display = 'block';
      return;
    }

    resultsBox.innerHTML = items.map((item) => '<button type="button" class="autocomplete-item" data-id="' + item.id + '" data-display="' + item.display.replace(/"/g, '&quot;') + '">' + item.display + '</button>').join('');
    resultsBox.style.display = 'block';
  }

  if (searchInput && resultsBox) {
    searchInput.addEventListener('input', function () {
      if (hiddenClienteId) hiddenClienteId.value = '';
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(function () {
        doSearch(searchInput.value.trim()).catch(function () {
          resultsBox.innerHTML = '<div class="autocomplete-item">Error al buscar</div>';
          resultsBox.style.display = 'block';
        });
      }, 250);
    });

    resultsBox.addEventListener('click', function (event) {
      const target = event.target.closest('[data-id]');
      if (!target) return;
      if (hiddenClienteId) hiddenClienteId.value = target.dataset.id || '';
      searchInput.value = target.dataset.display || '';
      resultsBox.style.display = 'none';
    });

    document.addEventListener('click', function (event) {
      if (!event.target.closest('[data-cliente-autocomplete]')) {
        resultsBox.style.display = 'none';
      }
    });
  }

  const modal = document.querySelector('[data-prospect-modal]');
  const openModalBtn = document.querySelector('[data-open-prospect-modal]');
  const closeModalBtn = document.querySelector('[data-close-prospect-modal]');
  const prospectForm = document.querySelector('[data-prospect-form]');
  const feedback = document.querySelector('[data-prospect-feedback]');
  const prospectUrl = citaForm.dataset.prospectUrl || '';

  function setModal(open) {
    if (!modal) return;
    modal.classList.toggle('is-open', open);
    modal.setAttribute('aria-hidden', open ? 'false' : 'true');
  }

  if (openModalBtn) openModalBtn.addEventListener('click', function () { setModal(true); });
  if (closeModalBtn) closeModalBtn.addEventListener('click', function () { setModal(false); });
  if (modal) {
    modal.addEventListener('click', function (event) {
      if (event.target === modal) setModal(false);
    });
  }

  if (prospectForm && prospectUrl) {
    prospectForm.addEventListener('submit', async function (event) {
      event.preventDefault();
      if (feedback) feedback.textContent = '';

      const formData = new FormData(prospectForm);
      const sucursalInput = citaForm.querySelector('[name="sucursal_id"]');
      if (sucursalInput) formData.append('sucursal_id', sucursalInput.value);
      const csrf = citaForm.querySelector('[name="_csrf"]');
      if (csrf) formData.append('_csrf', csrf.value);

      try {
        const response = await fetch(prospectUrl, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const payload = await response.json();
        if (!response.ok || !payload.ok) {
          if (feedback) feedback.textContent = payload.message || 'No se pudo crear el prospecto';
          return;
        }

        if (hiddenClienteId) hiddenClienteId.value = payload.data.id;
        if (searchInput) searchInput.value = payload.data.display;
        if (modeSel) modeSel.value = 'existente';
        syncMode();
        prospectForm.reset();
        setModal(false);
      } catch (error) {
        if (feedback) feedback.textContent = 'Error inesperado al guardar';
      }
    });
  }
})();
