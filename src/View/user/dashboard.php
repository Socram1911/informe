<?php include __DIR__ . '/../layout/header.php'; ?>

<div class="row">
  <div class="col-12">
    <h3>Bienvenido, <?= e($user['name']) ?></h3>
  </div>
</div>

<div class="row mt-3">
  <?php if (in_array($user['role'], [ROLE_SUPERVISOR, ROLE_ADMIN], true)): ?>
    <!-- VISTA ADMIN/SUPERVISOR -->
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
             <span>Últimos informes</span>
             <a class="btn btn-sm btn-primary" href="<?= e(app_url('admin/reports')) ?>">Gestionar</a>
        </div>
        <div class="card-body">
          <table class="table table-sm table-striped">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Periodo</th>
                    <th>Estado</th>
                    <th>Actualizado</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($data)): ?>
                <tr><td colspan="4" class="text-center text-muted">No hay informes recientes.</td></tr>
            <?php else: ?>
                <?php foreach ($data as $inf): ?>
                <tr>
                    <td><?= e($inf['titulo'] ?? $inf['title'] ?? '') ?></td>
                    <td><?= e($inf['periodo'] ?? $inf['period'] ?? '') ?></td>
                    <td><span class="badge bg-secondary"><?= e($inf['estado'] ?? $inf['status'] ?? '') ?></span></td>
                    <td><?= e($inf['actualizado_en'] ?? $inf['updated_at'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  <?php else: ?>
    <!-- VISTA EDITOR -->
    <div class="col-12">
      <div class="card">
        <div class="card-header">Tus secciones asignadas</div>
        <div class="card-body">
          <table class="table table-sm table-hover">
            <thead>
                <tr>
                    <th>Informe</th>
                    <th>Tu Sección</th>
                    <th>Estado</th>
                    <th>Periodo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($data)): ?>
                <tr><td colspan="5" class="text-center text-muted">No tienes secciones asignadas.</td></tr>
            <?php else: ?>
                <?php foreach ($data as $sec): ?>
                <tr>
                    <td><?= e($sec['informe_titulo']) ?></td>
                    <td><?= e($sec['seccion_titulo']) ?></td>
                    <td><span class="badge bg-info"><?= e($sec['seccion_estado']) ?></span></td>
                    <td><?= e($sec['periodo']) ?></td>
                    <td>
                        <a class="btn btn-sm btn-primary" href="<?= e(app_url('editor?section_id=' . (int)$sec['seccion_id'])) ?>">
                            Editar
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../layout/footer.php'; ?>
