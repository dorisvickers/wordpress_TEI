# TEI XML Display – WordPress Plugin

**Version:** 1.0  
**Author:** [Doris Vickers](https://ucrisportal.univie.ac.at/de/persons/doris-magdalena-vickers)

## Description

The TEI XML Display plugin enables the rendering of TEI-encoded XML files directly on WordPress pages or posts using a shortcode. Designed for digital humanities projects, this plugin provides a visual interface with glosses, notes, paragraph numbers, a paragraph index, and admin upload functionality.

---

## Features

- Render TEI XML files using [tei_display file="filename.xml"] shortcode.
- Display of TEI `<body>` content
- Gloss tooltips for `<persName>`, `<objectName>`, and `<object>` references (from `<listPerson>` / `<listObject>`)
- Inline numbered notes from `<note>` elements
- Line numbering for `<p>` and `<l>` elements
- Toggle buttons to show/hide glosses, notes, and line numbers
- Download button for original TEI XML
- Clean, responsive styling with minimal dependencies

---

## Installation

1. Upload the plugin folder to /wp-content/plugins/tei-xml-display-metadata/
2. Activate the plugin through the WordPress Plugins screen.
3. Go to Tools → Upload TEI XML to upload your .xml files.

---

## Usage

Add the shortcode to any post or page:

```wordpress
[tei_display file="your-file.xml"]
```

The file should be uploaded to the tei-files/ directory inside the plugin folder via the Upload TEI XML admin menu.

Alternatively, you may use a direct URL:
[tei_display file="https://example.com/path/to/tei.xml"]

---

## Example

```wordpress
[tei_display file="example-tei.xml"]
```

This will render:
- The title and author (from the TEI header)
- The encoded content from the `<body>`
- Interactive glosses, notes, and line numbers

---

## TEI Requirements

For full functionality, your TEI XML should include:

- A `<teiHeader>` with:
  - `<titleStmt><title>` – for display title
  - `<titleStmt><author>` – for author name and optional link
  - `<respStmt><persName>` – for encoder credit

- A `<body>` with elements like:
  - `<p>` or `<l>` – for line-numbered text
  - `<note>` – for annotations
  - `<persName>`, `<objectName>` – with `@ref` attributes matching entries in:
    - `<listPerson><person xml:id="...">`
    - `<listObject><object xml:id="...">`

---

## Admin Panel
Accessible from Tools → Upload TEI XML in the WordPress admin menu. From here you can upload .xml files to be rendered using the shortcode.

---

## File Structure
/assets/js/tei-display.js – Handles interactivity (toggling notes/glosses).

/assets/css/tei-display.css – Styles the rendered output.

/tei-files/ – Location where uploaded XML files are stored.

tei-display.php – Main plugin file containing all logic.

---

## TEI XML Support
Recognizes and extracts:
- persName, objectName, object with gloss tooltips.
- note elements as tooltipped inline references.
- Paragraphs (<p>) and lines (<l>) with automatic numbering.
- Headers (<head>) rendered as section headings.
- Basic metadata from <titleStmt> including <title>, <author>, and encoder names.

---

## Credits

Created by **Doris Vickers**  
Maintained for digital humanities and TEI enthusiasts.
