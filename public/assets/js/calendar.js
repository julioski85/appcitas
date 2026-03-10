(function () {
  const app = document.getElementById('calendarApp');
  if (!app) return;

  const titleEl = document.getElementById('calendarTitle');
  const viewButtons = document.querySelectorAll('[data-cal-view]');
  const navButtons = document.querySelectorAll('[data-cal-action]');
  let currentView = 'month';
  let currentDate = new Date();

  function pad(n) { return String(n).padStart(2, '0'); }
  function ymd(date) { return `${date.getFullYear()}-${pad(date.getMonth()+1)}-${pad(date.getDate())}`; }
  function formatHour(date) { return `${pad(date.getHours())}:${pad(date.getMinutes())}`; }

  function rangeForView(date, view) {
    const d = new Date(date);
    if (view === 'month') {
      const start = new Date(d.getFullYear(), d.getMonth(), 1);
      const end = new Date(d.getFullYear(), d.getMonth() + 1, 0);
      return { start, end };
    }
    if (view === 'week') {
      const day = d.getDay();
      const diff = day === 0 ? -6 : 1 - day;
      const start = new Date(d);
      start.setDate(d.getDate() + diff);
      const end = new Date(start);
      end.setDate(start.getDate() + 6);
      return { start, end };
    }
    return { start: new Date(d), end: new Date(d) };
  }

  async function fetchEvents() {
    const range = rangeForView(currentDate, currentView);
    const url = new URL(app.dataset.endpoint, window.location.origin);
    url.searchParams.set('start', ymd(range.start));
    url.searchParams.set('end', ymd(range.end));
    if (app.dataset.sucursal) url.searchParams.set('sucursal_id', app.dataset.sucursal);
    if (app.dataset.estatus) url.searchParams.set('estatus', app.dataset.estatus);
    const res = await fetch(url.toString(), { credentials: 'same-origin' });
    const json = await res.json();
    return json.data || [];
  }

  function createLink(date, time) {
    const url = new URL(app.dataset.createUrl, window.location.origin);
    url.searchParams.set('date', date);
    if (time) {
      url.searchParams.set('time', time);
      const [h, m] = time.split(':').map(Number);
      const end = new Date(2000,0,1,h,m);
      end.setMinutes(end.getMinutes() + 30);
      url.searchParams.set('end', `${pad(end.getHours())}:${pad(end.getMinutes())}`);
    }
    if (app.dataset.sucursal) url.searchParams.set('sucursal_id', app.dataset.sucursal);
    return url.toString();
  }

  function groupEvents(events) {
    const byDate = {};
    events.forEach(ev => {
      const date = ev.start.slice(0, 10);
      byDate[date] = byDate[date] || [];
      byDate[date].push(ev);
    });
    Object.keys(byDate).forEach(date => {
      byDate[date].sort((a,b) => a.start.localeCompare(b.start));
    });
    return byDate;
  }

  function renderMonth(events) {
    const byDate = groupEvents(events);
    const first = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
    const last = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
    const startOffset = (first.getDay() + 6) % 7;
    const daysInMonth = last.getDate();

    const monthName = currentDate.toLocaleDateString('es-MX', { month: 'long', year: 'numeric' });
    titleEl.textContent = monthName.charAt(0).toUpperCase() + monthName.slice(1);

    let html = '<div class="month-grid">';
    ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'].forEach(day => {
      html += `<div class="month-head">${day}</div>`;
    });

    for (let i = 0; i < startOffset; i++) {
      html += '<div class="month-cell muted"></div>';
    }

    for (let day = 1; day <= daysInMonth; day++) {
      const date = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
      const key = ymd(date);
      const dayEvents = byDate[key] || [];
      html += `<a class="month-cell" href="${createLink(key, '')}">
        <div class="month-date">${day}</div>
        <div class="month-events">`;
      dayEvents.slice(0, 4).forEach(ev => {
        const label = ev.is_block ? 'Bloqueo' : ev.cliente_nombre;
        html += `<div class="event-pill ${ev.is_block ? 'event-pill-block' : ''}" style="border-left-color:${ev.backgroundColor}">
          <span>${ev.start.slice(11,16)}</span> ${label}
        </div>`;
      });
      if (dayEvents.length > 4) {
        html += `<div class="event-more">+${dayEvents.length - 4} más</div>`;
      }
      html += `</div></a>`;
    }

    const cells = startOffset + daysInMonth;
    const remaining = (7 - (cells % 7)) % 7;
    for (let i = 0; i < remaining; i++) html += '<div class="month-cell muted"></div>';
    html += '</div>';
    app.innerHTML = html;
  }

  function renderTimeGrid(events, view) {
    const byDate = groupEvents(events);
    const range = rangeForView(currentDate, view);
    const dates = [];
    const temp = new Date(range.start);
    while (temp <= range.end) {
      dates.push(new Date(temp));
      temp.setDate(temp.getDate() + 1);
    }

    titleEl.textContent = view === 'week'
      ? `${range.start.toLocaleDateString('es-MX')} - ${range.end.toLocaleDateString('es-MX')}`
      : range.start.toLocaleDateString('es-MX', { weekday:'long', day:'numeric', month:'long', year:'numeric' });

    let html = '<div class="time-grid"><table class="time-table"><thead><tr><th>Hora</th>';
    dates.forEach(d => {
      html += `<th>${d.toLocaleDateString('es-MX', { weekday:'short', day:'numeric', month:'short' })}</th>`;
    });
    html += '</tr></thead><tbody>';

    for (let hour = 8; hour < 20; hour++) {
      const hourStr = `${pad(hour)}:00`;
      html += `<tr><td class="time-col">${hourStr}</td>`;
      dates.forEach(d => {
        const key = ymd(d);
        const cellEvents = (byDate[key] || []).filter(ev => ev.start.slice(11,13) === pad(hour));
        const hasBlock = (byDate[key] || []).some(ev => ev.is_block && ev.start.slice(11,16) < `${pad(hour+1)}:00` && ev.end.slice(11,16) > `${pad(hour)}:00`);
        html += `<td class="time-slot">${hasBlock ? '' : `<a class=\"slot-link\" href=\"${createLink(key, hourStr)}\">+</a>`}`;
        cellEvents.forEach(ev => {
          const tag = ev.url && !ev.is_block ? 'a' : 'div';
          const href = ev.url && !ev.is_block ? ` href=\"${ev.url}\"` : '';
          const title = ev.is_block ? 'Horario no disponible' : ev.cliente_nombre;
          html += `<${tag} class=\"time-event ${ev.is_block ? 'time-event-block' : ''}\"${href} style=\"border-left-color:${ev.backgroundColor}\">
            <strong>${ev.start.slice(11,16)}-${ev.end.slice(11,16)}</strong>
            <span>${title}</span>
            <small>${ev.servicio}</small>
          </${tag}>`;
        });
        html += `</td>`;
      });
      html += '</tr>';
    }

    html += '</tbody></table></div>';
    app.innerHTML = html;
  }

  async function render() {
    const events = await fetchEvents();
    if (currentView === 'month') renderMonth(events);
    if (currentView === 'week') renderTimeGrid(events, 'week');
    if (currentView === 'day') renderTimeGrid(events, 'day');

    viewButtons.forEach(btn => {
      btn.classList.toggle('is-active', btn.dataset.calView === currentView);
    });
  }

  viewButtons.forEach(btn => {
    btn.addEventListener('click', function () {
      currentView = btn.dataset.calView;
      render();
    });
  });

  navButtons.forEach(btn => {
    btn.addEventListener('click', function () {
      const action = btn.dataset.calAction;
      if (action === 'today') currentDate = new Date();
      if (action === 'prev') {
        if (currentView === 'month') currentDate.setMonth(currentDate.getMonth() - 1);
        else if (currentView === 'week') currentDate.setDate(currentDate.getDate() - 7);
        else currentDate.setDate(currentDate.getDate() - 1);
      }
      if (action === 'next') {
        if (currentView === 'month') currentDate.setMonth(currentDate.getMonth() + 1);
        else if (currentView === 'week') currentDate.setDate(currentDate.getDate() + 7);
        else currentDate.setDate(currentDate.getDate() + 1);
      }
      render();
    });
  });

  render();
})();
