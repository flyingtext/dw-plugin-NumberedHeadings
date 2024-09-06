# DokuWiki Plugin Numbered Headings

Prepend tiered numbers for hierarchical headings to DokuWiki without changing 
the actual behavior of the standard headings. 

**This is a modified version that add numbering to all headers.**

## Configuration

| Parameter  | Description                                                          |
| ---------- | -------------------------------------------------------------------- |
| tier1      | heading level corresponding to the first tier (default = 2)            |
| format     | numbering format (used in vsprintf) of each tier, JSON array string  |
| fancy      | styled heading numbers (default = false)                             |

default numbering format: `["%d.", "%d.%d", "%d.%d.%d", "%d.%d.%d.%d", "%d.%d.%d.%d.%d"]`

## Usage

Adding a `-` before the heading text will make the headings tiered-numbered.
You can choose the first tier level (**tier1**) in the Configration manager.
The **tier1** may be a fixed value (eg. level 2) or auto-detected in the page.
You can use `-#<number>` to set number of the heading.

    ====== - Level 1 Headline ======
    ===== - Level 2 Headline =====
    ==== -#5 Level 3 Headline ====
    ==== - Level 3 Headline ====
    ===== -#7 Level 2 Headline =====
    ==== - Level 3 Headline ====

When the config **tier1** is set to 2, the headings are interpreted as if you have written: 

    ====== Level 1 Headline ======
    ===== 1. Level 2 Headline =====
    ==== 1.5 Level 3 Headline ====
    ==== 1.6 Level 3 Headline ====
    ===== 7. Level 2 Headline =====
    ==== 7.1 Level 3 Headline ====

### Auto-Detect first tier level

When the config **tier1** is 0, the first appeared numbered headings should define
the value of **tier1** for the page.
You can use different first tier level in each page.

### Numbering format

The config **format** defines tiered numbering style.
Each tier format is the formatting string of
 [sprintf](https://www.php.net/manual/en/function.sprintf.php "sprintf"), 
must be enclosed in double quotes.
If n-th tier format is not defined, numbers are simply joined with a period.
Some format examples: 

    ["%d.", "%d.%d", "%d.%d.%d", "%d.%d.%d.%d", "%d.%d.%d.%d.%d"]
    ["Chapter %d.", "Section %d.%d", "Subsection %d.%d.%d", "(%4$d)"]
    ["Model %04d", "%04d-%02d"]

### Control numbering feature

The numbered headings that are prefixed with `--` (instead of single `-`) 
are not rendered, but can be used to sepecify level numbers or tier format.

    assume config tier1 is set to 0
    === --#1000 ["(%04d)"] ===   ... set number and tier format of the level
    === - item 1 ===    → (1001) item 1
    === -- ===             ... initialise tier1, format, headings counter
    ==== - item 2 ====  → 1. item 2

### Experimental: Alpha-numeric numbering

Character variables can be incremented in PHP, like A0, A1, A2 ...
You can use `-"<string>"` to set number string of the heading.

    === --"A0" ["%s."] ===
    === - item a1 ===   → A1. item a1
    === - item a2 ===   → A2. item a2
    == - item a2.1 ==   → A2.1 item a2.1

