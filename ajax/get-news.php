<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    $sql = "SELECT * FROM news ORDER BY published_at DESC LIMIT 4";
    $stmt = $db->query($sql);
    $news = $stmt->fetchAll();
    
    $newsWithImages = array_map(function($item, $index) {
        $gradients = [
            ['start' => '#E63946', 'mid' => '#F1FAEE', 'end' => '#A8DADC'],
            ['start' => '#1D3557', 'end' => '#457B9D'],
            ['start' => '#457B9D', 'mid' => '#A8DADC', 'end' => '#F1FAEE'],
            ['start' => '#FFB700', 'mid' => '#FF8C00', 'end' => '#FF6B35']
        ];
        
        $gradient = $gradients[$index % 4];
        
        $svgPatterns = [
            // Новый год
            '<svg class="news-image" viewBox="0 0 400 200">
                <defs>
                    <linearGradient id="newsGrad' . $index . '" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:' . $gradient['start'] . '"/>
                        ' . (isset($gradient['mid']) ? '<stop offset="50%" style="stop-color:' . $gradient['mid'] . '"/>' : '') . '
                        <stop offset="100%" style="stop-color:' . $gradient['end'] . '"/>
                    </linearGradient>
                </defs>
                <rect width="400" height="200" fill="url(#newsGrad' . $index . ')"/>
                <circle cx="80" cy="60" r="30" fill="white" opacity="0.3"/>
                <circle cx="320" cy="140" r="40" fill="white" opacity="0.2"/>
                <text x="200" y="90" font-family="Exo 2" font-size="52" font-weight="900" fill="white" text-anchor="middle">НОВОСТИ</text>
            </svg>',
        ];
        
        return [
            'id' => $item['id'],
            'title' => $item['title'],
            'description' => $item['description'],
            'date' => date('d.m.Y', strtotime($item['published_at'])),
            'image_html' => $svgPatterns[0]
        ];
    }, $news, array_keys($news));
    
    echo json_encode([
        'success' => true,
        'news' => $newsWithImages
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка загрузки новостей'
    ]);
}
?>
