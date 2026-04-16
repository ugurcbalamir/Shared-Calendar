class PersonalOverviewCalendar {
  constructor(config) {
    this.config = config || {};
    this.dom = {
      grid: document.getElementById('overview-grid'),
      monthLabel: document.querySelector('[data-overview-month]'),
      prev: document.querySelector('[data-overview-prev]'),
      next: document.querySelector('[data-overview-next]'),
      today: document.querySelector('[data-overview-today]'),
      detail: document.querySelector('[data-overview-detail]'),
      detailBody: document.querySelector('[data-overview-detail-body]'),
      tooltip: null,
      filterSelectAll: document.querySelector('[data-overview-select-all]'),
      filterClear: document.querySelector('[data-overview-clear]'),
      filterNotesOnly: document.querySelector('[data-overview-notes-only]'),
      filterAvailabilityOnly: document.querySelector('[data-overview-availability-only]'),
      filtersToggle: document.querySelector('[data-overview-toggle-filters]'),
      filters: document.querySelector('[data-overview-filters]'),
    };
    this.calendars = this.config.calendars || [];
    this.cache = {};
    this.timezone = null;
    this.today = null;
    this.currentYear = null;
    this.currentMonth = null;
    this.selectedDate = null;
  }

  async init() {
    if (!this.dom.grid) return;
    this.today = new Date();
    this.currentYear = this.today.getFullYear();
    this.currentMonth = this.today.getMonth() + 1;
    this.buildTooltip();
    this.bindToolbar();
    this.bindFilters();
    this.renderGrid(this.currentYear, this.currentMonth);
    this.loadMonth(this.currentYear, this.currentMonth);
  }

  buildTooltip() {
    const tooltip = document.createElement('div');
    tooltip.className = 'overview-tooltip';
    tooltip.hidden = true;
    document.body.appendChild(tooltip);
    this.dom.tooltip = tooltip;
  }

  bindToolbar() {
    this.dom.prev?.addEventListener('click', () => {
      const prevMonth = new Date(this.currentYear, this.currentMonth - 2, 1);
      this.currentYear = prevMonth.getFullYear();
      this.currentMonth = prevMonth.getMonth() + 1;
      this.renderGrid(this.currentYear, this.currentMonth);
      this.loadMonth(this.currentYear, this.currentMonth);
    });

    this.dom.next?.addEventListener('click', () => {
      const nextMonth = new Date(this.currentYear, this.currentMonth, 1);
      this.currentYear = nextMonth.getFullYear();
      this.currentMonth = nextMonth.getMonth() + 1;
      this.renderGrid(this.currentYear, this.currentMonth);
      this.loadMonth(this.currentYear, this.currentMonth);
    });

    this.dom.today?.addEventListener('click', () => {
      const today = new Date();
      this.currentYear = today.getFullYear();
      this.currentMonth = today.getMonth() + 1;
      this.renderGrid(this.currentYear, this.currentMonth);
      this.loadMonth(this.currentYear, this.currentMonth);
    });
  }

  bindFilters() {
    const calendarInputs = document.querySelectorAll('[data-overview-calendar]');
    calendarInputs.forEach((input) => {
      input.addEventListener('change', () => this.loadMonth(this.currentYear, this.currentMonth));
    });

    this.dom.filterSelectAll?.addEventListener('click', () => {
      calendarInputs.forEach((input) => (input.checked = true));
      this.loadMonth(this.currentYear, this.currentMonth);
    });

    this.dom.filterClear?.addEventListener('click', () => {
      calendarInputs.forEach((input) => (input.checked = false));
      this.loadMonth(this.currentYear, this.currentMonth);
    });

    this.dom.filterNotesOnly?.addEventListener('change', () => this.paintAllDays());
    this.dom.filterAvailabilityOnly?.addEventListener('change', () => this.paintAllDays());

    this.dom.filtersToggle?.addEventListener('click', () => {
      if (!this.dom.filters) return;
      const current = window.getComputedStyle(this.dom.filters).display;
      if (current === 'none') {
        this.dom.filters.style.display = 'block';
        this.dom.filters.removeAttribute('hidden');
      } else {
        this.dom.filters.style.display = 'none';
        this.dom.filters.setAttribute('hidden', '');
      }
    });
  }

  renderGrid(year, month) {
    if (!this.dom.grid) return;
    this.dom.grid.innerHTML = '';

    // Create a safe "noon" date for calculations to avoid 00:00 shifts
    const firstDay = new Date(year, month - 1, 1, 12, 0, 0);
    const startDay = (firstDay.getDay() + 6) % 7; // Monday first
    const daysInMonth = new Date(year, month, 0).getDate();

    const prevMonthDays = new Date(year, month - 1, 0).getDate();
    const totalCells = Math.ceil((startDay + daysInMonth) / 7) * 7;

    const renderCell = (dateObj, otherMonth = false) => {
      const iso = this.formatDate(dateObj);
      const cell = document.createElement('div');
      cell.className = `calendar-day${otherMonth ? ' other-month' : ''}`;
      cell.dataset.date = iso;

      const labelWrap = document.createElement('div');
      labelWrap.className = 'calendar-day-label';
      const label = document.createElement('span');
      label.textContent = dateObj.getDate();
      if (iso === this.formatDate(this.today)) {
        label.classList.add('calendar-day-today');
      }
      labelWrap.appendChild(label);
      cell.appendChild(labelWrap);

      const meta = document.createElement('div');
      meta.className = 'calendar-day-meta';
      cell.appendChild(meta);

      const markers = document.createElement('div');
      markers.className = 'calendar-day-markers';
      cell.appendChild(markers);

      cell.addEventListener('click', () => this.toggleSelection(iso, cell));
      cell.addEventListener('mouseenter', (e) => this.handleHover(e, iso, cell));
      cell.addEventListener('mousemove', (e) => this.moveTooltip(e));
      cell.addEventListener('mouseleave', () => this.hideTooltip());

      this.dom.grid.appendChild(cell);
    };

    for (let i = startDay - 1; i >= 0; i--) {
      // Use noon for previous month days too
      const date = new Date(year, month - 2, prevMonthDays - i, 12, 0, 0);
      renderCell(date, true);
    }

    for (let day = 1; day <= daysInMonth; day++) {
      // Use noon for current month days
      renderCell(new Date(year, month - 1, day, 12, 0, 0));
    }

    const trailing = totalCells - startDay - daysInMonth;
    for (let d = 1; d <= trailing; d++) {
      // Use noon for next month days
      const date = new Date(year, month, d, 12, 0, 0);
      renderCell(date, true);
    }

    const monthLabel = new Date(year, month - 1, 1, 12, 0, 0).toLocaleDateString(this.config.lang || 'tr', { month: 'long', year: 'numeric' });
    if (this.dom.monthLabel) this.dom.monthLabel.textContent = monthLabel;
  }

  async loadMonth(year, month) {
    const params = new URLSearchParams();
    params.set('year', year);
    params.set('month', month);
    const calendars = this.getSelectedCalendars();
    if (calendars.length > 0) {
      params.set('calendars', calendars.join(','));
    }

    try {
      const endpoint = `${document.body.dataset.apiBase}/api/overview/availability?${params.toString()}`;
      const res = await fetch(endpoint, { cache: 'no-store' });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();
      this.cache = data.days || {};
      this.paintAllDays();
      this.resetSelection();
    } catch (err) {
      console.error('Overview calendar load failed', err);
    }
  }

  getSelectedCalendars() {
    const inputs = document.querySelectorAll('[data-overview-calendar]');
    const selected = [];
    inputs.forEach((i) => {
      if (i.checked) selected.push(parseInt(i.value, 10));
    });
    return selected;
  }

  paintAllDays() {
    const cells = this.dom.grid?.querySelectorAll('.calendar-day');
    cells?.forEach((cell) => {
      const date = cell.dataset.date;
      this.paintDay(date, cell);
    });
  }

  paintDay(date, cell) {
    const entry = this.cache?.[date];
    const meta = cell.querySelector('.calendar-day-meta');
    const markers = cell.querySelector('.calendar-day-markers');
    const filters = {
      notesOnly: this.dom.filterNotesOnly?.checked,
      availabilityOnly: this.dom.filterAvailabilityOnly?.checked,
    };

    cell.classList.remove('overview-muted');

    if (entry) {
      const counts = entry.full_day_counts || { available: 0, busy: 0 };
      const total = counts.available + counts.busy;
      if (total > 0) {
        const availPct = (counts.available / total) * 100;
        cell.style.background = `linear-gradient(135deg, rgba(40, 167, 69, 0.2) 0%, rgba(40, 167, 69, 0.2) ${availPct}%, rgba(220, 53, 69, 0.2) ${availPct}%, rgba(220, 53, 69, 0.2) 100%)`;
      } else {
        cell.style.background = '';
      }

      const markerContent = [];
      markers.innerHTML = '';
      const renderPill = (type, count, label) => {
        if (count <= 0) return;
        const pill = document.createElement('span');
        pill.className = `marker-pill marker-${type}`;
        pill.innerHTML = `<span class="dot"></span> ${count}`;
        pill.dataset.tooltip = label;
        pill.addEventListener('mouseenter', (e) => this.showTooltip(e, label));
        pill.addEventListener('mouseleave', () => this.hideTooltip());
        markers.appendChild(pill);
      };

      renderPill('available', entry.hourly_summary?.available || 0, this.config.labels.availableHourly);
      renderPill('busy', entry.hourly_summary?.busy || 0, this.config.labels.busyHourly);
      renderPill('note', entry.hourly_summary?.notes || 0, this.config.labels.notesLabel);

      const hasNotes = (entry.hour_notes && entry.hour_notes.length > 0) || (entry.day_notes && entry.day_notes.length > 0);
      const hasAvailability = total > 0 || (entry.hourly_summary?.available || 0) > 0 || (entry.hourly_summary?.busy || 0) > 0;

      if (filters.notesOnly && !hasNotes) {
        cell.classList.add('overview-muted');
      }
      if (filters.availabilityOnly && !hasAvailability) {
        cell.classList.add('overview-muted');
      }

      if (meta) {
        meta.innerHTML = '';
      }
    } else {
      cell.style.background = '';
      markers.innerHTML = '';
      if (filters.notesOnly || filters.availabilityOnly) {
        cell.classList.add('overview-muted');
      }
    }
  }

  toggleSelection(date, cell) {
    if (this.selectedDate === date) {
      this.selectedDate = null;
      this.dom.grid.querySelectorAll('.calendar-day').forEach((c) => c.classList.remove('selected'));
      if (this.dom.detail) this.dom.detail.hidden = true;
      return;
    }

    this.selectedDate = date;
    this.dom.grid.querySelectorAll('.calendar-day').forEach((c) => c.classList.remove('selected'));
    cell.classList.add('selected');
    this.renderDetails(date);
  }

  resetSelection() {
    this.selectedDate = null;
    this.dom.grid.querySelectorAll('.calendar-day')?.forEach((c) => c.classList.remove('selected'));
    if (this.dom.detail) this.dom.detail.hidden = true;
  }

  renderDetails(date) {
    if (!this.dom.detail || !this.dom.detailBody) return;
    const entry = this.cache?.[date];
    if (!entry) {
      this.dom.detail.hidden = true;
      return;
    }

    const detailDateLabel = document.querySelector('[data-overview-detail-date]');
    if (detailDateLabel) {
      const [y, m, d] = date.split('-').map(Number);
      const displayDate = new Date(y, m - 1, d, 12, 0, 0).toLocaleDateString(this.config.lang || 'tr', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
      });
      detailDateLabel.textContent = displayDate;
    }

    const createList = (items) => {
      if (!items || items.length === 0) return `<p class="form-helper">${this.config.labels.none}</p>`;
      return `<ul class="overview-list">${items.map((i) => `<li>${i}</li>`).join('')}</ul>`;
    };

    const fullDayLines = (entry.full_day_statuses || []).map((s) => `${this.escape(s.calendar_name)} – ${s.status === 'busy' ? this.config.labels.busy : this.config.labels.available}`);
    const hourLines = (entry.hourly_details || []).map((h) => `${this.escape(h.time_range)} – ${this.escape(h.calendar_name)} – ${h.status === 'busy' ? this.config.labels.busy : this.config.labels.available}`);

    const noteLines = [];
    (entry.day_notes || []).forEach((n) => noteLines.push(`${this.escape(n.calendar_name)} – ${this.escape(n.text)}`));
    (entry.hour_notes || []).forEach((n) => noteLines.push(`${this.escape(n.time_range)} – ${this.escape(n.calendar_name)} – ${this.escape(n.text)}`));

    this.dom.detailBody.innerHTML = `
      <div class="overview-detail-section">
        <h4>${this.config.labels.fullDayHeading}</h4>
        ${createList(fullDayLines)}
      </div>
      <div class="overview-detail-section">
        <h4>${this.config.labels.notesHeading}</h4>
        ${createList(noteLines)}
      </div>
      <div class="overview-detail-section">
        <h4>${this.config.labels.hourlyHeading}</h4>
        ${createList(hourLines)}
      </div>
    `;

    this.dom.detail.hidden = false;
  }

  handleHover(event, date, cell) {
    const entry = this.cache?.[date];
    if (!entry) return;
    const target = event.target;
    const isMarkerArea = target.classList.contains('marker-pill') || target.closest('.calendar-day-markers');

    if (isMarkerArea) {
      const lines = [];
      (entry.hourly_details || []).forEach((h) => {
        if (!h.status && !h.time_range) return;
        const statusText = h.status === 'busy' ? this.config.labels.busy : this.config.labels.available;
        lines.push(`${this.escape(h.time_range)} · ${this.escape(h.calendar_name)} – ${statusText}`);
      });
      (entry.hour_notes || []).forEach((n) => {
        lines.push(`${this.escape(n.time_range)} · ${this.escape(n.calendar_name)} – ${this.escape(n.text)}`);
      });
      if (lines.length > 0) this.showTooltip(event, lines.join('<br>'));
      return;
    }

    const fullDay = (entry.full_day_statuses || []).map((s) => `${this.escape(s.calendar_name)} – ${s.status === 'busy' ? this.config.labels.busy : this.config.labels.available}`);
    if (fullDay.length > 0) {
      this.showTooltip(event, fullDay.join('<br>'));
    }
  }

  showTooltip(event, content) {
    if (!this.dom.tooltip || !content) return;
    this.dom.tooltip.innerHTML = content;
    this.dom.tooltip.hidden = false;
    this.moveTooltip(event);
  }

  moveTooltip(event) {
    if (!this.dom.tooltip || this.dom.tooltip.hidden) return;
    this.dom.tooltip.style.left = `${event.pageX + 12}px`;
    this.dom.tooltip.style.top = `${event.pageY + 12}px`;
  }

  hideTooltip() {
    if (!this.dom.tooltip) return;
    this.dom.tooltip.hidden = true;
  }

  formatDate(date) {
    // Explicitly construct YYYY-MM-DD from the date object, ignoring time
    const year = date.getFullYear();
    const month = this.pad(date.getMonth() + 1);
    const day = this.pad(date.getDate());
    return `${year}-${month}-${day}`;
  }

  pad(num) {
    return String(num).padStart(2, '0');
  }


  escape(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }
}

window.addEventListener('DOMContentLoaded', () => {
  const config = window.overviewConfig || {};
  const overview = new PersonalOverviewCalendar(config);
  overview.init();
});
