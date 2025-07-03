# TEI XML Display (Metadata) – WordPress Plugin

**Version:** 1.0  
**Author:** [Doris Vickers](https://ucrisportal.univie.ac.at/de/persons/doris-magdalena-vickers){:target="_blank"}

## Description

This WordPress plugin renders [TEI XML](https://tei-c.org/){:target="_blank"} files with rich features such as gloss tooltips, inline notes, and line numbering. It is especially useful for digital editions of historical texts, poetry, or annotated documents encoded in the TEI standard.

The plugin provides a simple shortcode interface to load and display TEI documents directly within posts or pages.

---

## Features

- Display of TEI `<body>` content
- Gloss tooltips for `<persName>`, `<objectName>`, and `<object>` references (from `<listPerson>` / `<listObject>`)
- Inline numbered notes from `<note>` elements
- Line numbering for `<p>` and `<l>` elements
- Toggle buttons to show/hide glosses, notes, and line numbers
- Download button for original TEI XML
- Clean, responsive styling with minimal dependencies

---

## Installation

1. Clone or download this repository into your WordPress plugin directory.

2. Activate the plugin in your WordPress dashboard under **Plugins > Installed Plugins**.

3. Create a `tei-files/` directory inside the plugin folder and upload your `.xml` TEI files there.

---

## Usage

Add the shortcode to any post or page:

```wordpress
[tei_display file="your-file.xml"]
```

- The `file` parameter can be either:
  - The name of a file inside the plugin's `tei-files/` folder
  - A full URL to a TEI XML file online

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

## Credits

Created by **Doris Vickers**  
Maintained for digital humanities and TEI enthusiasts.
