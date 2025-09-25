<?php
require_once __DIR__ . '/../includes/header.php';
require_role(ROLE_SUPERVISOR, ROLE_ADMIN);
$pdo = db();

$report_id = isset($_GET['report_id']) ? (int)$_GET['report_id'] : 0;
if (!$report_id) {
    echo '<div class="alert alert-danger">Falta report_id</div></div></body></html>'; exit;
}

// Cargar informe
$stmt = $pdo->prepare("SELECT id, titulo AS title, periodo AS period, estado AS status FROM informes WHERE id = ?");
$stmt->execute([$report_id]);
$informe = $stmt->fetch();
if (!$informe) { echo '<div class="alert alert-danger">Informe no encontrado</div></div></body></html>'; exit; }

$success = null; $error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add_section') {
            $titulo = trim($_POST['titulo'] ?? '');
            $departamento_id = isset($_POST['departamento_id']) && $_POST['departamento_id'] !== '' ? (int)$_POST['departamento_id'] : null;
            $asignado_a = isset($_POST['asignado_a']) && $_POST['asignado_a'] !== '' ? (int)$_POST['asignado_a'] : null;
            $capitulo = (int)($_POST['capitulo'] ?? 1); if ($capitulo < 1 || $capitulo > 5) $capitulo = 1;
            if (!$titulo || !$departamento_id) throw new Exception('Complete título y departamento');
            // Validar asignado (si viene) que sea editor
            if ($asignado_a) {
                $v = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND rol = 'editor'");
                $v->execute([$asignado_a]);
                if (!$v->fetch()) throw new Exception('Usuario asignado inválido');
            }
            // Calcular orden siguiente para el capítulo específico
            $stmt_ord = $pdo->prepare("SELECT COALESCE(MAX(orden),0)+1 FROM secciones_informe WHERE informe_id = ? AND capitulo = ?");
            $stmt_ord->execute([$report_id, $capitulo]);
            $ord = (int)$stmt_ord->fetchColumn();
            $ins = $pdo->prepare("INSERT INTO secciones_informe (informe_id, departamento_id, titulo, contenido, estado, asignado_a, orden, capitulo, actualizado_en) VALUES (?, ?, ?, '', 'borrador', ?, ?, ?, NOW())");
            $ins->execute([$report_id, $departamento_id, $titulo, $asignado_a, $ord, $capitulo]);
            if ($asignado_a) { notify_user($asignado_a, 'Nueva sección asignada', 'Se te asignó la sección "' . e($titulo) . '".'); }
            $success = 'Sección creada';
        } elseif ($action === 'update_section') {
            $section_id = (int)($_POST['section_id'] ?? 0);
            $titulo = trim($_POST['titulo'] ?? '');
            $departamento_id = isset($_POST['departamento_id']) && $_POST['departamento_id'] !== '' ? (int)$_POST['departamento_id'] : null;
            $asignado_a = isset($_POST['asignado_a']) && $_POST['asignado_a'] !== '' ? (int)$_POST['asignado_a'] : null;
            $capitulo = (int)($_POST['capitulo'] ?? 1); if ($capitulo < 1 || $capitulo > 5) $capitulo = 1;
            if (!$section_id || !$titulo || !$departamento_id) throw new Exception('Datos incompletos');
            // Obtener asignación actual
            $cur = $pdo->prepare("SELECT asignado_a, titulo FROM secciones_informe WHERE id = ? AND informe_id = ?");
            $cur->execute([$section_id, $report_id]);
            $prev = $cur->fetch();
            if (!$prev) throw new Exception('Sección no encontrada');
            // Validar asignado
            if ($asignado_a) {
                $v = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND rol = 'editor'");
                $v->execute([$asignado_a]);
                if (!$v->fetch()) throw new Exception('Usuario asignado inválido');
            }
      $pdo->prepare("UPDATE secciones_informe SET titulo = ?, departamento_id = ?, asignado_a = ?, capitulo = ?, actualizado_en = NOW() WHERE id = ? AND informe_id = ?")
        ->execute([$titulo, $departamento_id, $asignado_a, $capitulo, $section_id, $report_id]);
            if ($asignado_a && (int)($prev['asignado_a'] ?? 0) !== $asignado_a) {
                notify_user($asignado_a, 'Sección asignada', 'Se te asignó la sección "' . e($titulo) . '".');
            }
            $success = 'Sección actualizada';
        } elseif ($action === 'delete_section') {
            $section_id = (int)($_POST['section_id'] ?? 0);
            if (!$section_id) throw new Exception('Sección inválida');
            $pdo->prepare("DELETE FROM secciones_informe WHERE id = ? AND informe_id = ?")
                ->execute([$section_id, $report_id]);
            $success = 'Sección eliminada';
        }
    } catch (Exception $ex) {
        $error = $ex->getMessage();
    }
}

$depts = $pdo->query("SELECT id, nombre AS name FROM departamentos ORDER BY nombre")->fetchAll();
$editores = $pdo->query("SELECT id, nombre, departamento_id FROM usuarios WHERE rol = 'editor' ORDER BY nombre")->fetchAll();
$secciones_raw = $pdo->prepare("SELECT rs.id, rs.titulo, rs.estado, rs.departamento_id, rs.asignado_a, rs.capitulo, d.nombre AS dept_name
  FROM secciones_informe rs LEFT JOIN departamentos d ON d.id = rs.departamento_id
  WHERE rs.informe_id = ? ORDER BY rs.capitulo ASC, rs.orden ASC");
$secciones_raw->execute([$report_id]);
$secciones_por_capitulo = [];
foreach ($secciones_raw->fetchAll() as $s) {
    $secciones_por_capitulo[$s['capitulo']][] = $s;
}
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h4>Gestionar secciones: <?= e($informe['title']) ?> (<?= e($informe['period']) ?>)</h4>
  <a class="btn btn-outline-secondary" href="<?= e(app_url('admin/informes.php')) ?>">Volver</a>
</div>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<div class="row g-3">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header">Agregar nueva sección</div>
      <div class="card-body">
        <form method="post">
          <input type="hidden" name="<?= e(CSRF_TOKEN_KEY) ?>" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="add_section">
          <div class="mb-3">
            <label class="form-label">Departamento</label>
            <select class="form-select" name="departamento_id" id="sel-depto" required>
              <option value="">-- Seleccione --</option>
              <?php foreach ($depts as $d): ?>
                <option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Título de la sección</label>
            <input class="form-control" name="titulo" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Capítulo</label>
            <select class="form-select" name="capitulo" required>
              <?php for ($c=1;$c<=5;$c++): ?>
                <option value="<?= $c ?>">Capítulo <?= $c ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Asignar a (opcional)</label>
            <select class="form-select" name="asignado_a" id="sel-editor">
              <option value="">-- Sin asignar --</option>
              <?php foreach ($editores as $e): ?>
                <option data-depto="<?= (int)$e['departamento_id'] ?>" value="<?= (int)$e['id'] ?>"><?= e($e['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">Se filtra por departamento seleccionado.</div>
          </div>
          <button class="btn btn-primary">Agregar sección</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">Secciones del informe</div>
      <div class="card-body table-responsive">
        <?php if (empty($secciones_por_capitulo)): ?>
          <div class="alert alert-info">Aún no hay secciones en este informe.</div>
        <?php else: ?>
          <?php foreach ($secciones_por_capitulo as $capitulo => $secciones): ?>
            <h5 class="mt-4">Capítulo <?= e($capitulo) ?></h5>
            <table class="table table-sm align-middle table-hover">
              <thead><tr><th style="width: 2rem;"></th><th>Departamento</th><th>Título</th><th>Estado</th><th>Asignado</th><th></th></tr></thead>
              <tbody class="sortable-list" data-capitulo="<?= e($capitulo) ?>">
              <?php foreach ($secciones as $s): ?>
                <tr data-id="<?= (int)$s['id'] ?>">
                  <td class="handle" style="cursor: move;">&#x2630;</td>
                  <td>
                    <form method="post" class="d-flex gap-2 align-items-center">
                      <input type="hidden" name="<?= e(CSRF_TOKEN_KEY) ?>" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="update_section">
                      <input type="hidden" name="section_id" value="<?= (int)$s['id'] ?>">
                      <input type="hidden" name="capitulo" value="<?= (int)$s['capitulo'] ?>">
                      <select class="form-select form-select-sm" name="departamento_id" style="min-width: 160px">
                        <?php foreach ($depts as $d): ?>
                          <option value="<?= (int)$d['id'] ?>" <?= ((int)$s['departamento_id'] === (int)$d['id']) ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                  </td>
                  <td><input class="form-control form-control-sm" name="titulo" value="<?= e($s['titulo']) ?>"></td>
                  <td><span class="badge bg-secondary"><?= e($s['estado']) ?></span></td>
                  <td>
                      <select class="form-select form-select-sm" name="asignado_a" style="min-width: 180px">
                        <option value="">-- Sin asignar --</option>
                        <?php foreach ($editores as $e): ?>
                          <?php if ((int)$e['departamento_id'] === (int)$s['departamento_id']): ?>
                            <option value="<?= (int)$e['id'] ?>" <?= ((int)($s['asignado_a'] ?? 0) === (int)$e['id']) ? 'selected' : '' ?>><?= e($e['nombre']) ?></option>
                          <?php endif; ?>
                        <?php endforeach; ?>
                      </select>
                      <button class="btn btn-sm btn-primary ms-2">Guardar</button>
                    </form>
                  </td>
                  <td>
                    <form method="post" onsubmit="return confirm('¿Eliminar sección?')">
                      <input type="hidden" name="<?= e(CSRF_TOKEN_KEY) ?>" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="delete_section">
                      <input type="hidden" name="section_id" value="<?= (int)$s['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
            <button class="btn btn-primary btn-sm save-order-btn mt-2" data-capitulo="<?= e($capitulo) ?>" style="display:none;">Guardar Orden del Capítulo <?= e($capitulo) ?></button>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
(function(){
    const reportId = <?= $report_id ?>;
    document.querySelectorAll('.sortable-list').forEach(list => {
        const capitulo = list.dataset.capitulo;
        const saveOrderBtn = document.querySelector(`.save-order-btn[data-capitulo="${capitulo}"]`);

        new Sortable(list, {
            handle: '.handle',
            animation: 150,
            onUpdate: function () {
                saveOrderBtn.style.display = 'inline-block';
            }
        });
    });

    document.querySelectorAll('.save-order-btn').forEach(button => {
        button.addEventListener('click', async function () {
            const capitulo = this.dataset.capitulo;
            const sortableList = document.querySelector(`.sortable-list[data-capitulo="${capitulo}"]`);
            const sectionIds = Array.from(sortableList.querySelectorAll('tr')).map(row => row.dataset.id);

            try {
                const response = await fetch('../api/informes.php?action=reorder_sections', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?= e(csrf_token()) ?>'
                    },
                    body: JSON.stringify({ 
                        report_id: reportId,
                        section_ids: sectionIds 
                    })
                });

                const result = await response.json();

                if (result.ok) {
                    alert(result.message || '¡Orden guardado con éxito!');
                    this.style.display = 'none';
                } else {
                    alert('Error: ' + (result.error || 'No se pudo guardar el orden.'));
                }
            } catch (error) {
                console.error('Error en la llamada a la API:', error);
                alert('Ocurrió un error de red.');
            }
        });
    });
})();
</script>
<script>
(function(){
  const selDepto = document.getElementById('sel-depto');
  const selEditor = document.getElementById('sel-editor');
  function filterEditors(){
    const depto = selDepto.value || '';
    Array.from(selEditor.options).forEach(opt => {
      if (!opt.value) return; // skip placeholder
      const d = opt.getAttribute('data-depto') || '';
      opt.hidden = (depto && d !== depto);
    });
    selEditor.value = '';
  }
  if (selDepto && selEditor) {
    selDepto.addEventListener('change', filterEditors);
  }
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        onerror="document.write('<script src=<?= json_encode(app_url('assets/js/bootstrap.bundle.min.js')) ?>><\\/script>')"></script>
</body></html>
