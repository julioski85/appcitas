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
      const active = !!toggleEl.checked;
      block.style.display = active ? 'block' : 'none';
      block.classList.toggle('is-visible', active);
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
  const selectedHint = citaForm.querySelector('[data-cliente-selected-hint]');
  const searchFeedback = citaForm.querySelector('[data-cliente-search-feedback]');
  const searchUrl = citaForm.dataset.clientSearchUrl || '';

  function syncMode() {
    const mode = modeSel ? modeSel.value : 'manual';
    const existingMode = mode === 'existente';

    existentes.forEach((el) => {
      el.style.display = existingMode ? 'grid' : 'none';
    });

    manuales.forEach((el) => {
      el.style.display = existingMode ? 'none' : 'grid';
      el.querySelectorAll('input').forEach((input) => {
        input.required = !existingMode;
      });
    });

    if (!existingMode && hiddenClienteId) {
      hiddenClienteId.value = '';
      setSearchFeedback('');
    }

    syncSelectedHint();
  }

  function setSearchFeedback(message, isError) {
    if (!searchFeedback) return;
    searchFeedback.textContent = message || '';
    searchFeedback.classList.toggle('is-error', !!isError);
  }

  function syncSelectedHint() {
    if (!selectedHint) return;
    const hasCliente = hiddenClienteId && hiddenClienteId.value !== '';
    selectedHint.style.display = hasCliente ? 'block' : 'none';
  }

  function sanitize(value) {
    const text = value == null ? '' : String(value);
    return text.replace(/[&<>'"]/g, function (char) {
      if (char === '&') return '&amp;';
      if (char === '<') return '&lt;';
      if (char === '>') return '&gt;';
      if (char === '"') return '&quot;';
      return '&#39;';
    });
  }

  if (modeSel) {
    modeSel.addEventListener('change', syncMode);
    syncMode();
  }

  let debounceTimer;

  async function doSearch(query) {
    if (!resultsBox) return;

    if (!searchUrl) {
      setSearchFeedback('Ruta de búsqueda de clientes no disponible.', true);
      return;
    }

    if (query.length < 2) {
      resultsBox.innerHTML = '';
      resultsBox.style.display = 'none';
      setSearchFeedback('');
      return;
    }

    const response = await fetch(searchUrl + '?q=' + encodeURIComponent(query), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });

    let payload;
    try {
      payload = await response.json();
    } catch (error) {
      throw new Error('La respuesta del buscador no es JSON válido.');
    }

    if (!response.ok || !payload || !payload.ok) {
      throw new Error((payload && payload.message) ? payload.message : 'No se pudo buscar clientes en este momento.');
    }

    const items = Array.isArray(payload.data) ? payload.data : [];

    if (!items.length) {
      resultsBox.innerHTML = '<div class="autocomplete-item no-click">Sin coincidencias</div>';
      resultsBox.style.display = 'block';
      setSearchFeedback('No encontramos clientes con ese dato.');
      return;
    }

    resultsBox.innerHTML = items.map((item) => {
      const display = sanitize(item.display || (item.nombre_completo + ' · ' + item.telefono));
      return '<button type="button" class="autocomplete-item" data-id="' + sanitize(item.id) + '" data-display="' + display + '">' + display + '</button>';
    }).join('');
    resultsBox.style.display = 'block';
    setSearchFeedback('');
  }

  if (searchInput && resultsBox) {
    searchInput.addEventListener('input', function () {
      if (hiddenClienteId) hiddenClienteId.value = '';
      syncSelectedHint();
      setSearchFeedback('Selecciona un cliente de la lista para continuar.', false);

      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(function () {
        doSearch(searchInput.value.trim()).catch(function (error) {
          const message = (error && error.message) ? error.message : 'Error al buscar clientes.';
          resultsBox.innerHTML = '<div class="autocomplete-item no-click">' + sanitize(message) + '</div>';
          resultsBox.style.display = 'block';
          setSearchFeedback(message, true);
        });
      }, 250);
    });

    resultsBox.addEventListener('click', function (event) {
      const target = event.target.closest('[data-id]');
      if (!target) return;
      if (hiddenClienteId) hiddenClienteId.value = target.dataset.id || '';
      searchInput.value = target.dataset.display || '';
      resultsBox.style.display = 'none';
      setSearchFeedback('Cliente seleccionado correctamente.');
      syncSelectedHint();
    });

    document.addEventListener('click', function (event) {
      if (!event.target.closest('[data-cliente-autocomplete]')) {
        resultsBox.style.display = 'none';
      }
    });
  }

  citaForm.addEventListener('submit', function (event) {
    const isExistingMode = modeSel && modeSel.value === 'existente';
    if (!isExistingMode) return;

    const hasId = hiddenClienteId && hiddenClienteId.value !== '';
    if (hasId) return;

    event.preventDefault();
    setSearchFeedback('Debes seleccionar un cliente existente válido de la lista.', true);
    if (searchInput) searchInput.focus();
  });

  syncSelectedHint();

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

  if (openModalBtn && modal) {
    openModalBtn.addEventListener('click', function () {
      setModal(true);
      if (feedback) feedback.textContent = '';
    });
  }

  if (closeModalBtn && modal) {
    closeModalBtn.addEventListener('click', function () {
      setModal(false);
    });
  }

  if (modal) {
    modal.addEventListener('click', function (event) {
      if (event.target === modal) setModal(false);
    });
  }

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') setModal(false);
  });

  if (prospectForm && prospectUrl) {
    bindResponsableScope(prospectForm);

    prospectForm.addEventListener('submit', async function (event) {
      event.preventDefault();
      if (feedback) feedback.textContent = '';

      const formData = new FormData(prospectForm);
      const sucursalInput = citaForm.querySelector('[name="sucursal_id"]');
      if (sucursalInput) formData.append('sucursal_id', sucursalInput.value);
      const csrf = citaForm.querySelector('[name="_csrf"]');
      if (csrf) formData.append('_csrf', csrf.value);

      try {
        const response = await fetch(prospectUrl, {
          method: 'POST',
          body: formData,
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        let payload;
        try {
          payload = await response.json();
        } catch (error) {
          throw new Error('Respuesta inválida del servidor al crear prospecto.');
        }

        if (!response.ok || !payload.ok) {
          if (feedback) feedback.textContent = payload.message || 'No se pudo crear el prospecto.';
          return;
        }

        if (hiddenClienteId) hiddenClienteId.value = String(payload.data.id || '');
        if (searchInput) searchInput.value = payload.data.display || '';
        if (modeSel) modeSel.value = 'existente';

        syncMode();
        syncSelectedHint();
        setSearchFeedback('Prospecto creado y seleccionado automáticamente.');
        prospectForm.reset();
        bindResponsableScope(prospectForm);
        setModal(false);
      } catch (error) {
        if (feedback) {
          feedback.textContent = (error && error.message) ? error.message : 'Error inesperado al guardar prospecto.';
        }
      }
    });
  }
})();
