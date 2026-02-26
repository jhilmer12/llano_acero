<?php
// test_connection.php
echo "<h2>Test de Conexión a Base de Datos</h2>";

try {
    // Test de conexión básica
    $pdo = new PDO("mysql:host=localhost", "root", "");
    echo "✓ Conexión a MySQL exitosa<br>";
    
    // Test de base de datos
    $pdo->exec("USE ferreteria_llano_acero_2");
    echo "✓ Base de datos ferreteria_llano_acero_2 accesible<br>";
    
    // Test de tablas
    $tables = ['usuarios', 'categorias', 'productos', 'proveedores'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Tabla '$table' existe<br>";
        } else {
            echo "✗ Tabla '$table' NO existe<br>";
        }
    }
    
    // Test de usuarios
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM usuarios");
    $result = $stmt->fetch();
    echo "✓ Usuarios en sistema: " . $result['count'] . "<br>";
    
    echo "<h3>✅ Todas las pruebas pasaron</h3>";
    
} catch (PDOException $e) {
    echo "<h3>❌ Error encontrado:</h3>";
    echo "Mensaje: " . $e->getMessage() . "<br>";
    echo "Código: " . $e->getCode() . "<br>";
    
    echo "<h4>Solución:</h4>";
    echo "1. Verifica que MySQL esté corriendo<br>";
    echo "2. Verifica el usuario y contraseña en config/database.php<br>";
    echo "3. Ejecuta setup_database.php para crear la base de datos<br>";
}
?>