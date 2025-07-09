<?php
/**
 * Plugin Name: TEI XML Display
 * Description: Display TEI XML file with glosses, notes, line numbers, and a paragraph index.
 * Version: 1.0
 * Author: Doris Vickers
 */

defined('ABSPATH') or die('No script kiddies please!');

// Enqueue JS and CSS
add_action('wp_enqueue_scripts', function () {
    $plugin_url = plugin_dir_url(__FILE__);

    wp_enqueue_script(
        'tei-display-popup',
        $plugin_url . 'assets/js/tei-display.js',
        [],
        '1.0',
        true
    );

    wp_enqueue_style(
        'tei-display-style',
        $plugin_url . 'assets/css/tei-display.css',
        [],
        '1.0'
    );
});

// Load TEI XML and extract body and gloss map
function load_tei_dom_and_map($source, $is_url = false) {
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();

    if ($is_url) {
        $xml_data = @file_get_contents($source);
        if (!$xml_data || !$dom->loadXML($xml_data)) {
            return [null, [], null, null];
        }
    } else {
        if (!file_exists($source) || !$dom->load($source)) {
            return [null, [], null, null];
        }
    }

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace("tei", "http://www.tei-c.org/ns/1.0");

    $refMap = [];
    $nodes = $xpath->query('//tei:listPerson/tei:person | //tei:listObject/tei:object');
    foreach ($nodes as $item) {
        $id = $item->getAttributeNS('http://www.w3.org/XML/1998/namespace', 'id');
        $desc = '';
        foreach (['occupation', 'objectName', 'persName'] as $tag) {
            $descNode = $xpath->query(".//tei:$tag", $item)->item(0);
            if ($descNode) {
                $desc = trim($descNode->textContent);
                break;
            }
        }
        if ($id && $desc) {
            $refMap[$id] = $desc;
        }
    }

    $body = $xpath->query('//tei:text/tei:body')->item(0);
    return [$body, $refMap, $nodes, $xpath];
}

// Shortcode rendering
add_shortcode('tei_display', 'tei_display_shortcode');

function tei_display_shortcode($atts) {
    $atts = shortcode_atts(['file' => ''], $atts);
    $file_input = $atts['file'];

    if (!$file_input) {
        return '<p><strong>No TEI file specified.</strong></p>';
    }

    $is_url = filter_var($file_input, FILTER_VALIDATE_URL);
    $source = $is_url ? $file_input : plugin_dir_path(__FILE__) . 'tei-files/' . basename($file_input);

    list($body, $refMap, $entityNodes, $headerXPath) = load_tei_dom_and_map($source, $is_url);
    if (!$body) {
        return '<p><strong>Could not load TEI XML.</strong></p>';
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $xml_data = @file_get_contents($source);
    if (!$xml_data || !$dom->loadXML($xml_data)) {
        return '<p><strong>Could not parse TEI XML.</strong></p>';
    }

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace("tei", "http://www.tei-c.org/ns/1.0");

    $titleNode = $xpath->query('//tei:fileDesc/tei:titleStmt/tei:title')->item(0);
    $authorNode = $xpath->query('//tei:fileDesc/tei:titleStmt/tei:author')->item(0);
    $encoderNode = $xpath->query('//tei:fileDesc/tei:titleStmt/tei:respStmt/tei:persName')->item(0);

    $title = $titleNode ? esc_html($titleNode->textContent) : '';

    $author = '';
    if ($authorNode) {
        $authorText = trim($authorNode->textContent);
        if ($authorText !== '') {
            $authorRef = $authorNode->hasAttribute('ref') ? $authorNode->getAttribute('ref') : '';
            if ($authorRef) {
                $author = '<a href="' . esc_url($authorRef) . '" target="_blank" rel="noopener">' . esc_html($authorText) . '</a>';
            } else {
                $author = esc_html($authorText);
            }
        }
    }

    $encoder = $encoderNode ? esc_html($encoderNode->textContent) : '';

    $noteCounter = 1;
    $lineNumber = 1;
    $indexList = [];
    $html = tei_render_node($body, $refMap, $noteCounter, $lineNumber, $indexList);

    $indexHtml = '';
    if (!empty($indexList)) {
        $indexHtml .= '<div class="tei-index"><strong>Jump to paragraph:</strong><br>';
        foreach ($indexList as $item) {
            $indexHtml .= '<a class="tei-index-link" href="#' . esc_attr($item['id']) . '">' . esc_html($item['label']) . '</a> ';
        }
        $indexHtml .= '</div><hr>';
    }

    $xml_url = $is_url ? esc_url($source) : plugins_url('tei-files/' . basename($file_input), __FILE__);

    return '
        <div id="tei-top"></div>
        ' . ($title ? '<h1 class="tei-title">' . $title . '</h1>' : '') . '
        ' . ($author ? '<p class="tei-author">by ' . $author . '</p>' : '') . '
        ' . ($encoder ? '<p class="tei-encoder">XML by: ' . $encoder . '</p>' : '') . '
        <div class="tei-controls">
            <a class="tei-button download-button" href="' . $xml_url . '" download>🗎 Download XML</a>
            <button type="button" id="toggle-refs" class="tei-button toggle-button" data-label-on="Glosses On" data-label-off="Glosses Off">Glosses On</button>
            <button type="button" id="toggle-notes" class="tei-button toggle-button" data-label-on="Notes On" data-label-off="Notes Off">Notes On</button>
            <button type="button" id="toggle-lines" class="tei-button toggle-button" data-label-on="Line Numbers On" data-label-off="Line Numbers Off">Line Numbers On</button>
        </div>
        <div class="tei-output">' . $indexHtml . $html . '</div>';
}

// Render TEI recursively
function tei_render_node($node, $refMap, &$noteCounter, &$lineNumber, &$indexList) {
    if ($node->nodeType === XML_ELEMENT_NODE && $node->localName === 'app') return '';

    $html = '';
    foreach ($node->childNodes as $child) {
        if ($child->nodeType === XML_ELEMENT_NODE) {
            $tag = $child->localName;
            switch ($tag) {
                case 'p':
                    $n = $child->getAttribute('n');
                    $paraId = $n ? 'para-' . intval($n) : 'para-' . $lineNumber;
                    if ($n) {
                        $indexList[] = [
                            'label' => $n,
                            'id' => $paraId
                        ];
                    }
                    $lineNum = '<span class="tei-line-num">' . $lineNumber++ . '</span>';
                    $html .= '<div class="tei-line" id="' . esc_attr($paraId) . '">' . $lineNum . tei_render_node($child, $refMap, $noteCounter, $lineNumber, $indexList) . '</div>';
                    break;
                case 'l':
                    $lineNum = '<span class="tei-line-num">' . $lineNumber++ . '</span>';
                    $html .= '<div class="tei-line">' . $lineNum . tei_render_node($child, $refMap, $noteCounter, $lineNumber, $indexList) . '</div>';
                    break;
                case 'head':
                    $html .= '<h2 class="tei-head">' . tei_render_node($child, $refMap, $noteCounter, $lineNumber, $indexList) . '</h2>';
                    break;
                case 'persName':
                case 'objectName':
                case 'object':
                    $ref = $child->getAttribute('ref');
                    $id = ltrim($ref, '#');
                    $tooltip = isset($refMap[$id]) ? esc_attr($refMap[$id]) : '';
                    $content = tei_render_node($child, $refMap, $noteCounter, $lineNumber, $indexList);
                    $cls = 'tei-ref';
                    if ($tag === 'objectName') $cls .= ' objectName';

                    if (preg_match('/^https?:\\/\\//', $ref)) {
                        $html .= '<a href="' . esc_url($ref) . '" class="' . $cls . '" data-tooltip="' . $tooltip . '" target="_blank" rel="noopener">' . $content . '</a>';
                    } else {
                        $html .= '<span class="' . $cls . '" data-tooltip="' . $tooltip . '">' . $content . '</span>';
                    }
                    break;
                case 'note':
                    $tooltip = esc_attr(trim($child->textContent));
                    $html .= '<span class="tei-note" data-tooltip="' . $tooltip . '">[' . $noteCounter++ . ']</span>';
                    break;
                default:
                    $html .= tei_render_node($child, $refMap, $noteCounter, $lineNumber, $indexList);
                    break;
            }
        } elseif ($child->nodeType === XML_TEXT_NODE) {
            $html .= esc_html($child->nodeValue);
        }
    }
    return $html;
}

// Admin page for uploading TEI XML files
add_action('admin_menu', function () {
    add_submenu_page(
        'tools.php',
        'Upload TEI XML',
        'Upload TEI XML',
        'manage_options',
        'upload-tei-xml',
        'tei_xml_upload_page'
    );
});

function tei_xml_upload_page() {
    $message = '';

    if (isset($_POST['tei_xml_upload']) && check_admin_referer('tei_xml_upload_form')) {
        if (!empty($_FILES['tei_xml_file']['name'])) {
            $file = $_FILES['tei_xml_file'];
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            if (strtolower($ext) !== 'xml') {
                $message = '<div class="notice notice-error"><p>Only XML files are allowed.</p></div>';
            } else {
                $upload_dir = plugin_dir_path(__FILE__) . 'tei-files/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $target = $upload_dir . basename($file['name']);
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $message = '<div class="notice notice-success"><p>File uploaded successfully: ' . esc_html($file['name']) . '</p></div>';
                } else {
                    $message = '<div class="notice notice-error"><p>Failed to upload the file.</p></div>';
                }
            }
        } else {
            $message = '<div class="notice notice-warning"><p>No file selected.</p></div>';
        }
    }

    echo '<div class="wrap">';
    echo '<h1>Upload TEI XML File</h1>';
    echo $message;
    echo '<form method="post" enctype="multipart/form-data">';
    wp_nonce_field('tei_xml_upload_form');
    echo '<input type="file" name="tei_xml_file" accept=".xml" required>';
    echo '<p><input type="submit" name="tei_xml_upload" class="button button-primary" value="Upload"></p>';
    echo '</form>';
    echo '</div>';
}
