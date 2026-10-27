<?php
/**
 * reports.php – Screen 6: Department attendance report
 * Groups attendance by department and applies the attendance_summary() algorithm.
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$pdo  = db();
$from = valid_date($_GET['from'] ?? null, '1970-01-01');
$to   = valid_date($_GET['to'] ?? null, '9999-12-31');

// One row per department with counts of each status in the date range.
// LEFT JOINs keep departments that have no data yet.
$stmt = $pdo->prepare("
    SELECT d.department_id,
           d.department_name,
           COUNT(e.employee_id)                    AS headcount,
           SUM(a.status = 'Present')              AS present,
           SUM(a.status = 'Late')                 AS late,
           SUM(a.status = 'Absent')               AS absent
    FROM departments d
    LEFT JOIN employees  e ON e.department_id = d.department_id
    LEFT JOIN attendance a ON a.employee_id = e.employee_id
                          AND a.attendance_date BETWEEN ? AND ?
    GROUP BY d.department_id, d.department_name
    ORDER BY d.department_name
");
$stmt->execute([$from, $to]);

$rows = [];
$totals = ['present' => 0, 'late' => 0, 'absent' => 0];

foreach ($stmt->fetchAll() as $r) {
    $p = (int)$r['present'];
    $l = (int)$r['late'];
    $a = (int)$r['absent'];
    $r['summary'] = attendance_summary($p, $l, $a);
    $r['present'] = $p;
    $r['late']    = $l;
    $r['absent']  = $a;
    $rows[] = $r;

    $totals['present'] += $p;
    $totals['late']    += $l;
    $totals['absent']  += $a;
}

$overall = attendance_summary($totals['present'], $totals['late'], $totals['absent']);

render_header('Department Reports', 'reports');
?>
<div class="card">
    <form method="get" class="form-row">
        <div>
            <label for="from">From</label>
            <input type="date" id="from" name="from" value="<?= $from === '1970-01-01' ? '' : e($from) ?>">
        </div>
        <div>
            <label for="to">To</label>
            <input type="date" id="to" name="to" value="<?= $to === '9999-12-31' ? '' : e($to) ?>">
        </div>
        <div style="flex:0"><button class="btn" type="submit">Generate report</button></div>
    </form>
</div>

<div class="grid grid-4" style="margin-bottom:22px">
    <div class="stat stat-purple"><div class="label">Overall attendance</div><div class="value"><?= $overall['attendance_rate'] ?>%</div></div>
    <div class="stat stat-cyan"><div class="label">Overall punctuality</div><div class="value"><?= $overall['punctuality'] ?>%</div></div>
    <div class="stat stat-red"><div class="label">Overall absenteeism</div><div class="value"><?= $overall['absenteeism'] ?>%</div></div>
    <div class="stat stat-green"><div class="label">Records analysed</div><div class="value"><?= $overall['total'] ?></div></div>
</div>

<div class="card">
    <h2>Department breakdown</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Department</th><th>Staff</th><th>Present</th><th>Late</th><th>Absent</th>
                    <th>Attendance</th><th>Punctuality</th><th>Flags</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): $s = $r['summary']; ?>
                <tr>
                    <td><strong><?= e($r['department_name']) ?></strong></td>
                    <td><?= (int)$r['headcount'] ?></td>
                    <td><span class="badge st-present"><?= $r['present'] ?></span></td>
                    <td><span class="badge st-late"><?= $r['late'] ?></span></td>
                    <td><span class="badge st-absent"><?= $r['absent'] ?></span></td>
                    <td>
                        <?php if ($s['total'] === 0): ?>
                            <span style="color:var(--muted)">No data</span>
                        <?php else: ?>
                            <div class="bar"><span style="width:<?= $s['attendance_rate'] ?>%"></span></div>
                            <small><?= $s['attendance_rate'] ?>%</small>
                        <?php endif; ?>
                    </td>
                    <td><?= $s['total'] ? $s['punctuality'] . '%' : '—' ?></td>
                    <td>
                        <?php foreach ($s['flags'] as $flag):
                            $cls = $s['level'] === 'good' ? 'flag-good' : ($s['level'] === 'bad' ? 'flag-bad' : 'flag-warn');
                            if ($flag === 'No data') $cls = 'st-none'; ?>
                            <span class="badge <?= $cls ?>"><?= e($flag) ?></span>
                        <?php endforeach; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p style="color:var(--muted);font-size:0.85rem;margin-top:14px">
        Rules: <em>High absenteeism</em> when absence ≥ <?= ABSENTEEISM_THRESHOLD ?>%;
        <em>Punctuality issue</em> when on-time rate &lt; <?= PUNCTUALITY_THRESHOLD ?>%.
    </p>
</div>
<?php render_footer(); ?>
