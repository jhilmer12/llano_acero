<?php
// test_api.php
echo "<h2>Test de API</h2>";

// Test de login
$url = 'http://localhost/ferreteria_llano_acero_2/api/index.php';

$data = [
    'action' => 'login',
    'email' => 'admin@ferreteria.com',
    'password' => 'password'
];

echo "<h3>Probando login...</h3>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Código HTTP: " . $http_code . "<br>";
echo "Respuesta: " . $response . "<br>";

$result = json_decode($response, true);
if ($result && $result['success']) {
    echo "<p style='color: green;'>✅ Login exitoso</p>";
} else {
    echo "<p style='color: red;'>❌ Error en login: " . ($result['message'] ?? 'Desconocido') . "</p>";
}
?>