<?php
/*
 * Plugin Name:       Bra Size Converter
 * Description:       Allow convert letter sizes to numbers.
 * Version:           1.0
 * Requires at least: 5.2
 * Requires PHP:      8.3
 * Author:            Hapych Kyrylo
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bra-size-converter
 * Domain Path:       /lang
 */


if (!defined('ABSPATH')) {
    die;
}

function convert_bra_size($letter): int|string
{
    $sizes = [
        'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4,
        'e' => 5, 'f' => 6, 'g' => 7, 'h' => 8,
        'i' => 9, 'j' => 10
    ];
    $letter = strtolower(trim($letter));
    return $sizes[$letter] ?? "Недопустимый размер.";
}

// Шорткод для отображения формы
function bra_size_converter_shortcode(): bool|string
{
    $ajax_nonce = wp_create_nonce('bra_size_nonce');
    ob_start();
    ?>
    <form id="bra-size-converter-form">
        <label for="bra-size-input">Введите буквенный размер:</label>
        <input type="text" id="bra-size-input" name="bra_size" maxlength="1" required>
        <button type="button" id="convert-button">Конвертировать</button>
        <p id="conversion-result"></p>
    </form>
    <script>
        document.getElementById('convert-button').addEventListener('click', function () {
            const braSize = document.getElementById('bra-size-input').value;
            const resultElement = document.getElementById('conversion-result');
            resultElement.textContent = 'Обработка...';

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'convert_bra_size',
                    bra_size: braSize,
                    _ajax_nonce: '<?php echo $ajax_nonce; ?>'
                })
            })
                .then(response => response.json())
                .then(data => {
                    resultElement.textContent = data.message;
                })
                .catch(error => {
                    resultElement.textContent = 'Ошибка обработки.';
                    console.error(error);
                });
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('convert_bra_size', 'bra_size_converter_shortcode');

// Обработка AJAX-запроса
function handle_bra_size_conversion(): void
{
    // Проверка nonce для защиты от CSRF
    check_ajax_referer('bra_size_nonce', '_ajax_nonce');

    // Получение и очистка данных
    $bra_size = isset($_POST['bra_size']) ? sanitize_text_field($_POST['bra_size']) : '';

    // Выполнение конвертации
    $result = convert_bra_size($bra_size);

    // Отправка результата в JSON-формате
    wp_send_json(['message' => $result]);
}
add_action('wp_ajax_convert_bra_size', 'handle_bra_size_conversion');
add_action('wp_ajax_nopriv_convert_bra_size', 'handle_bra_size_conversion');

// Добавление страницы настроек в админ-панели
function bra_size_converter_settings_page(): void
{
    add_menu_page(
        'Конвертер размеров',
        'Bra Size Converter',
        'manage_options',
        'bra-size-converter',
        'bra_size_converter_settings_content'
    );
}
add_action('admin_menu', 'bra_size_converter_settings_page');

function bra_size_converter_settings_content(): void
{
    ?>
    <div class="wrap">
        <h1>Конвертер размеров бюстгальтера</h1>
        <p>
            Этот плагин позволяет конвертировать размер бюстгальтера из буквенного обозначения
            (например, A, B, C) в числовой формат (например, 1, 2, 3). Вы можете воспользоваться этим
            функционалом через:
        </p>
        <ul>
            <li>Публичный шорткод: <code>[convert_bra_size]</code>, который можно добавить на любую страницу.</li>
            <li>Эту административную страницу для ручного ввода и проверки данных.</li>
        </ul>
        <p>
            Список соответствий размеров:
        </p>
        <ul>
            <li>A = 1</li>
            <li>B = 2</li>
            <li>C = 3</li>
            <li>D = 4</li>
            <li>E = 5</li>
            <li>F = 6</li>
            <li>G = 7</li>
            <li>H = 8</li>
            <li>I = 9</li>
            <li>J = 10</li>
        </ul>
        <p>
            Обратите внимание, что вводимые данные чувствительны к неверным значениям. Если вы введете
            недопустимый символ или букву, будет выдано сообщение об ошибке.
        </p>

        <form method="POST" id="admin-bra-size-converter-form">
            <?php wp_nonce_field('bra_size_admin_nonce', 'bra_size_admin_nonce_field'); ?>
            <label for="admin-bra-size-input">Введите буквенный размер:</label>
            <input type="text" id="admin-bra-size-input" name="bra_size" maxlength="1" required>
            <button type="submit">Конвертировать</button>
        </form>
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Проверка nonce
            if (!isset($_POST['bra_size_admin_nonce_field']) ||
                !wp_verify_nonce($_POST['bra_size_admin_nonce_field'], 'bra_size_admin_nonce')) {
                echo '<p>Ошибка безопасности. Попробуйте снова.</p>';
                return;
            }

            // Обработка данных
            $bra_size = sanitize_text_field($_POST['bra_size']);
            $result = convert_bra_size($bra_size);
            echo '<p>Результат: ' . esc_html($result) . '</p>';
        }
        ?>
    </div>
    <?php
}
?>