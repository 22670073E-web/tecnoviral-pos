<?php
session_start();
require_once 'conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_nombre = $_SESSION['user_nombre'];
$user_rol = $_SESSION['user_rol'];

// Obtener filtro de categoría
$categoria_filtro = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;

// Obtener listado de productos con stock bajo para alertas
$query_alertas = "SELECT p.*, c.nombre as categoria_nombre 
                  FROM productos p 
                  LEFT JOIN categorias c ON p.id_categoria = c.id 
                  WHERE p.activo = 1 AND p.stock <= p.stock_minimo 
                  ORDER BY p.stock ASC";
$result_alertas = mysqli_query($conn, $query_alertas);
$total_alertas = mysqli_num_rows($result_alertas);

// Obtener productos para el inventario (con filtro por categoría)
if ($categoria_filtro > 0) {
    $query_productos = "SELECT p.*, c.nombre as categoria_nombre 
                        FROM productos p 
                        LEFT JOIN categorias c ON p.id_categoria = c.id 
                        WHERE p.activo = 1 AND p.id_categoria = $categoria_filtro 
                        ORDER BY p.stock ASC";
} else {
    $query_productos = "SELECT p.*, c.nombre as categoria_nombre 
                        FROM productos p 
                        LEFT JOIN categorias c ON p.id_categoria = c.id 
                        WHERE p.activo = 1 
                        ORDER BY 
                            CASE 
                                WHEN p.stock <= p.stock_minimo THEN 0 
                                ELSE 1 
                            END, 
                            p.stock ASC";
}
$result_productos = mysqli_query($conn, $query_productos);

// Obtener categorías para el filtro
$query_categorias = "SELECT * FROM categorias ORDER BY nombre";
$result_categorias = mysqli_query($conn, $query_categorias);

// Obtener movimientos recientes (SOLO CONSULTA)
$query_movimientos = "SELECT m.*, p.nombre as producto_nombre, u.nombre as usuario_nombre 
                      FROM movimientos_inventario m 
                      JOIN productos p ON m.id_producto = p.id 
                      JOIN usuarios u ON m.id_usuario = u.id 
                      ORDER BY m.fecha_movimiento DESC 
                      LIMIT 50";
$result_movimientos = mysqli_query($conn, $query_movimientos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>TecnoViral - Control de Inventario</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #0a1929 0%, #0b1e3a 50%, #0b2554 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        /* Encabezado */
        .header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 30px;
            padding: 20px 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .logo-area {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .logo-mini {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 5px 15px rgba(0, 102, 204, 0.3);
        }
        
        .titulo h1 {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            background: linear-gradient(135deg, #0a1929 0%, #0066cc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .titulo p {
            color: #666;
            margin: 0;
            font-size: 14px;
        }
        
        .user-info {
            background: linear-gradient(135deg, #0066cc, #0a1929);
            padding: 12px 25px;
            border-radius: 50px;
            color: white;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .btn-back {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .btn-back:hover {
            background: rgba(255,255,255,0.3);
            transform: translateX(-5px);
        }
        
        /* Alertas */
        .alertas-card {
            background: linear-gradient(135deg, #ff6b6b, #ee5253);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 10px 30px rgba(238, 82, 83, 0.3);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 10px 30px rgba(238, 82, 83, 0.3); }
            50% { box-shadow: 0 15px 40px rgba(238, 82, 83, 0.5); }
            100% { box-shadow: 0 10px 30px rgba(238, 82, 83, 0.3); }
        }
        
        .alertas-icon {
            font-size: 40px;
            margin-right: 15px;
        }
        
        .alertas-text h3 {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
        }
        
        .alertas-text p {
            margin: 5px 0 0;
            opacity: 0.9;
        }
        
        .btn-ver-alertas {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
            padding: 10px 25px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-ver-alertas:hover {
            background: white;
            color: #ee5253;
        }
        
        /* Filtros */
        .filtros-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .filtros-card select {
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            font-size: 16px;
            min-width: 250px;
        }
        
        .btn-filtrar {
            background: linear-gradient(135deg, #0066cc, #0a1929);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-filtrar:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,102,204,0.3);
        }
        
        /* Grid de inventario */
        .inventario-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .producto-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 25px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: all 0.3s;
            border-left: 8px solid #28a745;
            position: relative;
            overflow: hidden;
        }
        
        .producto-card.stock-bajo {
            border-left-color: #dc3545;
            background: linear-gradient(135deg, #fff, #fff5f5);
            animation: parpadeo 2s infinite;
        }
        
        .producto-card.stock-medio {
            border-left-color: #ffc107;
        }
        
        @keyframes parpadeo {
            0% { background: #fff5f5; }
            50% { background: #ffe0e0; }
            100% { background: #fff5f5; }
        }
        
        .producto-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .producto-nombre h3 {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
            color: #0a1929;
        }
        
        .producto-nombre p {
            color: #666;
            font-size: 13px;
            margin: 5px 0 0;
        }
        
        .producto-categoria {
            background: #e3f2fd;
            color: #0066cc;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .producto-stock {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }
        
        .stock-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #28a745, #20c997);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            color: white;
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.3);
        }
        
        .stock-bajo .stock-circle {
            background: linear-gradient(135deg, #dc3545, #ff6b6b);
            box-shadow: 0 10px 20px rgba(220, 53, 69, 0.3);
        }
        
        .stock-medio .stock-circle {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            box-shadow: 0 10px 20px rgba(255, 193, 7, 0.3);
        }
        
        .stock-circle .numero {
            font-size: 32px;
            font-weight: 700;
            line-height: 1;
        }
        
        .stock-circle .texto {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .stock-minmax {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 12px;
            color: #666;
        }
        
        .stock-minmax span i {
            margin-right: 5px;
        }
        
        .info-ubicacion {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed #e0e0e0;
            display: flex;
            justify-content: space-between;
            color: #666;
            font-size: 13px;
        }
        
        .info-ubicacion i {
            color: #0066cc;
        }
        
        /* Tabla de movimientos */
        .movimientos-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 30px;
            padding: 30px;
            margin-top: 30px;
        }
        
        .movimientos-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .movimientos-title h3 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            color: #0a1929;
        }
        
        .badge-info {
            background: #e3f2fd;
            color: #0066cc;
            padding: 8px 15px;
            border-radius: 50px;
            font-size: 14px;
        }
        
        .movimientos-table {
            margin-top: 20px;
        }
        
        .movimientos-table th {
            background: #f8f9fa;
            color: #0a1929;
            font-weight: 600;
            border-bottom: 2px solid #0066cc;
        }
        
        .movimientos-table td {
            vertical-align: middle;
            padding: 15px 10px;
        }
        
        .badge-entrada {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-salida {
            background: linear-gradient(135deg, #dc3545, #ff6b6b);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-ajuste {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
            }
            
            .logo-area {
                flex-direction: column;
            }
            
            .inventario-grid {
                grid-template-columns: 1fr;
            }
            
            .movimientos-table {
                font-size: 14px;
            }
            
            .badge-entrada, .badge-salida, .badge-ajuste {
                padding: 3px 8px;
                font-size: 11px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Encabezado -->
        <div class="header">
            <div class="logo-area">
                <img src="imagenes/logoe.jpeg" alt="TecnoViral" class="logo-mini" onerror="this.src='https://via.placeholder.com/60x60?text=TV'">
                <div class="titulo">
                    <h1>TECNOVIRAL</h1>
                    <p>Control de Inventario</p>
                </div>
            </div>
            
            <div class="user-info">
                <a href="menu_principal.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <i class="fas fa-user-circle"></i>
                <span><?php echo htmlspecialchars($user_nombre); ?></span>
            </div>
        </div>
        
        <!-- Alerta de Stock Bajo -->
        <?php if ($total_alertas > 0): ?>
            <div class="alertas-card">
                <div style="display: flex; align-items: center;">
                    <i class="fas fa-exclamation-triangle alertas-icon"></i>
                    <div class="alertas-text">
                        <h3><?php echo $total_alertas; ?> producto(s) con stock bajo</h3>
                        <p>Se recomienda realizar un pedido</p>
                    </div>
                </div>
                <a href="#productos-bajos" class="btn-ver-alertas">
                    <i class="fas fa-eye me-2"></i>Ver productos
                </a>
            </div>
        <?php endif; ?>
        
        <!-- Filtros -->
        <div class="filtros-card">
            <i class="fas fa-filter" style="color: #0066cc; font-size: 20px;"></i>
            <form method="GET" action="" style="display: flex; gap: 15px; flex-wrap: wrap; width: 100%;">
                <select name="categoria" class="form-select" style="max-width: 300px;">
                    <option value="0">Todas las categorías</option>
                    <?php 
                    mysqli_data_seek($result_categorias, 0);
                    while ($cat = mysqli_fetch_assoc($result_categorias)): 
                    ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $categoria_filtro == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo $cat['nombre']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="btn-filtrar">
                    <i class="fas fa-search me-2"></i>Filtrar
                </button>
            </form>
        </div>
        
        <!-- Grid de Inventario -->
        <div class="inventario-grid" id="productos-bajos">
            <?php if (mysqli_num_rows($result_productos) > 0): ?>
                <?php while ($producto = mysqli_fetch_assoc($result_productos)): 
                    $stock_class = '';
                    if ($producto['stock'] <= $producto['stock_minimo']) {
                        $stock_class = 'stock-bajo';
                    } elseif ($producto['stock'] <= $producto['stock_minimo'] * 2) {
                        $stock_class = 'stock-medio';
                    }
                ?>
                    <div class="producto-card <?php echo $stock_class; ?>">
                        <div class="producto-header">
                            <div class="producto-nombre">
                                <h3><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                                <p><i class="fas fa-tag"></i> <?php echo htmlspecialchars($producto['marca']); ?></p>
                            </div>
                            <span class="producto-categoria">
                                <i class="fas fa-folder"></i> <?php echo $producto['categoria_nombre']; ?>
                            </span>
                        </div>
                        
                        <div class="producto-stock">
                            <div class="stock-circle">
                                <span class="numero"><?php echo $producto['stock']; ?></span>
                                <span class="texto">existencias</span>
                            </div>
                            <div class="stock-minmax">
                                <span><i class="fas fa-arrow-down" style="color: #dc3545;"></i> Mín: <?php echo $producto['stock_minimo']; ?></span>
                                <span><i class="fas fa-arrow-up" style="color: #28a745;"></i> Máx: <?php echo $producto['stock_maximo']; ?></span>
                            </div>
                        </div>
                        
                        <div class="info-ubicacion">
                            <span><i class="fas fa-map-pin"></i> <?php echo $producto['ubicacion'] ?: 'Sin ubicación'; ?></span>
                            <span><i class="fas fa-boxes"></i> Precio: $<?php echo number_format($producto['precio'], 2); ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-box-open fa-4x mb-3" style="color: #ccc;"></i>
                    <h3>No hay productos en esta categoría</h3>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Historial de Movimientos -->
        <div class="movimientos-card">
            <div class="movimientos-title">
                <h3>
                    <i class="fas fa-history me-2" style="color: #0066cc;"></i>
                    Historial de Movimientos
                </h3>
                <span class="badge-info">
                    <i class="fas fa-sync-alt me-1"></i> Últimos 50 movimientos
                </span>
            </div>
            
            <div class="table-responsive">
                <table class="table movimientos-table">
                    <thead>
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>Producto</th>
                            <th>Tipo</th>
                            <th>Cantidad</th>
                            <th>Motivo</th>
                            <th>Registrado por</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result_movimientos) > 0): ?>
                            <?php while ($mov = mysqli_fetch_assoc($result_movimientos)): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($mov['fecha_movimiento'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($mov['producto_nombre']); ?></strong></td>
                                    <td>
                                        <?php if ($mov['tipo_movimiento'] == 'entrada'): ?>
                                            <span class="badge-entrada">
                                                <i class="fas fa-arrow-up"></i> ENTRADA
                                            </span>
                                        <?php elseif ($mov['tipo_movimiento'] == 'salida'): ?>
                                            <span class="badge-salida">
                                                <i class="fas fa-arrow-down"></i> SALIDA
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-ajuste">
                                                <i class="fas fa-adjust"></i> AJUSTE
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo $mov['cantidad']; ?> pz</strong></td>
                                    <td><?php echo htmlspecialchars($mov['motivo']); ?></td>
                                    <td>
                                        <i class="fas fa-user-circle" style="color: #0066cc;"></i>
                                        <?php echo htmlspecialchars($mov['usuario_nombre']); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-exchange-alt fa-3x mb-3" style="color: #ccc;"></i>
                                    <h5>No hay movimientos registrados</h5>
                                    <p class="text-muted">Los movimientos se generan automáticamente al:</p>
                                    <div class="d-flex justify-content-center gap-3 mt-2">
                                        <span class="badge-entrada px-3 py-2">
                                            <i class="fas fa-plus-circle"></i> Dar de alta productos
                                        </span>
                                        <span class="badge-salida px-3 py-2">
                                            <i class="fas fa-shopping-cart"></i> Realizar ventas
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Leyenda -->
            <div class="mt-3 text-muted small">
                <i class="fas fa-info-circle me-1" style="color: #0066cc;"></i>
                Los movimientos de entrada se generan al dar de alta productos en el catálogo.<br>
                Los movimientos de salida se generan automáticamente al realizar ventas.
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Efectos táctiles
        document.querySelectorAll('button, .btn-filtrar').forEach(btn => {
            btn.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
            });
            btn.addEventListener('touchend', function() {
                this.style.transform = 'scale(1)';
            });
            btn.addEventListener('touchcancel', function() {
                this.style.transform = 'scale(1)';
            });
        });
        
        // Smooth scroll para alertas
        document.querySelector('.btn-ver-alertas')?.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('#productos-bajos').scrollIntoView({
                behavior: 'smooth'
            });
        });
    </script>
</body>
</html>