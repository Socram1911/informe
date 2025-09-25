<?php
require_once __DIR__ . '/../includes/header.php';
require_role(ROLE_ADMIN);
$pdo = db();

$sections = $pdo->query("SELECT rs.id,
       r.titulo AS report_title,
       d.nombre AS dept,
       rs.titulo AS title,
       rs.capitulo,
       rs.estado AS status,
       u.nombre AS asignado
  FROM secciones_informe rs
  INNER JOIN informes r ON r.id = rs.informe_id
  LEFT JOIN departamentos d ON d.id = rs.departamento_id
  LEFT JOIN usuarios u ON u.id = rs.asignado_a
  ORDER BY r.actualizado_en DESC, rs.orden ASC
  LIMIT 100")->fetchAll();
?>
<div class="card">
  <div class="card-header">Secciones (últimas 100)</div>
  <div class="card-body">
    <table class="table table-sm">
      <thead><tr><th>Informe</th><th>Depto</th><th>Título</th><th>Capítulo</th><th>Estado</th><th>Asignado</th></tr></thead>
      <tbody>
      <?php foreach ($sections as $s): ?>
        <tr>
          <td><?= e($s['report_title']) ?></td>
          <td><?= e($s['dept'] ?? '-') ?></td>
          <td><?= e($s['title']) ?></td>
          <td><?= e($s['capitulo']) ?></td>
          <td><span class="badge bg-secondary"><?= e($s['status']) ?></span></td>
          <td><?= e($s['asignado'] ?? '-') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        onerror="document.write('<script src=<?= json_encode(app_url('assets/js/bootstrap.bundle.min.js')) ?>><\\/script>')"></script>
</body></html>