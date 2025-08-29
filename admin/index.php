<?php
require_once '../config/config.php';
require_once '../classes/Database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../auth/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Estadísticas del dashboard usando consultas más simples
$dashboardStats = [
    'pending_orders' => 0,
    'processing_orders' => 0,
    'ready_orders' => 0,
    'today_orders' => 0,
    'today_revenue' => 0,
    'new_users_today' => 0,
    'active_sessions' => 0
];

try {
    // Pedidos pendientes
    $stmt = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'PENDING'");
    $dashboardStats['pending_orders'] = $stmt->fetchColumn();

    // Pedidos en proceso
    $stmt = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'PROCESSING'");
    $dashboardStats['processing_orders'] = $stmt->fetchColumn();

    // Pedidos listos
    $stmt = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'READY'");
    $dashboardStats['ready_orders'] = $stmt->fetchColumn();

    // Pedidos de hoy
    $stmt = $conn->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()");
    $dashboardStats['today_orders'] = $stmt->fetchColumn();

    // Ingresos de hoy
    $stmt = $conn->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE DATE(created_at) = CURDATE()");
    $dashboardStats['today_revenue'] = $stmt->fetchColumn();

    // Nuevos usuarios hoy
    $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()");
    $dashboardStats['new_users_today'] = $stmt->fetchColumn();

    // Sesiones activas
    $stmt = $conn->query("SELECT COUNT(*) FROM user_sessions WHERE is_active = 1");
    $dashboardStats['active_sessions'] = $stmt->fetchColumn();
} catch (Exception $e) {
    error_log("Error getting dashboard stats: " . $e->getMessage());
}

// Pedidos recientes que requieren atención
$activeOrders = [];
try {
    $stmt = $conn->prepare("
        SELECT o.id, o.order_number, o.status, o.total_price, o.total_files, 
               o.created_at, o.priority, u.first_name, u.last_name, u.email
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.status IN ('PENDING', 'PAID', 'PROCESSING', 'PRINTING')
        ORDER BY 
            CASE o.priority 
                WHEN 'URGENT' THEN 1 
                WHEN 'HIGH' THEN 2 
                WHEN 'NORMAL' THEN 3 
                ELSE 4 
            END,
            o.created_at ASC
        LIMIT 10
    ");
    $stmt->execute();
    $activeOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error getting active orders: " . $e->getMessage());
}

// Estadísticas de ingresos (últimos 7 días) - versión simple
$weeklyStats = [];
try {
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $stmt = $conn->prepare("
            SELECT 
                ? as date,
                COUNT(*) as orders_count,
                COALESCE(SUM(total_price), 0) as revenue,
                COALESCE(SUM(total_pages), 0) as pages
            FROM orders 
            WHERE status = 'COMPLETED' 
              AND DATE(created_at) = ?
        ");
        $stmt->execute([$date, $date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['orders_count'] > 0) {
            $weeklyStats[] = $result;
        }
    }
} catch (Exception $e) {
    error_log("Error getting weekly stats: " . $e->getMessage());
}

// Usuarios más activos (últimos 30 días) - versión simple
$topUsers = [];
try {
    $date30DaysAgo = date('Y-m-d', strtotime('-30 days'));
    $stmt = $conn->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email,
               COUNT(o.id) as orders_count,
               COALESCE(SUM(o.total_price), 0) as total_spent,
               MAX(o.created_at) as last_order
        FROM users u
        JOIN orders o ON u.id = o.user_id
        WHERE o.status != 'CANCELLED'
          AND DATE(o.created_at) >= ?
        GROUP BY u.id, u.first_name, u.last_name, u.email
        ORDER BY orders_count DESC, total_spent DESC
        LIMIT 5
    ");
    $stmt->execute([$date30DaysAgo]);
    $topUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error getting top users: " . $e->getMessage());
}

// Alertas del sistema
$alerts = [];

// Verificar pedidos atrasados
try {
    $stmt = $conn->query("
        SELECT COUNT(*) as delayed_orders
        FROM orders 
        WHERE status IN ('PROCESSING', 'PRINTING') 
          AND estimated_completion < NOW()
    ");
    $delayedCount = $stmt->fetchColumn();
    if ($delayedCount > 0) {
        $alerts[] = [
            'type' => 'warning',
            'icon' => 'fas fa-clock',
            'title' => 'Pedidos Retrasados',
            'message' => "$delayedCount pedidos están retrasados respecto a su fecha estimada",
            'action' => 'orders.php?filter=delayed'
        ];
    }
} catch (Exception $e) {
    error_log("Error checking delayed orders: " . $e->getMessage());
}

// Verificar archivos sin procesar
try {
    $stmt = $conn->query("
        SELECT COUNT(*) as pending_files
        FROM order_items 
        WHERE processing_status = 'PENDING'
    ");
    $pendingFiles = $stmt->fetchColumn();
    if ($pendingFiles > 0) {
        $alerts[] = [
            'type' => 'info',
            'icon' => 'fas fa-file-upload',
            'title' => 'Archivos Pendientes',
            'message' => "$pendingFiles archivos esperando procesamiento",
            'action' => 'files.php?status=pending'
        ];
    }
} catch (Exception $e) {
    error_log("Error checking pending files: " . $e->getMessage());
}

$pageTitle = 'Panel de Administración - Copisteria Low Cost';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .card {
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-2px);
        }
        .stats-card {
            background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary) 100%);
        }
        .table-responsive {
            border-radius: 0.5rem;
            overflow: hidden;
        }
        .priority-urgent {
            border-left: 4px solid #dc3545;
        }
        .priority-high {
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body class="bg-light">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid px-4 py-3">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-1">Panel de Administración</h1>
                        <p class="text-muted mb-0">Gestión completa del sistema de impresión</p>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog me-2"></i>Acciones
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="orders.php"><i class="fas fa-list me-2"></i>Gestionar Pedidos</a></li>
                            <li><a class="dropdown-item" href="users.php"><i class="fas fa-users me-2"></i>Gestionar Usuarios</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cogs me-2"></i>Configuración</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alertas del sistema -->
        <?php if (!empty($alerts)): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <?php foreach ($alerts as $alert): ?>
                        <div class="alert alert-<?php echo $alert['type']; ?> alert-dismissible fade show" role="alert">
                            <i class="<?php echo $alert['icon']; ?> me-2"></i>
                            <strong><?php echo $alert['title']; ?>:</strong> <?php echo $alert['message']; ?>
                            <?php if (isset($alert['action'])): ?>
                                <a href="<?php echo $alert['action']; ?>" class="alert-link ms-2">Ver detalles →</a>
                            <?php endif; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Estadísticas principales -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-clock fa-2x opacity-75"></i>
                            </div>
                            <div>
                                <div class="h3 mb-0"><?php echo number_format($dashboardStats['pending_orders']); ?></div>
                                <div class="small">Pedidos Pendientes</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <a class="small text-white stretched-link" href="orders.php?status=pending">Ver Detalles</a>
                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card bg-warning text-white h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-print fa-2x opacity-75"></i>
                            </div>
                            <div>
                                <div class="h3 mb-0"><?php echo number_format($dashboardStats['processing_orders']); ?></div>
                                <div class="small">En Proceso</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <a class="small text-white stretched-link" href="orders.php?status=processing">Ver Detalles</a>
                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-check-circle fa-2x opacity-75"></i>
                            </div>
                            <div>
                                <div class="h3 mb-0"><?php echo number_format($dashboardStats['ready_orders']); ?></div>
                                <div class="small">Listos para Recoger</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <a class="small text-white stretched-link" href="orders.php?status=ready">Ver Detalles</a>
                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card bg-info text-white h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-euro-sign fa-2x opacity-75"></i>
                            </div>
                            <div>
                                <div class="h3 mb-0"><?php echo number_format($dashboardStats['today_revenue'], 0); ?>€</div>
                                <div class="small">Ingresos Hoy</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <a class="small text-white stretched-link" href="reports.php?period=today">Ver Detalles</a>
                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Columna principal -->
            <div class="col-xl-8">
                <!-- Pedidos activos -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-tasks me-2"></i>Pedidos Activos
                        </h5>
                        <a href="orders.php" class="btn btn-outline-primary btn-sm">Ver Todos</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($activeOrders)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-clipboard-check fa-3x text-success mb-3"></i>
                                <h6 class="text-muted">¡Todo al día!</h6>
                                <p class="text-muted mb-0">No hay pedidos pendientes de atención</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Pedido</th>
                                            <th>Cliente</th>
                                            <th>Estado</th>
                                            <th>Archivos</th>
                                            <th>Total</th>
                                            <th>Fecha</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activeOrders as $order): ?>
                                        <tr class="<?php echo $order['priority'] === 'URGENT' ? 'priority-urgent' : ($order['priority'] === 'HIGH' ? 'priority-high' : ''); ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php echo renderPriorityIcon($order['priority']); ?>
                                                    <strong class="ms-2"><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo renderStatusBadge($order['status'], 'order'); ?>
                                            </td>
                                            <td><?php echo $order['total_files']; ?></td>
                                            <td><strong><?php echo formatPrice($order['total_price']); ?></strong></td>
                                            <td><?php echo formatDate($order['created_at'], 'd/m H:i'); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="order-detail.php?id=<?php echo $order['id']; ?>" 
                                                       class="btn btn-outline-primary" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button class="btn btn-outline-success" 
                                                            onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'next')" 
                                                            title="Avanzar estado">
                                                        <i class="fas fa-arrow-right"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Estadísticas de ingresos -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>Ingresos de los Últimos 7 Días
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($weeklyStats)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No hay datos de ingresos para mostrar</p>
                            </div>
                        <?php else: ?>
                            <canvas id="revenueChart" height="100"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Columna lateral -->
            <div class="col-xl-4">
                <!-- Estadísticas adicionales -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie me-2"></i>Estadísticas del Día
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="border-end">
                                    <div class="h4 text-primary mb-1"><?php echo number_format($dashboardStats['today_orders']); ?></div>
                                    <div class="small text-muted">Pedidos Hoy</div>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="h4 text-success mb-1"><?php echo number_format($dashboardStats['new_users_today']); ?></div>
                                <div class="small text-muted">Nuevos Usuarios</div>
                            </div>
                            <div class="col-6">
                                <div class="border-end">
                                    <div class="h4 text-info mb-1"><?php echo number_format($dashboardStats['active_sessions']); ?></div>
                                    <div class="small text-muted">Sesiones Activas</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="h4 text-warning mb-1"><?php echo count($activeOrders); ?></div>
                                <div class="small text-muted">Requieren Atención</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Usuarios más activos -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>Clientes Top (30d)
                        </h5>
                        <a href="users.php" class="btn btn-outline-primary btn-sm">Ver Todos</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($topUsers)): ?>
                            <p class="text-muted text-center">No hay datos suficientes</p>
                        <?php else: ?>
                            <?php foreach ($topUsers as $i => $user): ?>
                            <div class="d-flex align-items-center mb-3 <?php echo $i === count($topUsers) - 1 ? '' : 'border-bottom pb-3'; ?>">
                                <div class="me-3">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-success"><?php echo formatPrice($user['total_spent'], ''); ?>€</div>
                                    <small class="text-muted"><?php echo $user['orders_count']; ?> pedidos</small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Acciones rápidas -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>Acceso Rápido
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="orders.php?status=pending" class="btn btn-outline-warning">
                                <i class="fas fa-clock me-2"></i>Procesar Pendientes
                            </a>
                            <a href="orders.php?status=ready" class="btn btn-outline-success">
                                <i class="fas fa-check me-2"></i>Marcar Entregados
                            </a>
                            <a href="users.php?action=new" class="btn btn-outline-primary">
                                <i class="fas fa-user-plus me-2"></i>Crear Usuario
                            </a>
                            <a href="settings.php" class="btn btn-outline-secondary">
                                <i class="fas fa-cogs me-2"></i>Configuración
                            </a>
                            <hr>
                            <button class="btn btn-outline-info" onclick="refreshDashboard()">
                                <i class="fas fa-sync-alt me-2"></i>Actualizar Dashboard
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
    // Gráfico de ingresos
    <?php if (!empty($weeklyStats)): ?>
    const ctx = document.getElementById('revenueChart').getContext('2d');
    const revenueChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($weeklyStats, 'date')); ?>,
            datasets: [{
                label: 'Ingresos (€)',
                data: <?php echo json_encode(array_column($weeklyStats, 'revenue')); ?>,
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Pedidos',
                data: <?php echo json_encode(array_column($weeklyStats, 'orders_count')); ?>,
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });
    <?php endif; ?>

    function updateOrderStatus(orderId, action) {
        if (!confirm('¿Avanzar al siguiente estado?')) return;
        
        fetch('../ajax/update-order-status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                csrf_token: '<?php echo $_SESSION['csrf_token'] ?? ''; ?>',
                order_id: orderId,
                action: action
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión');
        });
    }

    function refreshDashboard() {
        location.reload();
    }

    // Auto-refresh cada 5 minutos
    setInterval(() => {
        const lastRefresh = new Date().toLocaleTimeString();
        console.log('Dashboard auto-refresh: ' + lastRefresh);
        refreshDashboard();
    }, 300000);
    </script>
</body>
</html>