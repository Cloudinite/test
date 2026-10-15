<?php
/** GET /api/events_list.php?scope=upcoming|ongoing|past|all */
require_once __DIR__ . '/helpers.php';

$scope = $_GET['scope'] ?? 'all';
$pdo = get_pdo();
$rows = $pdo->query("SELECT id, title, description, location, start_dt, end_dt, is_cancelled, image FROM events WHERE is_published = 1 ORDER BY start_dt ASC")->fetchAll();

$now = new DateTime('now');
$out = [];
foreach ($rows as $r) {
    $start = new DateTime($r['start_dt']);
    $end = $r['end_dt'] ? new DateTime($r['end_dt']) : (clone $start)->modify('+2 hours');
    if ($r['is_cancelled']) {
        $status = 'cancelled';
    } elseif ($now < $start) {
        $status = 'upcoming';
    } elseif ($now >= $start && $now <= $end) {
        $status = 'ongoing';
    } else {
        $status = 'past';
    }
    $r['status'] = $status;
    if ($scope === 'all' || $scope === $status || ($scope === 'upcoming' && $status === 'ongoing')) {
        $out[] = $r;
    }
}

json_response(['success' => true, 'items' => $out, 'server_time' => $now->format('c')]);
