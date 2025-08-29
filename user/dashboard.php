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

// Récupérer les données utilisateur
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Statistiques utilisateur
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN status = 'COMPLETED' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN status IN ('PENDING', 'PROCESSING', 'PRINTING') THEN 1 END) as active_orders,
        COALESCE(SUM(CASE WHEN status = 'COMPLETED' THEN total_price END), 0) as total_spent,
        COALESCE(SUM(CASE WHEN status = 'COMPLETED' THEN total_pages END), 0) as total_pages
    FROM orders WHERE user_id = ? AND status != 'CANCELLED'
");
$stmt->execute([$userId]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Commandes récentes
$stmt = $conn->prepare("
    SELECT id, order_number, status, total_price, total_files, created_at,
           estimated_completion, pickup_code
    FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$userId]);
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Notifications non lues
$stmt = $conn->prepare("
    SELECT id, title, message, notification_type, created_at
    FROM notifications 
    WHERE user_id = ? AND is_read = 0 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Configurations sauvegardées
$stmt = $conn->prepare("
    SELECT id, config_name, paper_size, color_mode, binding, usage_count, created_at
    FROM print_configurations 
    WHERE user_id = ? 
    ORDER BY is_default DESC, usage_count DESC 
    LIMIT 5
");
$stmt->execute([$userId]);
$configurations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Panel - Copisteria Low Cost</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }
        
        .stats-card {
            background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary) 100%);
        }
        
        .table-responsive {
            border-radius: 8px;
        }
        
        .badge {
            font-size: 0.75em;
            padding: 0.5em 0.75em;
        }
        
        .btn {
            border-radius: 8px;
        }
        
        .list-group-item {
            border: none;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .list-group-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="../index.php">
                <i class="fas fa-print me-2"></i>Copisteria Low Cost
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="fas fa-home me-1"></i>Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../print.php">
                            <i class="fas fa-print me-1"></i>Imprimir
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Mi Panel</a></li>
                            <li><a class="dropdown-item" href="orders.php">
                                <i class="fas fa-shopping-cart me-2"></i>Mis Pedidos</a></li>
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user me-2"></i>Mi Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 py-3">
        <!-- En-tête de bienvenue -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-1">¡Hola, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
                        <p class="text-muted mb-0">Bienvenido/a de vuelta a tu panel de control</p>
                    </div>
                    <div>
                        <a href="../print.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Nuevo Pedido
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas principales -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-shopping-cart fa-2x opacity-75"></i>
                        </div>
                        <div>
                            <div class="h4 mb-0"><?php echo number_format($stats['total_orders']); ?></div>
                            <div class="small">Total Pedidos</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-success text-white h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-check-circle fa-2x opacity-75"></i>
                        </div>
                        <div>
                            <div class="h4 mb-0"><?php echo number_format($stats['completed_orders']); ?></div>
                            <div class="small">Completados</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-warning text-white h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-clock fa-2x opacity-75"></i>
                        </div>
                        <div>
                            <div class="h4 mb-0"><?php echo number_format($stats['active_orders']); ?></div>
                            <div class="small">En Proceso</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-info text-white h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-euro-sign fa-2x opacity-75"></i>
                        </div>
                        <div>
                            <div class="h4 mb-0"><?php echo number_format($stats['total_spent'], 2); ?>€</div>
                            <div class="small">Total Gastado</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Columna principal -->
            <div class="col-lg-8">
                <!-- Pedidos recientes -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list-alt me-2"></i>Pedidos Recientes
                        </h5>
                        <a href="orders.php" class="btn btn-outline-primary btn-sm">Ver Todos</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentOrders)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No tienes pedidos aún</h6>
                                <p class="text-muted mb-3">¡Crea tu primer pedido y aprovecha nuestros servicios!</p>
                                <a href="../print.php" class="btn btn-primary">Hacer Primer Pedido</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Pedido</th>
                                            <th>Estado</th>
                                            <th>Archivos</th>
                                            <th>Total</th>
                                            <th>Fecha</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentOrders as $order): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                                <?php if ($order['pickup_code'] && in_array($order['status'], ['READY', 'COMPLETED'])): ?>
                                                    <br><small class="text-muted">Código: <?php echo $order['pickup_code']; ?></small>
                                                <?php endif; ?>
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
                                            </td>
                                            <td><?php echo $order['total_files']; ?> archivo(s)</td>
                                            <td><strong><?php echo number_format($order['total_price'], 2); ?>€</strong></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                            <td>
                                                <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Configuraciones guardadas -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-save me-2"></i>Mis Configuraciones
                        </h5>
                        <a href="configurations.php" class="btn btn-outline-primary btn-sm">Gestionar</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($configurations)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-cogs fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No tienes configuraciones guardadas</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($configurations as $config): ?>
                                <div class="col-md-6 col-xl-4 mb-3">
                                    <div class="card border">
                                        <div class="card-body p-3">
                                            <h6 class="card-title"><?php echo htmlspecialchars($config['config_name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo $config['paper_size']; ?> • 
                                                <?php echo $config['color_mode'] === 'BW' ? 'B/N' : 'Color'; ?>
                                                <?php if ($config['binding'] !== 'NONE'): ?>
                                                    • <?php echo $config['binding']; ?>
                                                <?php endif; ?>
                                            </small>
                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                <small class="text-success">Usado <?php echo $config['usage_count']; ?> veces</small>
                                                <button class="btn btn-sm btn-outline-primary" onclick="useConfiguration(<?php echo $config['id']; ?>)">
                                                    Usar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Barra lateral -->
            <div class="col-lg-4">
                <!-- Notificaciones -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-bell me-2"></i>Notificaciones
                            <?php if (count($notifications) > 0): ?>
                                <span class="badge bg-danger"><?php echo count($notifications); ?></span>
                            <?php endif; ?>
                        </h5>
                        <?php if (count($notifications) > 0): ?>
                            <button class="btn btn-sm btn-outline-secondary" onclick="markAllAsRead()">
                                Marcar leídas
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($notifications)): ?>
                            <div class="p-4 text-center">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                <p class="mb-0 text-muted">¡Todo al día!</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($notifications as $notification): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                        <small><?php echo date('d/m H:i', strtotime($notification['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-1 small"><?php echo htmlspecialchars($notification['message']); ?></p>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Acceso rápido -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-bolt me-2"></i>Acceso Rápido
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="../print.php" class="btn btn-primary">
                                <i class="fas fa-print me-2"></i>Nuevo Pedido
                            </a>
                            <a href="orders.php" class="btn btn-outline-secondary">
                                <i class="fas fa-list me-2"></i>Mis Pedidos
                            </a>
                            <a href="profile.php" class="btn btn-outline-secondary">
                                <i class="fas fa-user me-2"></i>Mi Perfil
                            </a>
                            <a href="configurations.php" class="btn btn-outline-secondary">
                                <i class="fas fa-cog me-2"></i>Configuraciones
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Información de cuenta -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Mi Cuenta
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="fas fa-envelope text-muted me-2"></i>
                                <?php echo htmlspecialchars($user['email']); ?>
                            </li>
                            <?php if ($user['phone']): ?>
                            <li class="mb-2">
                                <i class="fas fa-phone text-muted me-2"></i>
                                <?php echo htmlspecialchars($user['phone']); ?>
                            </li>
                            <?php endif; ?>
                            <li class="mb-2">
                                <i class="fas fa-calendar text-muted me-2"></i>
                                Desde <?php echo date('M Y', strtotime($user['created_at'])); ?>
                            </li>
                            <li>
                                <i class="fas fa-file-alt text-muted me-2"></i>
                                <?php echo number_format($stats['total_pages']); ?> páginas impresas
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function useConfiguration(configId) {
        window.location.href = '../print.php?config=' + configId;
    }

    function markAllAsRead() {
        fetch('../ajax/mark-notifications-read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                csrf_token: '<?php echo $_SESSION['csrf_token'] ?? ''; ?>',
                mark_all: true
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => console.error('Error:', error));
    }
    </script>
</body>
</html>