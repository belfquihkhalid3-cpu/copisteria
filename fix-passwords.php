<?php
/**
 * Script de réparation des mots de passe - Copisteria
 * Corrige les hash de mots de passe dans la base de données
 */

require_once 'config/config.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Réparation Mots de Passe</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
    .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background-color: #f2f2f2; }
    .btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
    .btn:hover { background: #0056b3; }
    code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
</style>";
echo "</head><body>";

echo "<h1>🔧 Réparation des Mots de Passe - Copisteria</h1>";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<div class='info'>✅ Connexion à la base de données réussie</div>";
    
    // Vérifier les utilisateurs actuels
    echo "<h2>📊 État actuel des utilisateurs :</h2>";
    $stmt = $pdo->query("SELECT id, email, first_name, last_name, login_attempts, locked_until, is_active, LENGTH(password) as password_length FROM users");
    $users = $stmt->fetchAll();
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Email</th><th>Nom</th><th>Tentatives</th><th>Longueur Hash</th><th>Actif</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['first_name']} {$user['last_name']}</td>";
        echo "<td>{$user['login_attempts']}</td>";
        echo "<td>{$user['password_length']} caractères</td>";
        echo "<td>" . ($user['is_active'] ? '✅' : '❌') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Générer de nouveaux hash corrects
    echo "<h2>🔐 Génération de nouveaux hash :</h2>";
    
    $passwords = [
        'admin@copisteria.com' => 'admin123',
        'test@copisteria.com' => 'test123'
    ];
    
    foreach ($passwords as $email => $plainPassword) {
        echo "<h3>Pour $email :</h3>";
        
        // Générer plusieurs types de hash pour test
        $hash1 = password_hash($plainPassword, PASSWORD_DEFAULT);
        $hash2 = password_hash($plainPassword, PASSWORD_BCRYPT);
        $hash3 = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 10]);
        
        echo "<p><strong>Mot de passe :</strong> <code>$plainPassword</code></p>";
        echo "<p><strong>Hash DEFAULT :</strong> <code style='word-break: break-all;'>$hash1</code></p>";
        echo "<p><strong>Hash BCRYPT :</strong> <code style='word-break: break-all;'>$hash2</code></p>";
        echo "<p><strong>Hash BCRYPT cost=10 :</strong> <code style='word-break: break-all;'>$hash3</code></p>";
        
        // Tester la vérification
        echo "<p><strong>Tests de vérification :</strong></p>";
        echo "<ul>";
        echo "<li>Hash1 vs '$plainPassword' : " . (password_verify($plainPassword, $hash1) ? '✅ OK' : '❌ ERREUR') . "</li>";
        echo "<li>Hash2 vs '$plainPassword' : " . (password_verify($plainPassword, $hash2) ? '✅ OK' : '❌ ERREUR') . "</li>";
        echo "<li>Hash3 vs '$plainPassword' : " . (password_verify($plainPassword, $hash3) ? '✅ OK' : '❌ ERREUR') . "</li>";
        echo "</ul>";
        
        // Mettre à jour dans la base
        $stmt = $pdo->prepare("UPDATE users SET password = ?, login_attempts = 0, locked_until = NULL WHERE email = ?");
        $result = $stmt->execute([$hash3, $email]); // Utiliser hash3 (BCRYPT cost=10)
        
        if ($result) {
            echo "<div class='success'>✅ Mot de passe mis à jour avec succès pour $email</div>";
        } else {
            echo "<div class='error'>❌ Erreur lors de la mise à jour pour $email</div>";
        }
    }
    
    // Test final de connexion
    echo "<h2>🧪 Test de connexion :</h2>";
    
    foreach ($passwords as $email => $plainPassword) {
        $stmt = $pdo->prepare("SELECT id, email, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($plainPassword, $user['password'])) {
            echo "<div class='success'>✅ Test réussi pour $email avec le mot de passe $plainPassword</div>";
        } else {
            echo "<div class='error'>❌ Test échoué pour $email avec le mot de passe $plainPassword</div>";
        }
    }
    
    // Informations système PHP
    echo "<h2>🔍 Informations système :</h2>";
    echo "<ul>";
    echo "<li><strong>Version PHP :</strong> " . phpversion() . "</li>";
    echo "<li><strong>Algorithmes de hash disponibles :</strong> " . implode(', ', password_algos()) . "</li>";
    echo "<li><strong>Hash par défaut :</strong> " . PASSWORD_DEFAULT . "</li>";
    echo "<li><strong>Extension password :</strong> " . (function_exists('password_hash') ? '✅ Disponible' : '❌ Manquante') . "</li>";
    echo "</ul>";
    
    echo "<h2>🚀 Prochaines étapes :</h2>";
    echo "<div class='info'>";
    echo "<p>Si tous les tests sont ✅, vous pouvez maintenant :</p>";
    echo "<ol>";
    echo "<li>Aller à la page de connexion</li>";
    echo "<li>Utiliser les identifiants :</li>";
    echo "<ul>";
    echo "<li><strong>Admin :</strong> admin@copisteria.com / admin123</li>";
    echo "<li><strong>Test :</strong> test@copisteria.com / test123</li>";
    echo "</ul>";
    echo "</ol>";
    echo "</div>";
    
    echo "<p><a href='auth/login.php' class='btn'>🔐 Aller à la connexion</a>";
    echo "<a href='check-users.php' class='btn'>👥 Vérifier les utilisateurs</a></p>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p><strong>Vérifiez que :</strong></p>";
    echo "<ul>";
    echo "<li>XAMPP MySQL est démarré</li>";
    echo "<li>La base 'copisteria_db' existe</li>";
    echo "<li>La table 'users' est créée</li>";
    echo "</ul>";
}

echo "</body></html>";
?>