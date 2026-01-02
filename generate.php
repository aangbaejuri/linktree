<?php
ob_start();
require_once 'setting/connect.php';

header('Content-Type: application/json');

try {

    if (isset($_GET['get_limit'])) {
        $current_date = date('Y-m-d');

        if (!isset($_SESSION['generate_date']) || $_SESSION['generate_date'] !== $current_date) {
            $_SESSION['generate_date'] = $current_date;
            $_SESSION['generate_count'] = 0;
        }

        if (!isset($_SESSION['generate_count'])) {
            $_SESSION['generate_count'] = 0;
        }

        echo json_encode([
            'success' => true,
            'generate_count' => $_SESSION['generate_count'],
            'remaining' => 3 - $_SESSION['generate_count']
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }

    $current_date = date('Y-m-d');

    if (!isset($_SESSION['generate_date']) || $_SESSION['generate_date'] !== $current_date) {
        $_SESSION['generate_date'] = $current_date;
        $_SESSION['generate_count'] = 0;
    }

    if (!isset($_SESSION['generate_count'])) {
        $_SESSION['generate_count'] = 0;
    }

    if ($_SESSION['generate_count'] >= 3) {
        echo json_encode([
            'success' => false,
            'error' => 'Limit generate tercapai',
            'limit_reached' => true,
            'message' => 'Anda telah mencapai batas 3x generate per hari. Silakan coba lagi besok.',
            'generate_count' => $_SESSION['generate_count'],
            'remaining' => 0
        ]);
        exit;
    }

    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $custom_url = trim($_POST['custom_url'] ?? '');
    $url_logo = trim($_POST['url_logo'] ?? '');
    $urls = $_POST['urls'] ?? [];

    if (empty($deskripsi)) {
        echo json_encode(['success' => false, 'error' => 'Deskripsi tidak boleh kosong']);
        exit;
    }

    if (mb_strlen($deskripsi) > 1000) {
        echo json_encode(['success' => false, 'error' => 'Deskripsi maksimal 1000 karakter']);
        exit;
    }

    if (empty($custom_url)) {
        echo json_encode(['success' => false, 'error' => 'Custom URL tidak boleh kosong']);
        exit;
    }

    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $custom_url)) {
        echo json_encode(['success' => false, 'error' => 'Custom URL hanya boleh berisi huruf, angka, underscore, dan dash']);
        exit;
    }

    if (strlen($custom_url) < 3) {
        echo json_encode(['success' => false, 'error' => 'Custom URL minimal 3 karakter']);
        exit;
    }

    if (strlen($custom_url) > 50) {
        echo json_encode(['success' => false, 'error' => 'Custom URL maksimal 50 karakter']);
        exit;
    }

    $check_query = "SELECT id FROM linktrees WHERE custom_url = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param('s', $custom_url);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Custom URL "' . htmlspecialchars($custom_url) . '" sudah digunakan. Silakan pilih URL lain.'
        ]);
        exit;
    }
    $check_stmt->close();


    if (!is_array($urls) || count($urls) === 0) {
        echo json_encode(['success' => false, 'error' => 'Minimal harus ada 1 URL']);
        exit;
    }

    $urls = array_filter($urls, function ($url) {
        return !empty(trim($url));
    });

    if (count($urls) === 0) {
        echo json_encode(['success' => false, 'error' => 'Minimal harus ada 1 URL yang valid']);
        exit;
    }


    $system_prompt = "Kamu adalah expert web developer yang membuat halaman LinkTree yang indah dan modern.

PERSYARATAN TEKNIS:
- Gunakan HTML5 semantic dengan CSS inline/internal (dalam tag <style> jika diperlukan).
- Responsive untuk mobile dan desktop.
- Design modern, clean, dan menarik.
- Gunakan warna yang sesuai dengan deskripsi user.
- HANYA output HTML lengkap dengan tag <html>, <head>, <body>, dll.
- Jangan tambahkan komentar atau penjelasan apapun.

META TAGS SEO (WAJIB):
Tambahkan meta tags berikut di <head> untuk SEO dan social media preview:
- <meta charset=\"UTF-8\">
- <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
- <title> - Title menarik berdasarkan deskripsi
- <meta name=\"description\" content=\"...\"> - Deskripsi menarik max 160 karakter
- <meta name=\"keywords\" content=\"...\"> - 5-10 keywords relevan
- <link rel=\"canonical\" href=\"{CUSTOM_URL}\">

OPEN GRAPH META TAGS (untuk Facebook, LinkedIn, dll):
- <meta property=\"og:type\" content=\"website\">
- <meta property=\"og:url\" content=\"{CUSTOM_URL}\">
- <meta property=\"og:title\" content=\"...\"> - Title yang sama dengan <title>
- <meta property=\"og:description\" content=\"...\"> - Description yang sama
- <meta property=\"og:image\" content=\"...\"> - Logo jika ada, atau https://via.placeholder.com/1200x630/667eea/ffffff?text=LinkTree
- <meta property=\"og:site_name\" content=\"LinkTree\">

TWITTER CARD META TAGS:
- <meta name=\"twitter:card\" content=\"summary_large_image\">
- <meta name=\"twitter:url\" content=\"{CUSTOM_URL}\">
- <meta name=\"twitter:title\" content=\"...\">
- <meta name=\"twitter:description\" content=\"...\">
- <meta name=\"twitter:image\" content=\"...\">

FAVICON (WAJIB):
Jika ada URL logo, gunakan sebagai favicon:
- <link rel=\"icon\" type=\"image/png\" href=\"{URL_LOGO}\">
- <link rel=\"apple-touch-icon\" href=\"{URL_LOGO}\">

Jika TIDAK ada logo, generate favicon dengan emoji yang sesuai:
- <link rel=\"icon\" href=\"data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔗</text></svg>\">
Pilih emoji yang sesuai dengan tema/deskripsi (🔗📱💼🎨🎵📷 dll)

STRUCTURED DATA (JSON-LD):
Tambahkan JSON-LD untuk rich snippets:
<script type=\"application/ld+json\">
{
  \"@context\": \"https://schema.org\",
  \"@type\": \"ProfilePage\",
  \"name\": \"...\",
  \"description\": \"...\",
  \"url\": \"{CUSTOM_URL}\",
  \"image\": \"{URL_LOGO atau placeholder}\"
}
</script>

PENTING: 
- {CUSTOM_URL} akan diganti dengan URL actual
- Kamu HANYA output HTML lengkap, tanpa markdown code block, tanpa penjelasan.";

    $prompt = "Buat halaman LinkTree dengan ketentuan berikut:\n\n";
    $prompt .= "CUSTOM URL: " . $link_url . "lt/" . $custom_url . "\n";
    $prompt .= "Gunakan URL ini untuk {CUSTOM_URL} di meta tags canonical, og:url, twitter:url, dan JSON-LD.\n\n";
    $prompt .= "DESKRIPSI & STYLE:\n" . $deskripsi . "\n\n";

    if (!empty($url_logo)) {
        $prompt .= "LOGO: Gunakan logo dari URL: " . $url_logo . "\n";
        $prompt .= "Gunakan URL logo ini untuk favicon dan {URL_LOGO} di meta tags.\n\n";
    } else {
        $prompt .= "LOGO: Tidak ada. Generate emoji favicon yang sesuai dengan tema/deskripsi.\n\n";
    }

    $prompt .= "LINK-LINK:\n";
    foreach ($urls as $index => $url) {
        $prompt .= ($index + 1) . ". " . trim($url) . "\n";
    }

    $api_url = 'https://router.huggingface.co/v1/chat/completions';
    $ai_model = 'deepseek-ai/DeepSeek-V3.2-Exp:novita';

    $messages = [
        [
            'role' => 'system',
            'content' => $system_prompt
        ],
        [
            'role' => 'user',
            'content' => $prompt
        ]
    ];

    $payload = json_encode([
        'model' => $ai_model,
        'messages' => $messages,
        'max_tokens' => 4000,
        'temperature' => 0.7,
        'stream' => false
    ]);

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token_hf,
        'Content-Type: application/json',
        'x-use-cache: false'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        echo json_encode(['success' => false, 'error' => 'Koneksi ke API gagal: ' . $curl_error]);
        exit;
    }

    if ($http_code !== 200) {
        $error_detail = 'HTTP ' . $http_code;
        $response_data = json_decode($response, true);
        if (isset($response_data['error']['message'])) {
            $error_detail .= ': ' . $response_data['error']['message'];
        } elseif (isset($response_data['error'])) {
            $error_detail .= ': ' . $response_data['error'];
        }
        echo json_encode(['success' => false, 'error' => 'API Error: ' . $error_detail]);
        exit;
    }

    $result = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON response dari API']);
        exit;
    }

    $generated_html = '';

    if (isset($result['choices'][0]['message']['content'])) {
        $generated_html = trim($result['choices'][0]['message']['content']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Format response API tidak valid', 'debug' => $result]);
        exit;
    }

    if (empty($generated_html)) {
        echo json_encode(['success' => false, 'error' => 'AI tidak menghasilkan output']);
        exit;
    }

    if (preg_match('/^```html\s*(.*?)\s*```$/s', $generated_html, $matches)) {
        $generated_html = trim($matches[1]);
    } elseif (preg_match('/^```\s*(.*?)\s*```$/s', $generated_html, $matches)) {
        $generated_html = trim($matches[1]);
    }

    if (strpos($generated_html, '<!DOCTYPE') === false && strpos($generated_html, '<html') === false) {
        $generated_html = "<!DOCTYPE html>\n<html lang=\"id\">\n<head>\n<meta charset=\"UTF-8\">\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n<title>LinkTree</title>\n</head>\n<body>\n" . $generated_html . "\n</body>\n</html>";
    }

    $_SESSION['generate_count']++;

    echo json_encode([
        'success' => true,
        'html' => $generated_html,
        'generate_count' => $_SESSION['generate_count'],
        'remaining' => 3 - $_SESSION['generate_count']
    ]);
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Terjadi kesalahan sistem. Silakan coba lagi.'
    ]);
}
exit;
