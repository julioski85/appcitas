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

  const programaForm = document.querySelector('[data-programa-form]');
  if (programaForm) {
    const scopeSelect = programaForm.querySelector('[data-programa-scope]');
    const sucursalGroup = programaForm.querySelector('[data-programa-sucursal]');
    const sucursalSelect = sucursalGroup ? sucursalGroup.querySelector('select') : null;

    function syncProgramaScope() {
      if (!scopeSelect || !sucursalGroup || !sucursalSelect) return;
      const isSucursal = scopeSelect.value === 'sucursal';
      sucursalGroup.style.display = isSucursal ? 'grid' : 'none';
      sucursalSelect.required = isSucursal;
      if (!isSucursal) sucursalSelect.value = '';
    }

    if (scopeSelect) {
      scopeSelect.addEventListener('change', syncProgramaScope);
      syncProgramaScope();
    }
  }

  const programaSelect = citaForm.querySelector('[data-programa-select]');
  const sucursalInput = citaForm.querySelector('[name="sucursal_id"]');
  const programasUrl = citaForm.dataset.programasUrl || '';


  const horaInicioInput = citaForm.querySelector('[data-hora-inicio]');
  const horaFinInput = citaForm.querySelector('[data-hora-fin]');

  function syncHorarioSucursal() {
    if (!horaInicioInput || !horaFinInput) return;

    let openHour = '08:00';
    let closeHour = '20:00';

    if (sucursalInput && sucursalInput.tagName === 'SELECT') {
      const selectedOption = sucursalInput.options[sucursalInput.selectedIndex];
      if (selectedOption) {
        openHour = (selectedOption.dataset.openHour || openHour).slice(0, 5);
        closeHour = (selectedOption.dataset.closeHour || closeHour).slice(0, 5);
      }
    }

    horaInicioInput.min = openHour;
    horaInicioInput.max = closeHour;
    horaFinInput.min = openHour;
    horaFinInput.max = closeHour;

    if (horaInicioInput.value && horaInicioInput.value < openHour) {
      horaInicioInput.value = openHour;
    }

    if (horaFinInput.value && horaFinInput.value > closeHour) {
      horaFinInput.value = closeHour;
    }
  }

  if (programaSelect && sucursalInput && programasUrl) {
    function resetProgramasSelect(disabled) {
      programaSelect.innerHTML = '<option value="">Sin programa</option>';
      programaSelect.disabled = !!disabled;
    }

    async function refreshProgramas() {
      const sucursalId = (sucursalInput.value || '').trim();
      if (!sucursalId) {
        resetProgramasSelect(true);
        return;
      }

      const selected = programaSelect.value;
      const selectedOption = programaSelect.options[programaSelect.selectedIndex] || null;
      const response = await fetch(programasUrl + '?sucursal_id=' + encodeURIComponent(sucursalInput.value), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const payload = await response.json();
      if (!response.ok || !payload.ok) {
        resetProgramasSelect(false);
        return;
      }

      const options = Array.isArray(payload.data) ? payload.data : [];
      let html = '<option value="">Sin programa</option>' + options.map((item) => {
        const selectedAttr = String(item.id) === String(selected) ? ' selected' : '';
        return '<option value="' + sanitize(item.id) + '"' + selectedAttr + '>' + sanitize(item.nombre) + '</option>';
      }).join('');

      const selectedStillVisible = options.some((item) => String(item.id) === String(selected));
      if (selected && !selectedStillVisible && selectedOption) {
        html += '<option value="' + sanitize(selected) + '" selected>' + sanitize(selectedOption.textContent || 'Programa actual') + '</option>';
      }

      programaSelect.innerHTML = html;
      programaSelect.disabled = false;
    }

    sucursalInput.addEventListener('change', function () {
      syncHorarioSucursal();
      refreshProgramas().catch(function () {});
    });

    syncHorarioSucursal();
    refreshProgramas().catch(function () {
      resetProgramasSelect(false);
    });
  }


  if (sucursalInput && (!programaSelect || !programasUrl)) {
    sucursalInput.addEventListener('change', syncHorarioSucursal);
    syncHorarioSucursal();
  }

  const bloqueoForm = document.querySelector('[data-bloqueo-form]');
  if (bloqueoForm) {
    const tipo = bloqueoForm.querySelector('[data-tipo-bloqueo]');
    const fechaGroup = bloqueoForm.querySelector('[data-bloqueo-fecha]');
    const diaGroup = bloqueoForm.querySelector('[data-bloqueo-dia]');
    const fechaInput = fechaGroup ? fechaGroup.querySelector('input') : null;
    const diaInput = diaGroup ? diaGroup.querySelector('select') : null;

    function syncBloqueoType() {
      const value = tipo ? tipo.value : 'fecha_especifica';
      const showFecha = value === 'fecha_especifica';
      const showDia = value === 'recurrente_semanal';

      if (fechaGroup) fechaGroup.style.display = showFecha ? 'grid' : 'none';
      if (diaGroup) diaGroup.style.display = showDia ? 'grid' : 'none';
      if (fechaInput) fechaInput.required = showFecha;
      if (diaInput) diaInput.required = showDia;
    }

    if (tipo) {
      tipo.addEventListener('change', syncBloqueoType);
      syncBloqueoType();
    }
  }

})();
