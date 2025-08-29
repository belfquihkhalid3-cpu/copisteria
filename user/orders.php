<?php
session_start();
require_once '../config/config.php';
require_once '../classes/Database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Filtros
$statusFilter = $_GET['status'] ?? '';
$dateFilter = $_GET['date'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

// Construir la consulta WHERE
$whereConditions = ['user_id = ?'];
$params = [$userId];

if (!empty($statusFilter)) {
    $whereConditions[] = 'status = ?';
    $params[] = $statusFilter;
}

if (!empty($dateFilter)) {
    switch ($dateFilter) {
        case 'today':
            $whereConditions[] = 'DATE(created_at) = CURDATE()';
            break;
        case 'week':
            $whereConditions[] = 'created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)';
            break;
        case 'month':
            $whereConditions[] = 'created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)';
            break;
        case 'year':
            $whereConditions[] = 'created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)';
            break;
    }
}

$whereClause = implode(' AND ', $whereConditions);

// Contar el total d'éléments
$stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE $whereClause");
$stmt->execute($params);
$totalOrders = $stmt->fetchColumn();
$totalPages = ceil($totalOrders / $limit);

// Récupérer les commandes
$stmt = $conn->prepare("
    SELECT id, order_number, status, payment_status, total_price, total_files, 
           total_pages, created_at, estimated_completion, pickup_code, priority
    FROM orders 
    WHERE $whereClause 
    ORDER BY created_at DESC 
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques pour les filtres
$stmt = $conn->prepare("
    SELECT status, COUNT(*) as count 
    FROM orders 
    WHERE user_id = ? 
    GROUP BY status
");
$stmt->execute([$userId]);
$statusStats = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $statusStats[$row['status']] = $row['count'];
}

$pageTitle = 'Mis Pedidos - Copisteria Low Cost';
include '../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1">Mis Pedidos</h1>
                    <p class="text-muted mb-0">Gestiona y consulta todos tus pedidos</p>
                </div>
                <div>
                    <a href="../print.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Nuevo Pedido
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros y estadísticas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-end">
                        <!-- Filtros -->
                        <div class="col-md-3 mb-2">
                            <label for="statusFilter" class="form-label small">Estado</label>
                            <select class="form-select" id="statusFilter" onchange="applyFilters()">
                                <option value="">Todos los estados</option>
                                <option value="DRAFT" <?php echo $statusFilter === 'DRAFT' ? 'selected' : ''; ?>>Borrador</option>
                                <option value="PENDING" <?php echo $statusFilter === 'PENDING' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="PAID" <?php echo $statusFilter === 'PAID' ? 'selected' : ''; ?>>Pagado</option>
                                <option value="PROCESSING" <?php echo $statusFilter === 'PROCESSING' ? 'selected' : ''; ?>>Procesando</option>
                                <option value="PRINTING" <?php echo $statusFilter === 'PRINTING' ? 'selected' : ''; ?>>Imprimiendo</option>
                                <option value="READY" <?php echo $statusFilter === 'READY' ? 'selected' : ''; ?>>Listo</option>
                                <option value="COMPLETED" <?php echo $statusFilter === 'COMPLETED' ? 'selected' : ''; ?>>Completado</option>
                                <option value="CANCELLED" <?php echo $statusFilter === 'CANCELLED' ? 'selected' : ''; ?>>Cancelado</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label for="dateFilter" class="form-label small">Período</label>
                            <select class="form-select" id="dateFilter" onchange="applyFilters()">
                                <option value="">Todo el tiempo</option>
                                <option value="today" <?php echo $dateFilter === 'today' ? 'selected' : ''; ?>>Hoy</option>
                                <option value="week" <?php echo $dateFilter === 'week' ? 'selected' : ''; ?>>Última semana</option>
                                <option value="month" <?php echo $dateFilter === 'month' ? 'selected' : ''; ?>>Último mes</option>
                                <option value="year" <?php echo $dateFilter === 'year' ? 'selected' : ''; ?>>Último año</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-outline-secondary w-100" onclick="clearFilters()">
                                <i class="fas fa-times me-2"></i>Limpiar Filtros
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="small text-muted">
                                Mostrando <?php echo min($offset + 1, $totalOrders); ?>-<?php echo min($offset + $limit, $totalOrders); ?> 
                                de <?php echo number_format($totalOrders); ?> pedidos
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas rápidas -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="d-flex flex-wrap gap-2">
                                <?php
                                $statusLabels = [
                                    'DRAFT' => 'Borradores',
                                    'PENDING' => 'Pendientes', 
                                    'PROCESSING' => 'En Proceso',
                                    'READY' => 'Listos',
                                    'COMPLETED' => 'Completados'
                                ];
                                $statusColors = [
                                    'DRAFT' => 'secondary',
                                    'PENDING' => 'warning',
                                    'PROCESSING' => 'primary', 
                                    'READY' => 'success',
                                    'COMPLETED' => 'info'
                                ];
                                ?>
                                <?php foreach ($statusLabels as $status => $label): ?>
                                    <?php $count = $statusStats[$status] ?? 0; ?>
                                    <?php if ($count > 0): ?>
                                        <span class="badge bg-<?php echo $statusColors[$status]; ?> fs-6">
                                            <?php echo $label; ?>: <?php echo $count; ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de pedidos -->
    <div class="row">
        <div class="col-12">
            <?php if (empty($orders)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-inbox fa-4x text-muted mb-4"></i>
                        <h5 class="text-muted">No se encontraron pedidos</h5>
                        <p class="text-muted mb-4">
                            <?php if (!empty($statusFilter) || !empty($dateFilter)): ?>
                                Intenta cambiar los filtros o
                            <?php else: ?>
                                ¡Crea tu primer pedido y
                            <?php endif; ?>
                            aprovecha nuestros servicios de impresión.
                        </p>
                        <a href="../print.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Crear Pedido
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Pedido</th>
                                        <th>Estado</th>
                                        <th>Pago</th>
                                        <th>Archivos</th>
                                        <th>Páginas</th>
                                        <th>Total</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($order['priority'] === 'URGENT'): ?>
                                                    <i class="fas fa-exclamation-triangle text-danger me-2" title="Urgente"></i>
                                                <?php elseif ($order['priority'] === 'HIGH'): ?>
                                                    <i class="fas fa-exclamation text-warning me-2" title="Alta prioridad"></i>
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                                    <?php if ($order['pickup_code'] && in_array($order['status'], ['READY', 'COMPLETED'])): ?>
                                                        <br><small class="text-muted">Código: <code><?php echo $order['pickup_code']; ?></code></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = [
                                                'DRAFT' => 'secondary',
                                                'PENDING' => 'warning',
                                                'PAID' => 'info',
                                                'PROCESSING' => 'primary',
                                                'PRINTING' => 'primary',
                                                'READY' => 'success',
                                                'COMPLETED' => 'success',
                                                'CANCELLED' => 'danger'
                                            ][$order['status']] ?? 'secondary';
                                            
                                            $statusText = [
                                                'DRAFT' => 'Borrador',
                                                'PENDING' => 'Pendiente',
                                                'PAID' => 'Pagado',
                                                'PROCESSING' => 'Procesando',
                                                'PRINTING' => 'Imprimiendo',
                                                'READY' => 'Listo',
                                                'COMPLETED' => 'Completado',
                                                'CANCELLED' => 'Cancelado'
                                            ][$order['status']] ?? $order['status'];
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                            <?php if ($order['estimated_completion'] && $order['status'] !== 'COMPLETED'): ?>
                                                <br><small class="text-muted">Est: <?php echo date('d/m H:i', strtotime($order['estimated_completion'])); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $paymentClass = [
                                                'PENDING' => 'warning',
                                                'PAID' => 'success',
                                                'PARTIAL' => 'info',
                                                'FAILED' => 'danger',
                                                'REFUNDED' => 'secondary'
                                            ][$order['payment_status']] ?? 'secondary';
                                            
                                            $paymentText = [
                                                'PENDING' => 'Pendiente',
                                                'PAID' => 'Pagado',
                                                'PARTIAL' => 'Parcial',
                                                'FAILED' => 'Fallido',
                                                'REFUNDED' => 'Reembolsado'
                                            ][$order['payment_status']] ?? $order['payment_status'];
                                            ?>
                                            <span class="badge bg-<?php echo $paymentClass; ?> bg-opacity-25 text-<?php echo $paymentClass; ?>"><?php echo $paymentText; ?></span>
                                        </td>
                                        <td>
                                            <i class="fas fa-file-alt me-1"></i><?php echo $order['total_files']; ?>
                                        </td>
                                        <td>
                                            <i class="fas fa-copy me-1"></i><?php echo number_format($order['total_pages']); ?>
                                        </td>
                                        <td>
                                            <strong><?php echo number_format($order['total_price'], 2); ?>€</strong>
                                        </td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($order['created_at'])); ?>
                                            <br><small class="text-muted"><?php echo date('H:i', strtotime($order['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="order-detail.php?id=<?php echo $order['id']; ?>" 
                                                   class="btn btn-outline-primary btn-sm" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($order['status'] === 'DRAFT'): ?>
                                                    <a href="../print.php?order=<?php echo $order['id']; ?>" 
                                                       class="btn btn-outline-warning btn-sm" title="Continuar editando">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (in_array($order['status'], ['DRAFT', 'PENDING']) && $order['payment_status'] === 'PENDING'): ?>
                                                    <button class="btn btn-outline-danger btn-sm" 
                                                            onclick="cancelOrder(<?php echo $order['id']; ?>)" 
                                                            title="Cancelar pedido">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Paginación -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Navegación de pedidos" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page - 1); ?><?php echo buildQueryString(); ?>">
                                        <i class="fas fa-chevron-left"></i> Anterior
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 2);
                            $end = min($totalPages, $page + 2);
                            
                            if ($start > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1<?php echo buildQueryString(); ?>">1</a>
                                </li>
                                <?php if ($start > 2): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $start; $i <= $end; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo buildQueryString(); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($end < $totalPages): ?>
                                <?php if ($end < $totalPages - 1): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo buildQueryString(); ?>"><?php echo $totalPages; ?></a>
                                </li>
                            <?php endif; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page + 1); ?><?php echo buildQueryString(); ?>">
                                        Siguiente <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de confirmación para cancelar -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancelar Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas cancelar este pedido?</p>
                <p class="text-muted">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, mantener</button>
                <button type="button" class="btn btn-danger" id="confirmCancelBtn">Sí, cancelar pedido</button>
            </div>
        </div>
    </div>
</div>

<?php
function buildQueryString() {
    global $statusFilter, $dateFilter;
    $params = [];
    
    if (!empty($statusFilter)) {
        $params[] = 'status=' . urlencode($statusFilter);
    }
    
    if (!empty($dateFilter)) {
        $params[] = 'date=' . urlencode($dateFilter);
    }
    
    return !empty($params) ? '&' . implode('&', $params) : '';
}
?>

<script>
function applyFilters() {
    const status = document.getElementById('statusFilter').value;
    const date = document.getElementById('dateFilter').value;
    
    let url = 'orders.php?page=1';
    
    if (status) url += '&status=' + encodeURIComponent(status);
    if (date) url += '&date=' + encodeURIComponent(date);
    
    window.location.href = url;
}

function clearFilters() {
    window.location.href = 'orders.php';
}

let orderToCancel = null;

function cancelOrder(orderId) {
    orderToCancel = orderId;
    const modal = new bootstrap.Modal(document.getElementById('cancelOrderModal'));
    modal.show();
}

document.getElementById('confirmCancelBtn').addEventListener('click', function() {
    if (orderToCancel) {
        fetch('../ajax/cancel-order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                csrf_token: '<?php echo $_SESSION['csrf_token'] ?? ''; ?>',
                order_id: orderToCancel
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error al cancelar el pedido: ' + (data.error || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión');
        })
        .finally(() => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('cancelOrderModal'));
            modal.hide();
            orderToCancel = null;
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>