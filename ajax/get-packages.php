<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    $sql = "SELECT * FROM donate_packages WHERE is_active = 1 ORDER BY sort_order ASC";
    $stmt = $db->query($sql);
    $packages = $stmt->fetchAll();
    
    $packagesWithImages = array_map(function($item, $index) {
        $svgPatterns = [
            '<svg class="package-image" viewBox="0 0 200 150">
                <rect width="200" height="150" fill="#F0F0F0"/>
                <text x="100" y="90" font-family="Exo 2" font-size="48" font-weight="900" fill="#1565C0" text-anchor="middle">₽</text>
            </svg>',
            '<svg class="package-image" viewBox="0 0 200 150">
                <rect width="200" height="150" fill="#E8EEF5"/>
                <text x="100" y="90" font-family="Exo 2" font-size="52" font-weight="900" fill="#FF5722" text-anchor="middle">₽₽</text>
            </svg>',
            '<svg class="package-image" viewBox="0 0 200 150">
                <rect width="200" height="150" fill="#FFE8D6"/>
                <text x="100" y="90" font-family="Exo 2" font-size="56" font-weight="900" fill="#FFC107" text-anchor="middle">₽₽₽</text>
            </svg>',
            '<svg class="package-image" viewBox="0 0 200 150">
                <rect width="200" height="150" fill="#D4E4F7"/>
                <text x="100" y="90" font-family="Exo 2" font-size="60" font-weight="900" fill="#1565C0" text-anchor="middle">💎</text>
            </svg>',
            '<svg class="package-image" viewBox="0 0 200 150">
                <defs>
                    <linearGradient id="goldGrad">
                        <stop offset="0%" style="stop-color:#FFD700"/>
                        <stop offset="100%" style="stop-color:#FF8C00"/>
                    </linearGradient>
                </defs>
                <rect width="200" height="150" fill="url(#goldGrad)" opacity="0.2"/>
                <text x="100" y="90" font-family="Exo 2" font-size="64" font-weight="900" fill="#FFC107" text-anchor="middle">👑</text>
            </svg>'
        ];
        
        return [
            'id' => $item['id'],
            'name' => $item['name'],
            'title' => $item['title'],
            'amount' => $item['amount'],
            'formatted_amount' => number_format($item['amount'], 0, ',', ' '),
            'image_html' => $svgPatterns[$index % 5]
        ];
    }, $packages, array_keys($packages));
    
    echo json_encode([
        'success' => true,
        'packages' => $packagesWithImages
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка загрузки пакетов'
    ]);
}
?>
