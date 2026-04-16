<?php
$pageStyles = ['/css/CalendarPage.css', '/css/shared_calendar.css', '/css/overview_calendar.css'];
include __DIR__ . '/partials/header.php';
?>

<section class="hero">
  <h1><?= htmlspecialchars(t('welcome')) ?> 👋</h1>
  <p><?= htmlspecialchars(t('heroDescription')) ?></p>

  <form action="<?= $basePath ?>/calendars" method="post" class="inline-form">
    <input type="text" name="name" placeholder="<?= htmlspecialchars(t('calendarNamePlaceholder')) ?>" required>
    <button type="submit"><?= htmlspecialchars(t('create')) ?></button>
  </form>
  <div class="hero-links">
    <a class="button button-secondary" href="<?= $basePath ?>/join"><?= htmlspecialchars(t('joinCalendar')) ?></a>
  </div>
</section>

<section class="panel">
  <h2><?= htmlspecialchars(t('overviewCalendarTitle')) ?></h2>
  <div class="overview-calendar-card calendar-card">
    <div class="calendar-card-header">
      <div class="calendar-header-left">
        <p class="calendar-subtitle"><?= htmlspecialchars(t('overviewCalendarTitle')) ?></p>
        <h3 class="calendar-title"><?= htmlspecialchars(t('overviewCalendarTitle')) ?></h3>
      </div>
      <div class="calendar-toolbar">
        <button type="button" class="ghost" data-overview-prev aria-label="<?= htmlspecialchars(t('previousMonth')) ?>">&#8249;</button>
        <div class="month-label" data-overview-month></div>
        <button type="button" class="ghost" data-overview-next aria-label="<?= htmlspecialchars(t('nextMonth')) ?>">&#8250;</button>
        <button type="button" class="button button-secondary" data-overview-today><?= htmlspecialchars(t('today')) ?></button>
      </div>
    </div>

    <div class="overview-legend-row">
      <div class="overview-legend">
        <span class="calendar-legend-pill"><span class="calendar-legend-dot legend-available"></span><?= htmlspecialchars(t('available')) ?></span>
        <span class="calendar-legend-pill"><span class="calendar-legend-dot legend-busy"></span><?= htmlspecialchars(t('busy')) ?></span>
        <span class="calendar-legend-pill"><span class="calendar-legend-dot legend-note"></span><?= htmlspecialchars(t('legendBlue')) ?></span>
      </div>
      <button type="button" class="button button-secondary button-sm overview-filters-toggle" data-overview-toggle-filters>
        <?= htmlspecialchars(t('overviewFiltersButton')) ?>
      </button>
    </div>

    <div class="overview-filters" data-overview-filters hidden>
      <h3><?= htmlspecialchars(t('overviewFiltersTitle')) ?></h3>
      <div class="overview-filter-actions">
        <button type="button" class="button button-secondary button-sm" data-overview-select-all><?= htmlspecialchars(t('overviewSelectAll')) ?></button>
        <button type="button" class="button button-secondary button-sm" data-overview-clear><?= htmlspecialchars(t('overviewClearAll')) ?></button>
      </div>
      <p class="form-helper" style="margin-bottom: 0.5rem;"><?= htmlspecialchars(t('overviewCalendarsLabel')) ?></p>
      <ul class="overview-filter-list">
        <?php foreach ($calendars as $c): ?>
          <li>
            <label>
              <input type="checkbox" data-overview-calendar value="<?= (int)$c['id'] ?>" checked>
              <span><?= htmlspecialchars($c['name']) ?></span>
            </label>
          </li>
        <?php endforeach; ?>
      </ul>
      <div style="margin-top: 0.75rem; display: flex; flex-direction: column; gap: 0.35rem;">
        <label><input type="checkbox" data-overview-availability-only> <?= htmlspecialchars(t('overviewFilterAvailability')) ?></label>
        <label><input type="checkbox" data-overview-notes-only> <?= htmlspecialchars(t('overviewFilterNotes')) ?></label>
      </div>
    </div>

    <div class="calendar-grid">
      <div class="calendar-weekdays" aria-hidden="true">
        <?php
          $weekdays = t('weekdaysShort');
          if (!is_array($weekdays)) {
              $weekdays = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
          } else {
              $weekdays = array_values($weekdays);
              if (count($weekdays) === 7) {
                  $first = strtolower((string) $weekdays[0]);
                  $sundayLabels = ['sun', 'sunday', 'paz', 'pazar', 'dom', 'domingo', 'dim', 'dimanche'];
                  if (in_array($first, $sundayLabels, true)) {
                      $weekdays = array_merge(array_slice($weekdays, 1), [$weekdays[0]]);
                  }
              }
          }
        ?>
        <?php foreach ($weekdays as $day): ?>
          <div class="calendar-weekday"><?= $day ?></div>
        <?php endforeach; ?>
      </div>
      <div class="calendar-grid-body" id="overview-grid"></div>
    </div>

    <div class="overview-details" data-overview-detail hidden>
      <div class="calendar-card-header" style="padding-left: 0; padding-right: 0;">
        <div class="calendar-header-left">
          <p class="calendar-subtitle"><?= htmlspecialchars(t('overviewDetailSubtitle')) ?></p>
          <h3 class="calendar-title" data-overview-detail-date>--</h3>
        </div>
      </div>
      <div data-overview-detail-body></div>
    </div>
  </div>

  <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid #e2e8f0;">

  <h2><?= htmlspecialchars(t('yourCalendars')) ?></h2>
  <?php if (empty($calendars)): ?>
    <p><?= htmlspecialchars(t('noCalendars')) ?></p>
  <?php else: ?>
    <ul class="calendar-list">
      <?php foreach ($calendars as $c): ?>
        <li>
            <a href="<?= $basePath ?>/calendars/<?= htmlspecialchars($c['id']) ?>" class="calendar-card-link">
                <span class="calendar-card-title"><?= htmlspecialchars($c['name']) ?></span>
                <span class="calendar-card-meta">📅 <?= htmlspecialchars(t('calendarViewTitle')) ?></span>
            </a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<script>
  window.overviewConfig = {
    lang: <?= json_encode($lang) ?>,
    calendars: <?= json_encode(array_map(fn($c) => ['id' => (int)$c['id'], 'name' => $c['name']], $calendars)) ?>,
    labels: {
      available: <?= json_encode(t('available')) ?>,
      busy: <?= json_encode(t('busy')) ?>,
      availableHourly: <?= json_encode(t('overviewHourlyAvailable')) ?>,
      busyHourly: <?= json_encode(t('overviewHourlyBusy')) ?>,
      notesLabel: <?= json_encode(t('overviewNotesLabel')) ?>,
      fullDayHeading: <?= json_encode(t('overviewFullDayHeading')) ?>,
      notesHeading: <?= json_encode(t('overviewNotesHeading')) ?>,
      hourlyHeading: <?= json_encode(t('overviewHourlyHeading')) ?>,
      none: <?= json_encode(t('overviewNone')) ?>
    }
  };
</script>
<script src="<?= $basePath ?>/js/overview_calendar.js"></script>

<?php include __DIR__ . '/partials/footer.php'; ?>
