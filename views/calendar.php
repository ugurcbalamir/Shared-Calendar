<?php
$pageStyles = ['/css/ui/unified.css', '/css/CalendarPage.css', '/css/shared_calendar.css'];
include __DIR__ . '/partials/header.php';
?>

<div class="calendar-page">
  <div class="calendar-container">
    <div class="calendar-main">
        <div class="calendar-card">
          <div class="calendar-card-header">
            <div class="calendar-header-left">
              <p class="calendar-subtitle"><?= htmlspecialchars(t('availabilityLegend')) ?></p>
              <h1 class="calendar-title"><?= htmlspecialchars($calendar['name']) ?></h1>
            </div>
            <div class="calendar-toolbar">
            <?php if (in_array($role ?? 'member', ['owner', 'admin'], true)): ?>
              <a class="button button-secondary" href="<?= $basePath ?>/calendars/<?= (int) $calendar['id'] ?>/settings"><?= htmlspecialchars(t('settings')) ?></a>
            <?php endif; ?>
              <button type="button" class="ghost" data-prev-month aria-label="<?= htmlspecialchars(t('previousMonth')) ?>">&#8249;</button>
              <div class="month-label" data-month-label></div>
              <button type="button" class="ghost" data-next-month aria-label="<?= htmlspecialchars(t('nextMonth')) ?>">&#8250;</button>
              <button type="button" class="button button-secondary" data-today><?= htmlspecialchars(t('today')) ?></button>
            </div>
          </div>

          <div class="calendar-legend">
            <span class="calendar-legend-pill"><span class="calendar-legend-dot legend-available"></span><?= htmlspecialchars(t('available')) ?> / <?= htmlspecialchars(t('legendGreen')) ?></span>
            <span class="calendar-legend-pill"><span class="calendar-legend-dot legend-busy"></span><?= htmlspecialchars(t('busy')) ?> / <?= htmlspecialchars(t('legendRed')) ?></span>
            <span class="calendar-legend-pill"><span class="calendar-legend-dot legend-note"></span><?= htmlspecialchars(t('legendBlue')) ?></span>
          </div>

        <div class="calendar-alert" data-calendar-alert hidden></div>

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
                          // Force Monday-first ordering by rotating a Sunday-first array.
                          $weekdays = array_merge(array_slice($weekdays, 1), [$weekdays[0]]);
                      }
                  }
              }
            ?>
            <?php foreach ($weekdays as $day): ?>
              <div class="calendar-weekday"><?= $day ?></div>
            <?php endforeach; ?>
          </div>
          <div class="calendar-grid-body" id="calendar-grid">
            <?php
              $today = new DateTime();
              $fbYear = (int) $today->format('Y');
              $fbMonth = (int) $today->format('n');
              $firstDay = new DateTime("$fbYear-$fbMonth-01");
              $startDay = ((int) $firstDay->format('N')) - 1; // Monday = 0
              $daysInMonth = (int) $firstDay->format('t');
              $daysInPrevMonth = (int) $firstDay->modify('-1 day')->format('t');
              $totalCells = (int) ceil(($startDay + $daysInMonth) / 7) * 7;

              $renderFallbackCell = function($date, $isOverflow = false) {
                  $dateLabel = (int) $date->format('j');
                  $iso = $date->format('Y-m-d');
                  $todayKey = (new DateTime())->format('Y-m-d');
            ?>
              <div class="calendar-day<?= $isOverflow ? ' other-month' : '' ?>" data-date="<?= htmlspecialchars($iso) ?>">
                <div class="calendar-day-label">
                  <span<?= $iso === $todayKey ? ' class="calendar-day-today"' : '' ?>><?= $dateLabel ?></span>
                </div>
                <div class="calendar-day-meta"></div>
                <div class="calendar-day-markers"></div>
              </div>
            <?php };

              for ($i = $startDay - 1; $i >= 0; $i--) {
                  $day = $daysInPrevMonth - $i;
                  $date = new DateTime("$fbYear-$fbMonth-01");
                  $date->modify('-1 month')->setDate((int) $date->format('Y'), (int) $date->format('n'), $day);
                  $renderFallbackCell($date, true);
              }

              for ($day = 1; $day <= $daysInMonth; $day++) {
                  $date = new DateTime("$fbYear-$fbMonth-$day");
                  $renderFallbackCell($date, false);
              }

              $trailing = $totalCells - $startDay - $daysInMonth;
              for ($d = 1; $d <= $trailing; $d++) {
                  $date = new DateTime("$fbYear-$fbMonth-01");
                  $date->modify('+1 month')->setDate((int) $date->format('Y'), (int) $date->format('n'), $d);
                  $renderFallbackCell($date, true);
              }
            ?>
          </div>
        </div>
      </div>

      <div class="calendar-card hourly-card" data-hour-panel>
        <div class="calendar-card-header">
          <div class="calendar-header-left">
            <p class="calendar-subtitle"><?= htmlspecialchars(t('hourlyPlan')) ?></p>
            <h2 class="calendar-title" data-selected-date>--</h2>
            <div id="day-actions" style="display: none; gap: 0.5rem; margin-top: 0.5rem; flex-wrap: wrap;">
                <button type="button" class="button button-success button-sm" data-action="set-available"><?= htmlspecialchars(t('available')) ?></button>
                <button type="button" class="button button-danger button-sm" data-action="set-busy"><?= htmlspecialchars(t('busy')) ?></button>
                <button type="button" class="button button-secondary button-sm" data-action="set-clear"><?= htmlspecialchars(t('clear')) ?></button>
                <button type="button" class="button button-secondary button-sm" data-action="set-note"><?= htmlspecialchars(t('note')) ?></button>
            </div>
            <p class="calendar-subtitle" data-hour-help><?= htmlspecialchars(t('hourlyInstructions')) ?></p>
          </div>
        </div>
        <div class="hourly-placeholder" data-hour-empty>
          <p class="form-helper"><?= htmlspecialchars(t('selectADay')) ?></p>
        </div>
        <div id="hourly" class="hourly-grid" hidden></div>
      </div>
    </div>

    <aside class="calendar-sidebar">
      <div class="calendar-widget">
        <div class="calendar-widget-title">
          <span><?= htmlspecialchars(t('summary')) ?></span>
          <small class="form-helper"><?= htmlspecialchars(t('activeMonth')) ?></small>
        </div>
        <div class="calendar-stats">
          <div class="calendar-stat">
            <p class="calendar-stat-label"><?= htmlspecialchars(t('availableDays')) ?></p>
            <p class="calendar-stat-value" data-count-available>0</p>
          </div>
          <div class="calendar-stat">
            <p class="calendar-stat-label"><?= htmlspecialchars(t('busyDays')) ?></p>
            <p class="calendar-stat-value" data-count-busy>0</p>
          </div>
          <div class="calendar-stat">
            <p class="calendar-stat-label"><?= htmlspecialchars(t('noteHours')) ?></p>
            <p class="calendar-stat-value" data-count-notes>0</p>
          </div>
        </div>
      </div>

      <div class="calendar-widget">
        <div class="calendar-widget-title">
          <span><?= htmlspecialchars(t('upcoming')) ?></span>
          <small class="form-helper"><?= htmlspecialchars(t('plannedHours')) ?></small>
        </div>
        <div class="calendar-upcoming-list" id="upcoming-list">
          <?php if (empty($upcoming)): ?>
            <p class="form-helper"><?= htmlspecialchars(t('noUpcoming')) ?></p>
          <?php else: ?>
            <?php foreach ($upcoming as $item): ?>
              <div class="calendar-upcoming-item">
                <div class="calendar-day-label">
                  <span><?= htmlspecialchars($item['day']) ?> · <?= str_pad((string) $item['hour'], 2, '0', STR_PAD_LEFT) ?>:00</span>
                  <span class="badge <?= $item['status'] === 'busy' ? 'badge-danger' : 'badge-success' ?>">
                    <?= $item['status'] === 'busy' ? htmlspecialchars(t('busy')) : htmlspecialchars(t('available')) ?>
                  </span>
                </div>
                <?php if (!empty($item['note'])): ?>
                  <p class="form-helper"><?= htmlspecialchars($item['note']) ?></p>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="calendar-widget">
        <div class="calendar-widget-title">
          <span><?= htmlspecialchars(t('recentNotes')) ?></span>
          <small class="form-helper"><?= htmlspecialchars(t('recentNoteHelp')) ?></small>
        </div>
        <div class="calendar-upcoming-list" id="note-list">
          <?php if (empty($recentNotes)): ?>
            <p class="form-helper"><?= htmlspecialchars(t('noNotes')) ?></p>
          <?php else: ?>
            <?php foreach ($recentNotes as $note): ?>
              <div class="calendar-upcoming-item">
                <div class="calendar-day-label">
                  <span><?= htmlspecialchars($note['day']) ?> · <?= str_pad((string) $note['hour'], 2, '0', STR_PAD_LEFT) ?>:00</span>
                  <span class="badge badge-secondary"><?= htmlspecialchars(t('note')) ?></span>
                </div>
                <p class="form-helper"><?= htmlspecialchars($note['note']) ?></p>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </aside>
  </div>
</div>

<?php
$jsConfig = [
    'calendarId' => (int) $calendar['id'],
    'lang' => $lang,
    'apiBase' => $basePath,
    'labels' => [
        'available' => t('available'),
        'busy' => t('busy'),
        'clear' => t('clear'),
        'note' => t('note'),
        'save' => t('save'),
        'cancel' => t('cancel'),
        'fullDayAvailability' => t('fullDayAvailability'),
        'hourlyAvailability' => t('hourlyAvailability'),
    ],
    'texts' => [
        'hourHelp' => t('hourlyInstructions'),
        'hourHelpActive' => t('hourlyInstructionsActive') ?? t('hourlyInstructions'),
        'busyHoursLabel' => t('busyHoursLabel') ?? t('busy'),
        'availableHoursLabel' => t('availableHoursLabel') ?? t('available'),
        'noteCountLabel' => t('noteCountLabel') ?? t('note'),
        'noNotes' => t('noNotes'),
        'noUpcoming' => t('noUpcoming'),
        'selectDay' => t('selectADay'),
        'status' => t('status') ?? 'Status',
        'btnNote' => t('btnNote') ?? t('note'),
        'allDay' => t('allDay') ?? 'All Day',
    ],
    'errors' => [
        'loadFailed' => t('errorLoadFailed'),
        'missingConfig' => t('errorMissingConfig'),
        'saveFailed' => t('errorSaveFailed'),
    ]
];
?>
<script>
  window.calendarConfig = <?= json_encode($jsConfig) ?>;
</script>
<script src="<?= $basePath ?>/js/calendar.js"></script>
<?php include __DIR__ . '/partials/footer.php'; ?>
