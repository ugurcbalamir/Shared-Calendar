class CalendarPage {
  constructor(config) {
    this.config = config || {};
    this.timezone = null;
    this.today = null;
    this.currentYear = null;
    this.currentMonth = null;
    this.selectedDate = null;
    this.selectedCell = null;
    this.cache = {};
    this.pollingInterval = null;

    this.dom = {
      grid: document.getElementById('calendar-grid'),
      monthLabel: document.querySelector('[data-month-label]'),
      hourly: document.getElementById('hourly'),
      hourPlaceholder: document.querySelector('[data-hour-empty]'),
      hourPanel: document.querySelector('[data-hour-panel]'),
      selectedDateText: document.querySelector('[data-selected-date]'),
      dayActions: document.getElementById('day-actions'),
      dayNoteEditor: document.getElementById('day-note-editor'),
      hourHelp: document.querySelector('[data-hour-help]'),
      prevMonth: document.querySelector('[data-prev-month]'),
      nextMonth: document.querySelector('[data-next-month]'),
      today: document.querySelector('[data-today]'),
      availableCount: document.querySelector('[data-count-available]'),
      busyCount: document.querySelector('[data-count-busy]'),
      notesCount: document.querySelector('[data-count-notes]'),
      upcoming: document.getElementById('upcoming-list'),
      notes: document.getElementById('note-list'),
      alert: document.querySelector('[data-calendar-alert]'),
      tooltip: null,
    };

    this.labels = {
      available: 'Available',
      busy: 'Busy',
      clear: 'Clear',
      note: 'Note',
      save: 'Save',
      cancel: 'Cancel',
      ...(this.config.labels || {}),
    };

    this.texts = this.config.texts || {};
    this.errors = this.config.errors || {};
  }

  async init() {
    if (!this.dom.grid) {
      this.showAlert(this.errors.loadFailed || 'Calendar component could not be loaded.');
      return;
    }
    if (!this.config.calendarId) {
      this.showAlert(this.errors.missingConfig || 'Calendar configuration missing.');
      return;
    }

    await this.syncTimeWithIP();
    this.today = this.toTimezoneDate(this.timezone);
    this.currentYear = this.today.getFullYear();
    this.currentMonth = this.today.getMonth() + 1;

    this.toggleHourPanel(false);
    this.bindToolbar();

    // Initial load
    await this.loadMonth(this.currentYear, this.currentMonth, null);

    // Start Polling
    this.startPolling();
  }

  startPolling() {
    if (this.pollingInterval) clearInterval(this.pollingInterval);
    this.pollingInterval = setInterval(() => {
        this.fetchData(this.currentYear, this.currentMonth, true);
    }, 5000);
  }

  // Helpers
  formatDate(date) {
    const year = date.getFullYear();
    const month = this.pad(date.getMonth() + 1);
    const day = this.pad(date.getDate());
    return `${year}-${month}-${day}`;
  }

  pad(num) {
    return String(num).padStart(2, '0');
  }

  showAlert(message, type = 'warning') {
    if (!this.dom.alert) return;
    this.dom.alert.hidden = false;
    this.dom.alert.textContent = message;
    this.dom.alert.className = `calendar-alert${type === 'warning' ? ' calendar-alert-warning' : ''}`;
  }

  hideAlert() {
    if (!this.dom.alert) return;
    this.dom.alert.hidden = true;
    this.dom.alert.textContent = '';
    this.dom.alert.className = 'calendar-alert';
  }

  toggleHourPanel(hasSelection) {
    if (this.dom.hourPanel) this.dom.hourPanel.classList.toggle('is-empty', !hasSelection);
    if (this.dom.hourPlaceholder) this.dom.hourPlaceholder.hidden = hasSelection;
    if (this.dom.hourly) this.dom.hourly.hidden = !hasSelection;
    if (!hasSelection && this.dom.dayActions) this.dom.dayActions.style.display = 'none';
  }

  cycleStatus(current) {
    if (!current) return 'available';
    if (current === 'available') return 'busy';
    return null;
  }

  getTodayKey() {
    return this.formatDate(this.toTimezoneDate(this.timezone));
  }

  formatDisplay(dateKey) {
    const [y, m, d] = dateKey.split('-').map(Number);
    const date = new Date(y, m - 1, d);
    return date.toLocaleDateString(this.config.lang || 'en', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
  }

  toTimezoneDate(timezone) {
    if (!timezone) return new Date();
    try {
      const formatter = new Intl.DateTimeFormat('en-US', {
        timeZone: timezone,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
      });
      const parts = formatter.formatToParts(new Date()).reduce((acc, part) => {
        acc[part.type] = part.value;
        return acc;
      }, {});
      return new Date(`${parts.year}-${parts.month}-${parts.day}T${parts.hour}:${parts.minute}:${parts.second}`);
    } catch (err) {
      console.warn('Saat dilimi hesaplanamadı, tarayıcı saati kullanılacak.', err);
      return new Date();
    }
  }

  buildApiUrl(path) {
    const base = this.config.apiBase ?? document.body.dataset.apiBase ?? '';
    const normalizedBase = base.endsWith('/index.php') ? base : `${base}/index.php`;
    return `${normalizedBase}${path}`;
  }

  async syncTimeWithIP() {
    const timezoneApi = 'https://ipapi.co/json/';
    try {
      const response = await fetch(timezoneApi, { cache: 'no-store' });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const data = await response.json();
      if (data && data.timezone) {
        this.timezone = data.timezone;
      }
    } catch (err) {
      this.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    }
  }

  // Data Fetching
  async fetchData(year, month, isPolling = false) {
     const endpoint = this.buildApiUrl(`/api/calendars/${this.config.calendarId}/availability?year=${year}&month=${month}&_t=${new Date().getTime()}`);
     try {
       const response = await fetch(endpoint, { cache: 'no-store' });
       if (!response.ok) throw new Error(`HTTP ${response.status}`);
       const data = await response.json();
       this.cache = data;
       if (!isPolling) this.hideAlert();

       this.refreshView(isPolling);
     } catch (err) {
       console.error('Veri yüklenemedi', err);
       if (!isPolling) this.showAlert(this.errors.loadFailed || 'Failed to load month data.');
     }
  }

  async loadMonth(year, month, preferDate) {
    this.currentYear = year;
    this.currentMonth = month;
    this.updateMonthLabel();

    // Rebuild grid structure first (empty)
    this.renderMonthGridStructure();

    await this.fetchData(year, month);

    const fallbackDate = preferDate && preferDate.startsWith(`${year}-${this.pad(month)}`)
      ? preferDate
      : null;

    if (fallbackDate) {
      const cell = this.dom.grid.querySelector(`[data-date="${fallbackDate}"]`);
      if (cell) this.selectDay(fallbackDate, cell);
    } else {
      this.selectedDate = null;
      this.toggleHourPanel(false);
      if (this.dom.selectedDateText) this.dom.selectedDateText.textContent = '--';
      if (this.dom.hourHelp) this.dom.hourHelp.textContent = this.texts.selectDay || this.texts.hourHelp || 'Select a day on the calendar to view hours.';
    }
  }

  refreshView(isPolling) {
      // Update Month Cells
      Object.keys(this.cache).forEach(dateKey => {
          this.repaintSingleDay(dateKey);
      });

      this.updateSidebar();
      this.populateUpcoming();
      this.populateNotes();

      // Update Detail View if open
      if (this.selectedDate) {
          // Check if user is editing
          const isEditing = this.dom.hourPanel.contains(document.activeElement) &&
                            (document.activeElement.tagName === 'TEXTAREA' || document.activeElement.tagName === 'INPUT');

          if (!isEditing) {
              const entry = this.ensureEntry(this.selectedDate);
              this.renderHours(entry);
              this.setupDayActions(); // Refresh All Day status visual if needed
          }
      }
  }

  updateMonthLabel() {
    const date = new Date(this.currentYear, this.currentMonth - 1, 1);
    const label = date.toLocaleDateString(this.config.lang || 'en', { month: 'long', year: 'numeric' });
    if (this.dom.monthLabel) {
      this.dom.monthLabel.textContent = label.charAt(0).toUpperCase() + label.slice(1);
    }
  }

  renderMonthGridStructure() {
    if (!this.dom.grid) return;
    this.dom.grid.innerHTML = '';
    const firstDay = new Date(this.currentYear, this.currentMonth - 1, 1);
    const startDay = (firstDay.getDay() + 6) % 7;
    const daysInMonth = new Date(this.currentYear, this.currentMonth, 0).getDate();
    const daysInPrevMonth = new Date(this.currentYear, this.currentMonth - 1, 0).getDate();
    const totalCells = Math.ceil((startDay + daysInMonth) / 7) * 7;

    for (let i = startDay - 1; i >= 0; i--) {
      const day = daysInPrevMonth - i;
      const date = new Date(this.currentYear, this.currentMonth - 2, day);
      this.dom.grid.appendChild(this.createDay(date, true));
    }

    for (let day = 1; day <= daysInMonth; day++) {
      const date = new Date(this.currentYear, this.currentMonth - 1, day);
      this.dom.grid.appendChild(this.createDay(date, false));
    }

    const trailing = totalCells - startDay - daysInMonth;
    for (let d = 1; d <= trailing; d++) {
      const date = new Date(this.currentYear, this.currentMonth, d);
      this.dom.grid.appendChild(this.createDay(date, true));
    }
  }

  createDay(dateObj, isOverflow) {
    const dateKey = this.formatDate(dateObj);
    const cell = document.createElement('div');
    cell.className = 'calendar-day';
    if (isOverflow) cell.classList.add('other-month');
    cell.dataset.date = dateKey;

    const header = document.createElement('div');
    header.className = 'calendar-day-label';
    const number = document.createElement('span');
    number.textContent = dateObj.getDate();
    if (this.formatDate(this.today) === dateKey) number.classList.add('calendar-day-today');
    header.appendChild(number);

    cell.appendChild(header);

    const meta = document.createElement('div');
    meta.className = 'calendar-day-meta';
    cell.appendChild(meta);

    const markers = document.createElement('div');
    markers.className = 'calendar-day-markers';
    cell.appendChild(markers);

    // Tooltip Events
    cell.addEventListener('mouseenter', (e) => this.handleCellHover(e, dateKey));
    cell.addEventListener('mouseleave', () => this.hideTooltip());
    cell.addEventListener('mousemove', (e) => this.moveTooltip(e));

    cell.addEventListener('click', () => {
      if (isOverflow) {
        this.loadMonth(dateObj.getFullYear(), dateObj.getMonth() + 1, dateKey);
        return;
      }
      this.selectDay(dateKey, cell);
    });

    return cell;
  }

  handleCellHover(e, dateKey) {
    const entry = this.cache[dateKey];
    if (!entry) return;

    // Combined Tooltip Content
    let content = '';

    // 1. Full Day Info
    const users = entry.full_day_users || [];
    if (users.length > 0) {
        content += `<div class="tooltip-section">`;
        content += users.map(u => {
            const statusLabel = u.status === 'busy' ? this.labels.busy : this.labels.available;
            const colorClass = u.status === 'busy' ? 'text-danger' : 'text-success';
            return `<div class="tooltip-row"><span class="${colorClass}">●</span> <strong>${u.username}</strong> – ${statusLabel}</div>`;
        }).join('');
        content += `</div>`;
    }

    // 2. Hourly Info
    const hourStats = entry.hour_stats || {};
    let availCount = 0;
    let busyCount = 0;

    Object.values(hourStats).forEach(stats => {
       if (stats.available > 0) availCount++;
       if (stats.busy > 0) busyCount++;
    });

    const noteHours = new Set();
    (entry.notes || []).forEach(n => noteHours.add(n.hour));

    const hourlyParts = [];
    if (availCount > 0) hourlyParts.push(`<div class="tooltip-row"><span class="dot dot-success"></span> ${this.labels.available}: ${availCount} ${this.labels.hours}</div>`);
    if (busyCount > 0) hourlyParts.push(`<div class="tooltip-row"><span class="dot dot-danger"></span> ${this.labels.busy}: ${busyCount} ${this.labels.hours}</div>`);
    if (noteHours.size > 0) hourlyParts.push(`<div class="tooltip-row"><span class="dot dot-note"></span> ${this.labels.note}: ${noteHours.size}</div>`);

    if (hourlyParts.length > 0) {
        if (content) content += `<div class="tooltip-divider"></div>`;
        content += `<div class="tooltip-section">`;
        if (this.labels.hourlyAvailability) content += `<div class="tooltip-header">${this.labels.hourlyAvailability}</div>`;
        content += hourlyParts.join('');
        content += `</div>`;
    }

    if (content) {
        this.showTooltip(content, e);
    } else {
        this.hideTooltip();
    }
  }

  showTooltip(content, e) {
     if (!this.dom.tooltip) {
         this.dom.tooltip = document.createElement('div');
         this.dom.tooltip.className = 'calendar-tooltip';
         document.body.appendChild(this.dom.tooltip);
     }
     this.dom.tooltip.innerHTML = content;
     this.dom.tooltip.hidden = false;
     this.moveTooltip(e);
  }

  moveTooltip(e) {
      if (!this.dom.tooltip || this.dom.tooltip.hidden) return;
      const x = e.pageX + 10;
      const y = e.pageY + 10;
      this.dom.tooltip.style.left = `${x}px`;
      this.dom.tooltip.style.top = `${y}px`;
  }

  hideTooltip() {
      if (this.dom.tooltip) this.dom.tooltip.hidden = true;
  }

  paintDay(dateKey, cell) {
    const entry = this.cache[dateKey];
    if (!cell) {
        cell = this.dom.grid.querySelector(`.calendar-day[data-date="${dateKey}"]`);
    }
    if (!cell) return;

    // Aggregate Visual (Background)
    const stats = entry?.stats || { available: 0, busy: 0 };
    const total = stats.available + stats.busy;

    if (total > 0) {
        const availPct = (stats.available / total) * 100;
        cell.style.background = `linear-gradient(135deg, rgba(40, 167, 69, 0.2) 0%, rgba(40, 167, 69, 0.2) ${availPct}%, rgba(220, 53, 69, 0.2) ${availPct}%, rgba(220, 53, 69, 0.2) 100%)`;
    } else {
        cell.style.background = '';
    }

    // Markers
    const markersContainer = cell.querySelector('.calendar-day-markers');
    if (markersContainer) {
        markersContainer.innerHTML = '';

        let availSlots = 0;
        let busySlots = 0;
        const noteSlots = new Set();

        const hourStats = entry?.hour_stats || {};
        Object.values(hourStats).forEach(h => {
            if (h.available > 0) availSlots++;
            if (h.busy > 0) busySlots++;
        });

        (entry?.notes || []).forEach(n => noteSlots.add(n.hour));

        const renderDot = (type, count, label) => {
             if (count === 0) return;
             const dot = document.createElement('span');
             dot.className = `marker-pill marker-${type}`;
             dot.title = `${count} ${label}`; // Tooltip for the marker itself
             dot.innerHTML = `<span class="dot"></span> ${count}`;
             markersContainer.appendChild(dot);
        };

        renderDot('available', availSlots, this.labels.available);
        renderDot('busy', busySlots, this.labels.busy);
        renderDot('note', noteSlots.size, this.labels.note);
    }
  }

  ensureEntry(dateKey) {
    if (!this.cache[dateKey]) {
      this.cache[dateKey] = {
          my_data: { hours: [], status: null },
          stats: { available: 0, busy: 0 },
          hour_stats: {},
          notes: []
      };
    }
    // Deep ensure structure
    if (!this.cache[dateKey].my_data) this.cache[dateKey].my_data = { hours: [], status: null };
    if (!this.cache[dateKey].notes) this.cache[dateKey].notes = [];
    if (!this.cache[dateKey].hour_stats) this.cache[dateKey].hour_stats = {};

    return this.cache[dateKey];
  }

  closeDayNoteEditor() {
    const existing = document.getElementById('day-note-editor-container');
    if (existing) existing.remove();
  }

  deselectDay() {
      if (this.selectedCell) this.selectedCell.classList.remove('is-selected');
      this.selectedDate = null;
      this.selectedCell = null;
      this.toggleHourPanel(false);
      if (this.dom.selectedDateText) this.dom.selectedDateText.textContent = '--';
      this.closeDayNoteEditor();

      // Clear list
      const list = document.getElementById('full-day-user-list');
      if (list) list.innerHTML = '';
  }

  selectDay(dateKey, cell) {
    if (!dateKey) return;

    // Toggle behavior
    if (this.selectedDate === dateKey) {
        this.deselectDay();
        return;
    }

    this.selectedDate = dateKey;
    if (this.selectedCell) this.selectedCell.classList.remove('is-selected');
    this.selectedCell = cell;
    if (this.selectedCell) this.selectedCell.classList.add('is-selected');

    this.closeDayNoteEditor();
    const entry = this.ensureEntry(dateKey);

    this.toggleHourPanel(true);
    if (this.dom.selectedDateText) this.dom.selectedDateText.textContent = this.formatDisplay(dateKey);

    if (this.dom.dayActions) {
      this.dom.dayActions.style.display = 'flex';
      this.setupDayActions();
    }

    this.renderFullDayUserList(entry);

    this.renderHours(entry);
    this.repaintSingleDay(dateKey);
  }

  renderFullDayUserList(entry) {
      let list = document.getElementById('full-day-user-list');
      if (!list) {
          list = document.createElement('div');
          list.id = 'full-day-user-list';
          list.style.marginTop = '0.5rem';
          list.style.marginBottom = '0.5rem';
          // Insert before help text
          if (this.dom.hourHelp && this.dom.hourHelp.parentNode) {
              this.dom.hourHelp.parentNode.insertBefore(list, this.dom.hourHelp);
          }
      }

      list.innerHTML = '';
      const users = entry.full_day_users || [];
      if (users.length === 0) return;

      users.forEach(u => {
          const row = document.createElement('div');
          row.style.fontSize = '0.9rem';
          row.style.marginBottom = '2px';
          const statusLabel = u.status === 'busy' ? this.labels.busy : this.labels.available;
          row.innerHTML = `<strong>${u.username}</strong> – ${statusLabel}`;
          list.appendChild(row);
      });
  }

  setupDayActions() {
    const entry = this.ensureEntry(this.selectedDate);
    const btns = this.dom.dayActions.querySelectorAll('button[data-action]');

    // Highlight current status
    btns.forEach(btn => {
        btn.classList.remove('active'); // Optional styling
        const action = btn.dataset.action;
        const myStatus = entry.my_data.status;

        if (action === 'set-available' && myStatus === 'available') btn.classList.add('button-outline');
        // (Assuming button-outline or similar indicates active state, or just leave it)

        btn.onclick = async (e) => {
          e.stopPropagation();
          if (!this.selectedDate) return;

          if (action === 'set-note') {
            this.toggleDayNoteEditor(entry);
            return;
          }

          let newStatus = null;
          if (action === 'set-available') newStatus = 'available';
          if (action === 'set-busy') newStatus = 'busy';
          const isClearing = action === 'set-clear';

          entry.my_data.status = isClearing ? null : newStatus;
          if (isClearing) {
              // Also clear hours?
              // Logic: If I clear day status, I usually clear my hours too?
              // The original logic did that.
              entry.my_data.hours = [];
              this.closeDayNoteEditor();
          }

          await this.persist(this.selectedDate);
          // Persist triggers a fetch/refresh, so view updates automatically
        };
    });
  }

  toggleDayNoteEditor(entry) {
    if (!this.selectedDate) return;
    const currentEntry = entry;
    const myData = currentEntry.my_data;

    let editor = document.getElementById('day-note-editor-container');
    if (editor) {
      editor.remove();
      return;
    }

    const allDayHour = myData.hours.find(h => h.hour === -1) || { hour: -1, note: '' };
    // Temporarily add to hours if not present, for editing
    if (!myData.hours.includes(allDayHour)) myData.hours.push(allDayHour);

    editor = document.createElement('div');
    editor.id = 'day-note-editor-container';
    editor.style.marginTop = '0.5rem';
    editor.style.width = '100%';

    const header = document.createElement('div');
    header.className = 'day-note-heading';
    header.textContent = `${this.formatDisplay(this.selectedDate)} · ${this.labels.note || 'Note'}`;

    const textarea = document.createElement('textarea');
    textarea.rows = 2;
    textarea.placeholder = this.texts.notePlaceholder || 'Note...';
    textarea.value = allDayHour.note || '';
    textarea.style.width = '100%';
    textarea.style.marginBottom = '0.5rem';

    const btnGroup = document.createElement('div');
    btnGroup.style.display = 'flex';
    btnGroup.style.gap = '0.5rem';

    const saveBtn = document.createElement('button');
    saveBtn.className = 'button button-primary button-sm';
    saveBtn.textContent = this.labels.save || 'Save';

    const cancelBtn = document.createElement('button');
    cancelBtn.className = 'button button-secondary button-sm';
    cancelBtn.textContent = this.labels.cancel || 'Cancel';

    saveBtn.onclick = async () => {
        allDayHour.note = textarea.value;
        await this.persist(this.selectedDate);
        editor.remove();
    };

    cancelBtn.onclick = () => {
        editor.remove();
    };

    btnGroup.append(saveBtn, cancelBtn);
    editor.append(header, textarea, btnGroup);
    this.dom.dayActions.parentNode.insertBefore(editor, this.dom.dayActions.nextSibling);
    textarea.focus();
  }

  renderHours(entry) {
    this.dom.hourly.innerHTML = '';
    if (this.dom.hourHelp) this.dom.hourHelp.textContent = this.texts.hourHelp || 'Click an hour to toggle availability and write a note.';

    // 1. All Day Notes Section
    const allDayNotes = entry.notes.filter(n => n.hour === -1);
    if (allDayNotes.length > 0) {
        const adContainer = document.createElement('div');
        adContainer.className = 'all-day-notes-container';
        adContainer.style.marginBottom = '1rem';
        adContainer.style.padding = '0.5rem';
        adContainer.style.backgroundColor = '#f8f9fa';
        adContainer.style.borderRadius = '4px';

        const adHeader = document.createElement('small');
        adHeader.style.fontWeight = 'bold';
        adHeader.textContent = this.texts.allDay || 'All Day';
        adContainer.appendChild(adHeader);

        allDayNotes.forEach(note => {
            const p = document.createElement('div');
            p.style.fontSize = '0.9rem';
            p.style.marginTop = '0.25rem';

            const author = document.createElement('span');
            author.style.fontWeight = '600';
            author.style.marginRight = '0.5rem';
            author.textContent = note.username + ':';

            const text = document.createElement('span');
            text.textContent = note.note;

            p.append(author, text);
            adContainer.appendChild(p);
        });
        this.dom.hourly.appendChild(adContainer);
    }

    const ensureMyHour = (hour) => {
      if (!entry.my_data.hours) entry.my_data.hours = [];
      let item = entry.my_data.hours.find((h) => h.hour === hour);
      if (!item) {
        item = { hour };
        entry.my_data.hours.push(item);
      }
      return item;
    };

    const toggleNoteEditor = (row, hourState) => {
       // Similar to before, but operates on 'hourState' which is the USER'S data
       const existingEditor = row.querySelector('.hour-note-editor');
       if (existingEditor) { existingEditor.remove(); return; }
       this.dom.hourly.querySelectorAll('.hour-note-editor').forEach(el => el.remove());

      const editor = document.createElement('div');
      editor.className = 'hour-note-editor';
      editor.style.marginTop = '0.5rem';

      const textarea = document.createElement('textarea');
      textarea.rows = 2;
      textarea.placeholder = this.texts.notePlaceholder || 'Note...';
      textarea.value = hourState.note || '';
      textarea.style.width = '100%';
      textarea.style.marginBottom = '0.5rem';

      const btnGroup = document.createElement('div');
      btnGroup.style.display = 'flex';
      btnGroup.style.gap = '0.5rem';

      const saveBtn = document.createElement('button');
      saveBtn.className = 'button button-primary button-sm';
      saveBtn.textContent = this.labels.save || 'Save';

      saveBtn.onclick = async (e) => {
        e.stopPropagation();
        hourState.note = textarea.value;
        await this.persist(this.selectedDate);
        editor.remove();
        // View refreshes automatically
      };

      const cancelBtn = document.createElement('button');
      cancelBtn.className = 'button button-secondary button-sm';
      cancelBtn.textContent = this.labels.cancel || 'Cancel';
      cancelBtn.onclick = (e) => { e.stopPropagation(); editor.remove(); };

      btnGroup.append(saveBtn, cancelBtn);
      editor.append(textarea, btnGroup);
      row.appendChild(editor);
      textarea.focus();
    };

    for (let h = 0; h < 24; h++) {
      const myState = ensureMyHour(h);
      const hourStats = entry.hour_stats[h] || { available: 0, busy: 0 };
      const hourNotes = entry.notes.filter(n => n.hour === h);

      const row = document.createElement('div');
      row.className = 'hour-row';
      row.style.flexDirection = 'column';
      row.style.alignItems = 'stretch';
      row.style.position = 'relative';

      // Visual Aggregate Background for the Row?
      // Or a bar on the side?
      // Let's use a small bar on the left border or background.
      const total = hourStats.available + hourStats.busy;
      if (total > 0) {
          const pct = (hourStats.available / total) * 100;
          // Gradient background for the row, very subtle
           row.style.background = `linear-gradient(90deg, rgba(40, 167, 69, 0.1) 0%, rgba(40, 167, 69, 0.1) ${pct}%, rgba(220, 53, 69, 0.1) ${pct}%, rgba(220, 53, 69, 0.1) 100%)`;
      }

      const mainLine = document.createElement('div');
      mainLine.style.display = 'flex';
      mainLine.style.justifyContent = 'space-between';
      mainLine.style.alignItems = 'center';
      mainLine.style.width = '100%';
      mainLine.style.padding = '0.5rem'; // Padding for clickability

      // Left: Time + My Status
      const left = document.createElement('div');
      left.style.display = 'flex';
      left.style.alignItems = 'center';
      left.style.gap = '0.5rem';

      const time = document.createElement('span');
      time.className = 'hour-time';
      time.textContent = `${this.pad(h)}:00`;

      const pill = document.createElement('span');
      pill.className = 'hour-pill';
      if (myState.status === 'available') {
        pill.classList.add('available');
        pill.textContent = this.labels.available;
      } else if (myState.status === 'busy') {
        pill.classList.add('busy');
        pill.textContent = this.labels.busy;
      }
      left.append(time, pill);

      // Right: Actions
      const actions = document.createElement('div');
      actions.className = 'hour-actions';

      const toggleBtn = document.createElement('button');
      toggleBtn.type = 'button';
      toggleBtn.className = 'hour-toggle';
      toggleBtn.textContent = myState.status
          ? (myState.status === 'available' ? this.labels.available : this.labels.busy)
          : (this.texts.status || 'Status');

      toggleBtn.onclick = async (e) => {
          e.stopPropagation();
          myState.status = this.cycleStatus(myState.status || null);
          // Visual update immediate?
          // Wait for persist
          await this.persist(this.selectedDate);
      };

      const noteBtn = document.createElement('button');
      noteBtn.type = 'button';
      noteBtn.className = 'hour-note';
      noteBtn.textContent = this.texts.btnNote || 'Note';
      if (myState.note) noteBtn.classList.add('has-note');

      noteBtn.onclick = (e) => {
          e.stopPropagation();
          toggleNoteEditor(row, myState);
      };

      actions.append(toggleBtn, noteBtn);
      mainLine.append(left, actions);
      row.appendChild(mainLine);

      // Render Notes List (Including mine and others)
      if (hourNotes.length > 0) {
          const notesContainer = document.createElement('div');
          notesContainer.className = 'hour-notes-list';
          notesContainer.style.padding = '0 0.5rem 0.5rem 3rem'; // Indent

          hourNotes.forEach(n => {
             const div = document.createElement('div');
             div.style.fontSize = '0.85rem';
             div.style.color = '#555';

             const author = document.createElement('strong');
             author.textContent = n.username + ': ';

             const txt = document.createElement('span');
             txt.textContent = n.note;

             div.append(author, txt);
             notesContainer.appendChild(div);
          });
          row.appendChild(notesContainer);
      }

      this.dom.hourly.appendChild(row);
    }
  }

  repaintSingleDay(dateKey) {
    this.paintDay(dateKey, null);
  }

  updateSidebar() {
    let available = 0;
    let busy = 0;
    let notes = 0;

    // Aggregate stats from the whole month?
    // "How many users marked that slot as available" -> Sidebar usually shows "My Stats" or "Global Stats"?
    // The original code calculated based on entries.
    // Let's show Global Stats (Total Man-Days available).

    Object.values(this.cache).forEach((entry) => {
      // entry.stats has aggregate counts
      available += (entry.stats?.available || 0);
      busy += (entry.stats?.busy || 0);
      notes += (entry.notes?.length || 0);
    });

    if (this.dom.availableCount) this.dom.availableCount.textContent = available;
    if (this.dom.busyCount) this.dom.busyCount.textContent = busy;
    if (this.dom.notesCount) this.dom.notesCount.textContent = notes;
  }

  populateUpcoming() {
    if (!this.dom.upcoming) return;
    const todayKey = this.getTodayKey();

    // We want to show "Upcoming Availability"
    // Since it's a shared calendar, showing everyone's availability might be noisy.
    // The requirements didn't specify changing this widget.
    // But logically, I should probably show "My Upcoming" or "Global Highlights"?
    // Let's show "My Upcoming" to keep it personal for the dashboard feel.
    // OR show "Shared Events" where someone is Busy/Available?
    // Let's stick to "My Data" for the upcoming list to avoid clutter, as users usually want to see their own schedule.

    const items = [];
    Object.entries(this.cache).forEach(([day, entry]) => {
      if (day < todayKey) return;
      const myData = entry.my_data || { hours: [], status: null };

      const hours = myData.hours || [];
      if (hours.length > 0) {
        hours.forEach((h) => {
          if (h.status || h.note) items.push({ day, hour: h.hour, status: h.status || myData.status, note: h.note });
        });
      } else if (myData.status) {
        items.push({ day, hour: -1, status: myData.status, note: '' });
      }
    });

    items.sort((a, b) => {
      if (a.day !== b.day) return a.day.localeCompare(b.day);
      return a.hour - b.hour;
    });

    this.dom.upcoming.innerHTML = '';
    const limited = items.slice(0, 8);
    if (limited.length === 0) {
      this.dom.upcoming.innerHTML = `<p class="form-helper">${this.texts.noUpcoming || 'No saved hours yet.'}</p>`;
      return;
    }

    limited.forEach((item) => {
      const wrapper = document.createElement('div');
      wrapper.className = 'calendar-upcoming-item';
      const top = document.createElement('div');
      top.className = 'calendar-day-label';
      const left = document.createElement('span');

      const timeLabel = item.hour === -1 ? (this.texts.allDay || 'All Day') : `${this.pad(item.hour)}:00`;
      left.textContent = `${item.day} · ${timeLabel}`;

      const badge = document.createElement('span');
      const busy = item.status === 'busy';
      badge.className = `badge ${busy ? 'badge-danger' : 'badge-success'}`;
      badge.textContent = busy ? this.labels.busy : this.labels.available;
      top.append(left, badge);
      wrapper.appendChild(top);
      if (item.note) {
        const note = document.createElement('p');
        note.className = 'form-helper';
        note.textContent = item.note;
        wrapper.appendChild(note);
      }
      this.dom.upcoming.appendChild(wrapper);
    });
  }

  populateNotes() {
    if (!this.dom.notes) return;
    // Show RECENT NOTES from EVERYONE
    const notes = [];
    Object.entries(this.cache).forEach(([day, entry]) => {
      (entry.notes || []).forEach((n) => {
          notes.push({ day, hour: n.hour, note: n.note, username: n.username });
      });
    });

    notes.sort((a, b) => (a.day === b.day ? b.hour - a.hour : b.day.localeCompare(a.day))); // Newest first
    this.dom.notes.innerHTML = '';
    const limited = notes.slice(0, 6);
    if (limited.length === 0) {
      this.dom.notes.innerHTML = `<p class="form-helper">${this.texts.noNotes || ''}</p>`;
      return;
    }

    limited.forEach((item) => {
      const wrapper = document.createElement('div');
      wrapper.className = 'calendar-upcoming-item';
      const top = document.createElement('div');
      top.className = 'calendar-day-label';
      const left = document.createElement('span');
      const timeStr = item.hour === -1 ? 'All Day' : `${this.pad(item.hour)}:00`;
      left.textContent = `${item.day} · ${timeStr}`;

      const badge = document.createElement('span');
      badge.className = 'badge badge-default';
      badge.textContent = item.username; // Show Author name in badge
      top.append(left, badge);
      wrapper.appendChild(top);
      const note = document.createElement('p');
      note.className = 'form-helper';
      note.textContent = item.note;
      wrapper.appendChild(note);
      this.dom.notes.appendChild(wrapper);
    });
  }

  async persist(dateKey, overrideStatus) {
    if (!this.config.calendarId || !dateKey) return;
    const entry = this.ensureEntry(dateKey);
    const myData = entry.my_data;

    const resolvedStatus =
      overrideStatus !== undefined
        ? overrideStatus
        : myData.status !== undefined
          ? myData.status
          : null;

    const payload = {
      date: dateKey,
      status: resolvedStatus,
      hours: (myData.hours || [])
        .filter((h) => h.status || h.note)
        .map((h) => ({ hour: h.hour, status: h.status || null, note: h.note || '' })),
    };

    try {
      const response = await fetch(this.buildApiUrl(`/api/calendars/${this.config.calendarId}/availability`), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }
      // Immediate fetch to get updated aggregates
      await this.fetchData(this.currentYear, this.currentMonth, true); // Treat as poll to avoid full reset
    } catch (err) {
      console.error('Değişiklikler kaydedilemedi', err);
      this.showAlert(this.errors.saveFailed || 'Could not save changes, please check your connection and try again.');
    }
  }

  bindToolbar() {
    this.dom.prevMonth?.addEventListener('click', () => {
      const newMonth = this.currentMonth === 1 ? 12 : this.currentMonth - 1;
      const newYear = this.currentMonth === 1 ? this.currentYear - 1 : this.currentYear;
      this.loadMonth(newYear, newMonth);
    });

    this.dom.nextMonth?.addEventListener('click', () => {
      const newMonth = this.currentMonth === 12 ? 1 : this.currentMonth + 1;
      const newYear = this.currentMonth === 12 ? this.currentYear + 1 : this.currentYear;
      this.loadMonth(newYear, newMonth);
    });

    this.dom.today?.addEventListener('click', () => {
      const now = this.toTimezoneDate(this.timezone);
      const year = now.getFullYear();
      const month = now.getMonth() + 1;
      this.loadMonth(year, month).then(() => {
        const key = this.formatDate(now);
        const cell = this.dom.grid.querySelector(`[data-date="${key}"]`);
        this.selectDay(key, cell);
      });
    });
  }
}

(function boot() {
  const instance = new CalendarPage(window.calendarConfig || {});
  instance.init();
})();
