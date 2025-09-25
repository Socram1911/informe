<?php
require_once __DIR__ . '/../includes/header.php';
require_login();
$u = current_user();
$pdo = db();

$section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
$section = null;
if ($section_id) {
    $stmt = $pdo->prepare("SELECT rs.id, rs.titulo AS title, rs.contenido AS content, rs.asignado_a AS assigned_to, r.titulo AS report_title FROM secciones_informe rs 
        INNER JOIN informes r ON r.id = rs.informe_id WHERE rs.id = ?");
    $stmt->execute([$section_id]);
    $section = $stmt->fetch();
    if (!$section) {
        echo '<div class="alert alert-danger">Sección no encontrada.</div></div></body></html>'; exit;
    }
    if ($u['role'] === ROLE_EDITOR && (int)$section['assigned_to'] !== (int)$u['id']) {
        echo '<div class="alert alert-danger">No tienes acceso a esta sección.</div></div></body></html>'; exit;
    }
}
?>
      <div class="card-body">
  <div class="col-lg-9">
    <div class="card">
      <div class="card-header">
        Editor de Sección <?= $section ? '- ' . e($section['title']) : '' ?>
      </div>
      <div class="card-body">
        <?php if ($section): ?>
          <form id="form-editor">
            <input type="hidden" name="<?= e(CSRF_TOKEN_KEY) ?>" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="section_id" value="<?= (int)$section['id'] ?>">
            <div class="mb-3">
              <label class="form-label">Contenido</label>
              <textarea class="form-control" id="contenido" name="content" rows="10"><?= e($section['content'] ?? '') ?></textarea>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-secondary" data-action="guardar">Guardar borrador</button>
              <button class="btn btn-success" data-action="completar">Marcar como completado</button>
              <button class="btn btn-warning" data-action="revision">Solicitar revisión</button>
            </div>
          </form>
        <?php else: ?>
          <div class="alert alert-info">Selecciona una sección desde tu panel para editar.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-3">
    <div class="card">
      <div class="card-header">Historial</div>
      <div class="card-body" id="historial"></div>
    </div>
  </div>
</div>
</div>
<script type="module">
import { initEditor } from '../assets/js/editor.js';
import { apiFetch } from '../assets/js/api.js';
import { showToast } from '../assets/js/main.js';
initEditor('#contenido');

const form = document.getElementById('form-editor');
if (form) {
  form.addEventListener('click', async (e) => {
    const btn = e.target.closest('button[data-action]');
    if (!btn) return;
    e.preventDefault();
    const action = btn.dataset.action;
    // Obtener contenido desde CKEditor si existe, o desde el textarea
    const contenidoEl = document.querySelector('#contenido');
    const ck = contenidoEl && contenidoEl.__ckInstance;
    const contentValue = ck ? await ck.getData() : (contenidoEl ? contenidoEl.value : '');
    const payload = {
      section_id: Number(form.section_id.value),
      content: contentValue || '',
      _csrf_token: window.CSRF_TOKEN
    };
    let endpoint = '';
    if (action === 'guardar') endpoint = '../api/contribuciones.php?action=guardar';
    if (action === 'completar') endpoint = '../api/contribuciones.php?action=completar';
    if (action === 'revision') endpoint = '../api/contribuciones.php?action=solicitar_revision';
    try {
      const res = await apiFetch(endpoint, { method: 'POST', body: JSON.stringify(payload) });
      showToast(res.message || 'Acción realizada', 'success');
      loadHistorial();
    } catch (err) {
      showToast(err.message, 'danger');
    }
  });

  async function loadHistorial() {
    try {
      const res = await apiFetch('../api/contribuciones.php?action=historial&section_id=' + Number(form.section_id.value));
      const cont = document.getElementById('historial');
      cont.innerHTML = (res.items || []).map(h => `<div class="small border-start ps-2 mb-2"><b>${h.action}</b> - ${h.created_at}<br>${h.comment ? h.comment : ''}</div>`).join('') || '<em>Sin cambios</em>';
    } catch {}
  }
  loadHistorial();
}

</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        onerror="document.write('<script src=<?= json_encode(app_url('assets/js/bootstrap.bundle.min.js')) ?>><\\/script>')"></script>
</body></html>